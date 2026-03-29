<?php

namespace App\Controller\Api;

use App\Entity\Document;
use App\Entity\DocumentItem;
use App\Entity\Payment;
use App\Repository\ClientRepository;
use App\Repository\DocumentRepository;
use App\Service\PdfGeneratorService;
use App\Service\EmailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ApiDocumentController extends AbstractController
{
    /**
     * Sérialise un document pour la liste (format léger).
     */
    private function serializeDocumentListItem(Document $doc): array
    {
        return [
            'id' => $doc->getId(),
            'type' => $doc->getType(),
            'typeLabel' => $doc->getTypeLabel(),
            'number' => $doc->getNumber(),
            'date' => $doc->getDate()?->format('Y-m-d'),
            'dueDate' => $doc->getDueDate()?->format('Y-m-d'),
            'status' => $doc->getStatus(),
            'statusLabel' => $doc->getStatusLabel(),
            'totalHt' => $doc->getTotalHt(),
            'taxRate' => $doc->getTaxRate(),
            'totalTtc' => $doc->getTotalTtc(),
            'currency' => $doc->getCurrency(),
            'clientId' => $doc->getClient()?->getId(),
            'clientName' => $doc->getClient()?->getName(),
            'totalPaid' => $doc->getTotalPaid(),
            'remainingAmount' => $doc->getRemainingAmount(),
            'isOverdue' => $doc->isOverdue(),
            'createdAt' => $doc->getCreatedAt()?->format('c'),
        ];
    }

    /**
     * Sérialise un document complet pour le détail (avec items, paiements, infos client).
     */
    private function serializeDocument(Document $doc): array
    {
        $items = [];
        foreach ($doc->getItems() as $item) {
            $items[] = [
                'id' => $item->getId(),
                'designation' => $item->getDesignation(),
                'numberOfDays' => $item->getNumberOfDays(),
                'numberOfPersons' => $item->getNumberOfPersons(),
                'numberOfServices' => $item->getNumberOfServices(),
                'unitPrice' => $item->getUnitPrice(),
                'totalAmount' => $item->getTotalAmount(),
                'position' => $item->getPosition(),
            ];
        }

        $payments = [];
        foreach ($doc->getPayments() as $payment) {
            $payments[] = [
                'id' => $payment->getId(),
                'datePaiement' => $payment->getDatePaiement()?->format('c'),
                'montant' => $payment->getMontant(),
                'modePaiement' => $payment->getModePaiement(),
                'modePaiementLabel' => $payment->getModePaiementLabel(),
                'statutPaiement' => $payment->getStatutPaiement(),
                'statutLabel' => $payment->getStatutLabel(),
                'reference' => $payment->getReference(),
                'notes' => $payment->getNotes(),
                'createdAt' => $payment->getCreatedAt()?->format('c'),
            ];
        }

        return [
            'id' => $doc->getId(),
            'type' => $doc->getType(),
            'typeLabel' => $doc->getTypeLabel(),
            'number' => $doc->getNumber(),
            'date' => $doc->getDate()?->format('Y-m-d'),
            'dueDate' => $doc->getDueDate()?->format('Y-m-d'),
            'status' => $doc->getStatus(),
            'statusLabel' => $doc->getStatusLabel(),
            'location' => $doc->getLocation(),
            'totalHt' => $doc->getTotalHt(),
            'taxRate' => $doc->getTaxRate(),
            'totalTtc' => $doc->getTotalTtc(),
            'currency' => $doc->getCurrency(),
            'paymentTerms' => $doc->getPaymentTerms(),
            'clientId' => $doc->getClient()?->getId(),
            'clientName' => $doc->getClient()?->getName(),
            'clientEmail' => $doc->getClient()?->getEmail(),
            'clientPhone' => $doc->getClient()?->getPhone(),
            'clientAddress' => $doc->getClient()?->getAddress(),
            'items' => $items,
            'payments' => $payments,
            'totalPaid' => $doc->getTotalPaid(),
            'remainingAmount' => $doc->getRemainingAmount(),
            'isFullyPaid' => $doc->isFullyPaid(),
            'isOverdue' => $doc->isOverdue(),
            'daysOverdue' => $doc->getDaysOverdue(),
            'createdAt' => $doc->getCreatedAt()?->format('c'),
        ];
    }

    /**
     * Liste des documents avec filtrage et pagination.
     */
    #[Route('/api/documents', name: 'api_documents_list', methods: ['GET'])]
    public function list(Request $request, DocumentRepository $documentRepository): JsonResponse
    {
        $page = max(1, $request->query->getInt('page', 1));
        $type = $request->query->get('type');
        $status = $request->query->get('status');
        $search = $request->query->get('search', '');
        $limit = 20;
        $offset = ($page - 1) * $limit;

        $criteria = [];
        if ($type) $criteria['type'] = $type;
        if ($status) $criteria['status'] = $status;

        $documents = $documentRepository->findBy($criteria, ['createdAt' => 'DESC'], $limit, $offset);
        $total = $documentRepository->count($criteria);

        $data = array_map(fn(Document $doc) => $this->serializeDocumentListItem($doc), $documents);

        return $this->json([
            'data' => $data,
            'total' => $total,
            'page' => $page,
            'totalPages' => (int) ceil($total / $limit),
        ]);
    }

    /**
     * Obtenir un document spécifique (détail complet).
     */
    #[Route('/api/documents/{id}', name: 'api_documents_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(int $id, DocumentRepository $documentRepository): JsonResponse
    {
        $document = $documentRepository->find($id);

        if (!$document) {
            return $this->json(['message' => 'Document non trouvé.'], 404);
        }

        return $this->json($this->serializeDocument($document));
    }

    /**
     * Créer un document.
     */
    #[Route('/api/documents', name: 'api_documents_create', methods: ['POST'])]
    public function create(
        Request $request,
        ClientRepository $clientRepository,
        DocumentRepository $documentRepository,
        EntityManagerInterface $em,
        ValidatorInterface $validator
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        $document = new Document();
        $document->setType($data['type'] ?? 'quote');
        $document->setStatus($data['status'] ?? Document::STATUS_DRAFT);
        $document->setLocation($data['location'] ?? null);
        $document->setTaxRate($data['taxRate'] ?? '16.00');
        $document->setCurrency($data['currency'] ?? 'MRU');
        $document->setPaymentTerms($data['paymentTerms'] ?? 30);

        if (isset($data['date'])) {
            $document->setDate(new \DateTime($data['date']));
        }

        // Associer au client
        if (isset($data['clientId'])) {
            $client = $clientRepository->find($data['clientId']);
            if (!$client) {
                return $this->json(['message' => 'Client non trouvé.'], 404);
            }
            $document->setClient($client);
        }

        // Associer à l'entreprise
        $user = $this->getUser();
        if ($user->getCompany()) {
            $document->setCompany($user->getCompany());
        }

        // Générer le numéro
        $prefix = $document->getType() === 'quote' ? 'DEV' : 'FAC';
        $count = $documentRepository->countByType($document->getType()) + 1;
        $document->setNumber($prefix . '-' . date('Y') . '-' . str_pad($count, 4, '0', STR_PAD_LEFT));

        // Ajouter les items
        if (isset($data['items']) && is_array($data['items'])) {
            foreach ($data['items'] as $itemData) {
                $item = new DocumentItem();
                $item->setDesignation($itemData['designation'] ?? '');
                $item->setNumberOfDays($itemData['numberOfDays'] ?? 1);
                $item->setNumberOfPersons($itemData['numberOfPersons'] ?? 1);
                $item->setNumberOfServices($itemData['numberOfServices'] ?? 1);
                $item->setUnitPrice($itemData['unitPrice'] ?? '0.00');
                $item->setPosition($itemData['position'] ?? 0);
                $item->calculateTotal();
                $document->addItem($item);
            }
        }

        $document->calculateTotals();
        $document->calculateDueDate();

        $errors = $validator->validate($document);
        if (count($errors) > 0) {
            $messages = [];
            foreach ($errors as $error) {
                $messages[$error->getPropertyPath()] = $error->getMessage();
            }
            return $this->json(['errors' => $messages], 422);
        }

        $em->persist($document);
        $em->flush();

        return $this->json($this->serializeDocument($document), 201);
    }

    /**
     * Mettre à jour un document.
     */
    #[Route('/api/documents/{id}', name: 'api_documents_update', methods: ['PUT'], requirements: ['id' => '\d+'])]
    public function update(
        int $id,
        Request $request,
        DocumentRepository $documentRepository,
        EntityManagerInterface $em,
        ValidatorInterface $validator
    ): JsonResponse {
        $document = $documentRepository->find($id);

        if (!$document) {
            return $this->json(['message' => 'Document non trouvé.'], 404);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['status'])) $document->setStatus($data['status']);
        if (isset($data['location'])) $document->setLocation($data['location']);
        if (isset($data['taxRate'])) $document->setTaxRate($data['taxRate']);
        if (isset($data['currency'])) $document->setCurrency($data['currency']);
        if (isset($data['paymentTerms'])) $document->setPaymentTerms($data['paymentTerms']);
        if (isset($data['date'])) $document->setDate(new \DateTime($data['date']));

        $document->calculateTotals();
        $document->calculateDueDate();

        $errors = $validator->validate($document);
        if (count($errors) > 0) {
            $messages = [];
            foreach ($errors as $error) {
                $messages[$error->getPropertyPath()] = $error->getMessage();
            }
            return $this->json(['errors' => $messages], 422);
        }

        $em->flush();

        return $this->json($this->serializeDocument($document));
    }

    /**
     * Convertir un devis en facture.
     */
    #[Route('/api/documents/{id}/convert', name: 'api_documents_convert', methods: ['POST'])]
    public function convert(
        int $id,
        DocumentRepository $documentRepository,
        EntityManagerInterface $em
    ): JsonResponse {
        $quote = $documentRepository->find($id);

        if (!$quote) {
            return $this->json(['message' => 'Document non trouvé.'], 404);
        }

        if ($quote->getType() !== Document::TYPE_QUOTE) {
            return $this->json(['message' => 'Seuls les devis peuvent être convertis en factures.'], 400);
        }

        // Créer la facture à partir du devis
        $invoice = new Document();
        $invoice->setType(Document::TYPE_INVOICE);
        $invoice->setClient($quote->getClient());
        $invoice->setCompany($quote->getCompany());
        $invoice->setDate(new \DateTime());
        $invoice->setLocation($quote->getLocation());
        $invoice->setTaxRate($quote->getTaxRate());
        $invoice->setCurrency($quote->getCurrency());
        $invoice->setPaymentTerms($quote->getPaymentTerms());
        $invoice->setStatus(Document::STATUS_SENT);

        // Générer le numéro
        $count = $documentRepository->countByType('invoice') + 1;
        $invoice->setNumber('FAC-' . date('Y') . '-' . str_pad($count, 4, '0', STR_PAD_LEFT));

        // Copier les items
        foreach ($quote->getItems() as $quoteItem) {
            $item = new DocumentItem();
            $item->setDesignation($quoteItem->getDesignation());
            $item->setNumberOfDays($quoteItem->getNumberOfDays());
            $item->setNumberOfPersons($quoteItem->getNumberOfPersons());
            $item->setNumberOfServices($quoteItem->getNumberOfServices());
            $item->setUnitPrice($quoteItem->getUnitPrice());
            $item->setPosition($quoteItem->getPosition());
            $item->calculateTotal();
            $invoice->addItem($item);
        }

        $invoice->calculateTotals();
        $invoice->calculateDueDate();

        $em->persist($invoice);
        $em->flush();

        return $this->json($this->serializeDocument($invoice), 201);
    }

    /**
     * Télécharger le PDF d'un document.
     */
    #[Route('/api/documents/{id}/pdf', name: 'api_documents_pdf', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function downloadPdf(
        int $id,
        DocumentRepository $documentRepository,
        PdfGeneratorService $pdfGenerator
    ): Response {
        $document = $documentRepository->find($id);

        if (!$document) {
            return $this->json(['message' => 'Document non trouvé.'], 404);
        }

        try {
            $pdfContent = $pdfGenerator->generateDocumentPdf($document);

            $filename = $document->getNumber() . '.pdf';

            return new Response($pdfContent, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                'Content-Length' => strlen($pdfContent),
            ]);
        } catch (\Exception $e) {
            return $this->json(['message' => 'Erreur lors de la génération du PDF : ' . $e->getMessage()], 500);
        }
    }

    /**
     * Envoyer un document par email.
     */
    #[Route('/api/documents/{id}/send', name: 'api_documents_send', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function sendByEmail(
        int $id,
        Request $request,
        DocumentRepository $documentRepository,
        EmailService $emailService
    ): JsonResponse {
        $document = $documentRepository->find($id);

        if (!$document) {
            return $this->json(['message' => 'Document non trouvé.'], 404);
        }

        $data = json_decode($request->getContent(), true);
        $email = $data['email'] ?? $document->getClient()?->getEmail();
        $message = $data['message'] ?? null;

        if (!$email) {
            return $this->json(['message' => 'Aucune adresse email spécifiée et le client n\'a pas d\'email.'], 422);
        }

        try {
            $emailService->sendDocumentByEmail($document, $email, $message);

            return $this->json([
                'message' => 'Document envoyé avec succès à ' . $email,
                'email' => $email,
            ]);
        } catch (\Exception $e) {
            return $this->json(['message' => 'Erreur lors de l\'envoi : ' . $e->getMessage()], 500);
        }
    }

    /**
     * Supprimer un document (ROLE_ADMIN uniquement).
     */
    #[Route('/api/documents/{id}', name: 'api_documents_delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function delete(
        int $id,
        DocumentRepository $documentRepository,
        EntityManagerInterface $em
    ): JsonResponse {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $document = $documentRepository->find($id);

        if (!$document) {
            return $this->json(['message' => 'Document non trouvé.'], 404);
        }

        $em->remove($document);
        $em->flush();

        return $this->json(['message' => 'Document supprimé avec succès.']);
    }
}
