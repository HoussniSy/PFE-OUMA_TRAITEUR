<?php

namespace App\Controller;

use App\Entity\Document;
use App\Entity\DocumentItem;
use App\Form\DocumentType;
use App\Repository\ClientRepository;
use App\Repository\CompanyRepository;
use App\Repository\DocumentRepository;
use App\Repository\SavedFilterRepository;
use App\Service\EmailService;
use App\Service\FilterService;
use App\Service\PdfGeneratorService;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/document')]
#[IsGranted('ROLE_USER')]
class DocumentController extends AbstractController
{
    public function __construct(
        private ClientRepository $clientRepository,
        private SavedFilterRepository $savedFilterRepository,
        private FilterService $filterService,
    ) {
    }

    #[Route('', name: 'app_document_index', methods: ['GET'])]
    public function index(Request $request, DocumentRepository $documentRepository): Response
    {
        // ==========================================
        // FILTRES AVANCÉS - DÉBUT
        // ==========================================

        // Extraire les filtres depuis la requête
        $filters = $this->filterService->extractFiltersFromRequest($request);

        // Valider les filtres
        $validationErrors = $this->filterService->validateFilters($filters);

        // Si un raccourci de période est utilisé, calculer les dates
        if (isset($filters['period_shortcut']) && $filters['period_shortcut'] !== '') {
            $dates = $this->filterService->getPeriodDatesFromShortcut($filters['period_shortcut']);
            if ($dates['from'] && $dates['to']) {
                $filters['date_from'] = $dates['from']->format('Y-m-d');
                $filters['date_to'] = $dates['to']->format('Y-m-d');
            }
        }

        // Construire la requête avec filtres
        $qb = $documentRepository->createQueryBuilder('d')
            ->leftJoin('d.client', 'c')
            ->orderBy('d.createdAt', 'DESC');

        // Appliquer les filtres
        // Type
        $type = $request->query->get('type');
        if ($type && $type !== 'all') {
            $qb->andWhere('d.type = :type')
                ->setParameter('type', $type);
            $filters['type'] = $type;
        }

        // Statut
        $status = $request->query->get('status');
        if ($status && $status !== 'all') {
            $qb->andWhere('d.status = :status')
                ->setParameter('status', $status);
            $filters['status'] = $status;
        }

        // Recherche
        $search = $request->query->get('search');
        if ($search) {
            $qb->andWhere('d.number LIKE :search OR c.name LIKE :search')
                ->setParameter('search', '%' . $search . '%');
            $filters['search'] = $search;
        }

        // Client
        if (isset($filters['client_id']) && $filters['client_id'] !== 'all') {
            $qb->andWhere('d.client = :clientId')
                ->setParameter('clientId', $filters['client_id']);
        }

        // Période
        if (isset($filters['date_from'])) {
            $qb->andWhere('d.date >= :dateFrom')
                ->setParameter('dateFrom', new \DateTime($filters['date_from']));
        }

        if (isset($filters['date_to'])) {
            $qb->andWhere('d.date <= :dateTo')
                ->setParameter('dateTo', new \DateTime($filters['date_to'] . ' 23:59:59'));
        }

        // Montant
        if (isset($filters['amount_min'])) {
            $qb->andWhere('d.totalTtc >= :amountMin')
                ->setParameter('amountMin', $filters['amount_min']);
        }

        if (isset($filters['amount_max'])) {
            $qb->andWhere('d.totalTtc <= :amountMax')
                ->setParameter('amountMax', $filters['amount_max']);
        }

        $documents = $qb->getQuery()->getResult();

        // Compter le total sans filtres
        $totalDocuments = $documentRepository->count([]);

        // Récupérer tous les clients pour le dropdown
        $clients = $this->clientRepository->findAll();

        // Récupérer les filtres sauvegardés de l'utilisateur
        $savedFilters = $this->savedFilterRepository->findByUserAndPage($this->getUser(), 'document');

        // Raccourcis de période
        $periodShortcuts = $this->filterService->getPeriodShortcuts();

        // Nombre de filtres actifs
        $activeFiltersCount = $this->filterService->countActiveFilters($filters);

        // Résumé des filtres
        $filterSummary = $this->filterService->getFilterSummary($filters);

        // ==========================================
        // FILTRES AVANCÉS - FIN
        // ==========================================

        return $this->render('document/index.html.twig', [
            'documents' => $documents,
            'currentType' => $type,
            'currentStatus' => $status,
            'search' => $search,
            // Variables pour filtres avancés
            'clients' => $clients,
            'filters' => $filters,
            'typeFilter' => $filters['type'] ?? 'all',
            'statusFilter' => $filters['status'] ?? 'all',
            'savedFilters' => $savedFilters,
            'periodShortcuts' => $periodShortcuts,
            'activeFiltersCount' => $activeFiltersCount,
            'filterSummary' => $filterSummary,
            'totalDocuments' => $totalDocuments,
            'validationErrors' => $validationErrors,
        ]);
    }

    #[Route('/new/{type}', name: 'app_document_new', requirements: ['type' => 'quote|invoice'], methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        DocumentRepository $documentRepository,
        CompanyRepository $companyRepository,
        string $type
    ): Response {
        $document = new Document();
        $document->setType($type);
        $document->setDate(new \DateTime());
        $document->setNumber($documentRepository->generateNumber($type));
        $document->setLocation('Nouakchott');

        // Pré-remplir avec les valeurs par défaut de la société
        $user = $this->getUser();
        $company = $user->getCompany() ?? $companyRepository->findFirst();
        if ($company) {
            $document->setCompany($company);
            $document->setTaxRate($company->getDefaultTaxRate());
            $document->setPaymentTerms($company->getDefaultPaymentTerms());
            $document->setCurrency($company->getDefaultCurrency());
        }

        $form = $this->createForm(DocumentType::class, $document);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                // Calcul des totaux
                $document->calculateTotals();

                $entityManager->persist($document);
                $entityManager->flush();

                $this->addFlash('success', 'Le document a été créé avec succès.');
                return $this->redirectToRoute('app_document_show', ['id' => $document->getId()]);
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de la création : ' . $e->getMessage());
            }
        }

        return $this->render('document/new.html.twig', [
            'document' => $document,
            'form' => $form,
            'type' => $type,
        ]);
    }

    #[Route('/{id}', name: 'app_document_show', methods: ['GET'])]
    public function show(Document $document, CompanyRepository $companyRepository): Response
    {
        $company = $companyRepository->findFirst();

        return $this->render('document/show.html.twig', [
            'document' => $document,
            'company' => $company,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_document_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Document $document,
        EntityManagerInterface $entityManager
    ): Response {
        // Vérification des permissions
        if ($document->getStatus() !== Document::STATUS_DRAFT && !$this->isGranted('ROLE_COMPTABLE')) {
            $this->addFlash('error', 'Vous ne pouvez modifier que les documents en brouillon.');
            return $this->redirectToRoute('app_document_show', ['id' => $document->getId()]);
        }

        $form = $this->createForm(DocumentType::class, $document);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                // Recalcul des totaux
                $document->calculateTotals();

                $entityManager->flush();

                $this->addFlash('success', 'Le document a été mis à jour avec succès.');
                return $this->redirectToRoute('app_document_show', ['id' => $document->getId()]);
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de la mise à jour : ' . $e->getMessage());
            }
        }

        return $this->render('document/edit.html.twig', [
            'document' => $document,
            'form' => $form,
            'type' => $document->getType(),
        ]);
    }

    #[Route('/{id}/delete', name: 'app_document_delete', methods: ['POST'])]
    #[IsGranted('ROLE_COMPTABLE')]
    public function delete(
        Request $request,
        Document $document,
        EntityManagerInterface $entityManager
    ): Response {
        if ($this->isCsrfTokenValid('delete' . $document->getId(), $request->request->get('_token'))) {
            try {
                $entityManager->remove($document);
                $entityManager->flush();

                $this->addFlash('success', 'Le document a été supprimé avec succès.');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de la suppression : ' . $e->getMessage());
            }
        }

        return $this->redirectToRoute('app_document_index');
    }

    #[Route('/{id}/pdf', name: 'app_document_pdf', methods: ['GET'])]
    public function generatePdf(
        Document $document,
        PdfGeneratorService $pdfGenerator,
        CompanyRepository $companyRepository
    ): Response {
        $company = $companyRepository->findFirst();
        return $pdfGenerator->generateDocumentPdf($document, $company);
    }

    #[Route('/{id}/send-email', name: 'app_document_send_email', methods: ['POST'])]
    public function sendEmail(
        Request $request,
        Document $document,
        EmailService $emailService,
        CompanyRepository $companyRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $recipientEmail = $request->request->get('email');
        $message = $request->request->get('message');

        if (!$recipientEmail) {
            $recipientEmail = $document->getClient()->getEmail();
        }

        if (!$recipientEmail) {
            $this->addFlash('error', 'Aucune adresse email disponible pour ce client.');
            return $this->redirectToRoute('app_document_show', ['id' => $document->getId()]);
        }

        try {
            $company = $companyRepository->findFirst();
            $emailService->sendDocument($document, $company, $recipientEmail, null, $message);

            // Mettre à jour le statut si c'était en brouillon
            if ($document->getStatus() === Document::STATUS_DRAFT) {
                $document->setStatus(Document::STATUS_SENT);
                $entityManager->flush();
            }

            $this->addFlash('success', 'Le document a été envoyé par email avec succès.');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de l\'envoi : ' . $e->getMessage());
        }

        return $this->redirectToRoute('app_document_show', ['id' => $document->getId()]);
    }

    #[Route('/{id}/convert-to-invoice', name: 'app_document_convert_to_invoice', methods: ['POST'])]
    public function convertToInvoice(
        Request $request,
        Document $document,
        EntityManagerInterface $entityManager,
        DocumentRepository $documentRepository
    ): Response {
        if (!$this->isCsrfTokenValid('convert' . $document->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_document_show', ['id' => $document->getId()]);
        }

        if ($document->getType() !== Document::TYPE_QUOTE) {
            $this->addFlash('error', 'Seuls les devis peuvent être convertis en facture.');
            return $this->redirectToRoute('app_document_show', ['id' => $document->getId()]);
        }

        try {
            // Créer une nouvelle facture
            $invoice = new Document();
            $invoice->setType(Document::TYPE_INVOICE);
            $invoice->setNumber($documentRepository->generateNumber(Document::TYPE_INVOICE));
            $invoice->setDate(new \DateTime());
            $invoice->setClient($document->getClient());
            $invoice->setLocation($document->getLocation());
            $invoice->setTaxRate($document->getTaxRate());
            $invoice->setCurrency($document->getCurrency());
            $invoice->setStatus(Document::STATUS_DRAFT);

            // Copier les items
            foreach ($document->getItems() as $item) {
                $newItem = new DocumentItem();
                $newItem->setDesignation($item->getDesignation());
                $newItem->setNumberOfDays($item->getNumberOfDays());
                $newItem->setNumberOfPersons($item->getNumberOfPersons());
                $newItem->setNumberOfServices($item->getNumberOfServices());
                $newItem->setUnitPrice($item->getUnitPrice());
                $newItem->setPosition($item->getPosition());

                $invoice->addItem($newItem);
            }

            // Calculer les totaux
            $invoice->calculateTotals();

            $entityManager->persist($invoice);
            $entityManager->flush();

            $this->addFlash('success', 'Le devis a été converti en facture avec succès.');
            return $this->redirectToRoute('app_document_show', ['id' => $invoice->getId()]);
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de la conversion : ' . $e->getMessage());
            return $this->redirectToRoute('app_document_show', ['id' => $document->getId()]);
        }
    }

    #[Route('/{id}/change-status', name: 'app_document_change_status', methods: ['POST'])]
    public function changeStatus(
        Request $request,
        Document $document,
        EntityManagerInterface $entityManager
    ): Response {
        $newStatus = $request->request->get('status');

        // Vérifier que le statut est valide
        $validStatuses = [
            Document::STATUS_DRAFT,
            Document::STATUS_SENT,
            Document::STATUS_PARTIALLY_PAID,
            Document::STATUS_PAID,
            Document::STATUS_CANCELLED
        ];

        if (!in_array($newStatus, $validStatuses)) {
            $this->addFlash('error', 'Statut invalide.');
            return $this->redirectToRoute('app_document_show', ['id' => $document->getId()]);
        }

        // Vérification des permissions selon le statut
        $currentStatus = $document->getStatus();

        // Règles de transition :
        // 1. ROLE_USER peut : draft → sent
        // 2. ROLE_COMPTABLE peut : sent → partially_paid/paid
        // 3. ROLE_ADMIN peut : tout + cancelled

        // Tout le monde peut passer de draft à sent
        if ($currentStatus === Document::STATUS_DRAFT && $newStatus === Document::STATUS_SENT) {
            // OK pour ROLE_USER
        }
        // Seulement COMPTABLE+ peut changer vers paid/partially_paid
        elseif (in_array($newStatus, [Document::STATUS_PAID, Document::STATUS_PARTIALLY_PAID])) {
            if (!$this->isGranted('ROLE_COMPTABLE')) {
                $this->addFlash('error', 'Vous n\'avez pas les permissions pour marquer un document comme payé.');
                return $this->redirectToRoute('app_document_show', ['id' => $document->getId()]);
            }
        }
        // Seulement ADMIN peut annuler
        elseif ($newStatus === Document::STATUS_CANCELLED) {
            if (!$this->isGranted('ROLE_ADMIN')) {
                $this->addFlash('error', 'Seul un administrateur peut annuler un document.');
                return $this->redirectToRoute('app_document_show', ['id' => $document->getId()]);
            }
        }
        // Autres transitions nécessitent COMPTABLE minimum
        else {
            if (!$this->isGranted('ROLE_COMPTABLE')) {
                $this->addFlash('error', 'Vous n\'avez pas les permissions pour effectuer ce changement.');
                return $this->redirectToRoute('app_document_show', ['id' => $document->getId()]);
            }
        }

        try {
            $document->setStatus($newStatus);
            $entityManager->flush();

            $this->addFlash('success', 'Le statut a été modifié avec succès.');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors du changement de statut : ' . $e->getMessage());
        }

        return $this->redirectToRoute('app_document_show', ['id' => $document->getId()]);
    }

    #[Route('/{id}/send-reminder', name: 'app_document_send_reminder', methods: ['POST'])]
    public function sendReminder(
        Request $request,
        Document $document,
        NotificationService $notificationService
    ): Response {
        // Vérifier le token CSRF
        if (!$this->isCsrfTokenValid('send-reminder' . $document->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_document_show', ['id' => $document->getId()]);
        }

        // Vérifier que c'est une facture
        if ($document->getType() !== Document::TYPE_INVOICE) {
            $this->addFlash('error', 'Les rappels ne peuvent être envoyés que pour les factures.');
            return $this->redirectToRoute('app_document_show', ['id' => $document->getId()]);
        }

        // Vérifier que la facture n'est pas déjà payée
        if ($document->isFullyPaid()) {
            $this->addFlash('warning', 'Cette facture est déjà entièrement payée.');
            return $this->redirectToRoute('app_document_show', ['id' => $document->getId()]);
        }

        // Vérifier que le client a un email
        if (!$document->getClient() || !$document->getClient()->getEmail()) {
            $this->addFlash('error', 'Le client n\'a pas d\'adresse email renseignée.');
            return $this->redirectToRoute('app_document_show', ['id' => $document->getId()]);
        }

        try {
            // Récupérer le message personnalisé (optionnel)
            $customMessage = $request->request->get('message');

            // Créer et envoyer la notification immédiatement
            $notification = $notificationService->createManualReminder($document, $customMessage);

            if ($notification) {
                $notificationService->sendNotification($notification);
                $this->addFlash('success', 'Le rappel de paiement a été envoyé avec succès à ' . $document->getClient()->getEmail());
            } else {
                $this->addFlash('error', 'Impossible de créer la notification.');
            }
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de l\'envoi du rappel : ' . $e->getMessage());
        }

        return $this->redirectToRoute('app_document_show', ['id' => $document->getId()]);
    }
}
