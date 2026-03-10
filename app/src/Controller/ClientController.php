<?php

namespace App\Controller;

use App\Entity\Client;
use App\Entity\Document;
use App\Form\ClientType;
use App\Repository\ClientRepository;
use App\Repository\DocumentRepository;
use App\Service\FilterService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/client')]
#[IsGranted('ROLE_USER')]
class ClientController extends AbstractController
{
    public function __construct(
        private FilterService $filterService
    ) {}

    #[Route('', name: 'app_client_index', methods: ['GET'])]
    public function index(Request $request, ClientRepository $clientRepository): Response
    {
        // ==========================================
        // FILTRES AVANCÉS - DÉBUT
        // ==========================================

        // Extraire les filtres depuis la requête
        $filters = $this->filterService->extractFiltersFromRequest($request);

        // Valider les filtres
        $validationErrors = $this->filterService->validateFilters($filters);

        // Requête de base
        $qb = $clientRepository->createQueryBuilder('c')
            ->orderBy('c.name', 'ASC');

        // Recherche par nom, email ou téléphone
        $search = $request->query->get('search', '');
        if ($search) {
            $qb->where('c.name LIKE :search')
                ->orWhere('c.email LIKE :search')
                ->orWhere('c.phone LIKE :search')
                ->setParameter('search', '%' . $search . '%');
            $filters['search'] = $search;
        }

        // Filtres CA (nécessite une jointure avec documents)
        $needsDocumentJoin = isset($filters['ca_min']) || isset($filters['ca_max']);

        if ($needsDocumentJoin) {
            $qb->leftJoin('c.documents', 'd')
                ->addSelect('SUM(CASE WHEN d.type = :invoice AND d.status != :cancelled THEN d.totalTtc ELSE 0 END) as HIDDEN total_ca')
                ->setParameter('invoice', Document::TYPE_INVOICE)
                ->setParameter('cancelled', Document::STATUS_CANCELLED)
                ->groupBy('c.id');

            if (isset($filters['ca_min']) && $filters['ca_min'] !== '') {
                $qb->having('SUM(CASE WHEN d.type = :invoice AND d.status != :cancelled THEN d.totalTtc ELSE 0 END) >= :caMin')
                    ->setParameter('caMin', $filters['ca_min']);
            }

            if (isset($filters['ca_max']) && $filters['ca_max'] !== '') {
                if (isset($filters['ca_min'])) {
                    $qb->andHaving('SUM(CASE WHEN d.type = :invoice AND d.status != :cancelled THEN d.totalTtc ELSE 0 END) <= :caMax');
                } else {
                    $qb->having('SUM(CASE WHEN d.type = :invoice AND d.status != :cancelled THEN d.totalTtc ELSE 0 END) <= :caMax');
                }
                $qb->setParameter('caMax', $filters['ca_max']);
            }
        }

        // Période d'inscription
        if (isset($filters['created_from']) && $filters['created_from'] !== '') {
            $qb->andWhere('c.createdAt >= :createdFrom')
                ->setParameter('createdFrom', new \DateTime($filters['created_from']));
        }

        if (isset($filters['created_to']) && $filters['created_to'] !== '') {
            $qb->andWhere('c.createdAt <= :createdTo')
                ->setParameter('createdTo', new \DateTime($filters['created_to'] . ' 23:59:59'));
        }

        // Exécuter la requête
        $clients = $qb->getQuery()->getResult();

        // Statistiques clients
        $totalClients = $clientRepository->countTotal();
        $newThisMonth = $clientRepository->countNewThisMonth();

        // Compteurs de filtres actifs
        $activeFiltersCount = $this->filterService->countActiveFilters($filters);
        $filterSummary = $this->filterService->getFilterSummary($filters);

        // ==========================================
        // FILTRES AVANCÉS - FIN
        // ==========================================

        return $this->render('client/index.html.twig', [
            'clients' => $clients,
            'search' => $search,
            'filters' => $filters,
            'totalClients' => $totalClients,
            'newThisMonth' => $newThisMonth,
            'activeFiltersCount' => $activeFiltersCount,
            'filterSummary' => $filterSummary,
            'validationErrors' => $validationErrors,
        ]);
    }

    #[Route('/new', name: 'app_client_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $client = new Client();
        $form = $this->createForm(ClientType::class, $client);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $entityManager->persist($client);
                $entityManager->flush();

                $this->addFlash('success', 'Le client a été créé avec succès.');
                return $this->redirectToRoute('app_client_show', ['id' => $client->getId()]);
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de la création du client : ' . $e->getMessage());
            }
        }

        return $this->render('client/new.html.twig', [
            'client' => $client,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_client_show', methods: ['GET'])]
    public function show(Client $client, DocumentRepository $documentRepository): Response
    {
        // Historique des documents du client
        $documents = $documentRepository->findByClient($client->getId());

        // Calcul du CA total du client
        $totalRevenue = 0;
        foreach ($documents as $document) {
            if ($document->getType() === 'invoice') {
                $totalRevenue += (float) $document->getTotalTtc();
            }
        }

        return $this->render('client/show.html.twig', [
            'client' => $client,
            'documents' => $documents,
            'totalRevenue' => $totalRevenue,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_client_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Client $client, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ClientType::class, $client);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $entityManager->flush();

                $this->addFlash('success', 'Le client a été mis à jour avec succès.');
                return $this->redirectToRoute('app_client_show', ['id' => $client->getId()]);
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de la mise à jour : ' . $e->getMessage());
            }
        }

        return $this->render('client/edit.html.twig', [
            'client' => $client,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_client_delete', methods: ['POST'])]
    #[IsGranted('ROLE_COMPTABLE')]
    public function delete(Request $request, Client $client, EntityManagerInterface $entityManager): Response
    {
        // Vérification du token CSRF
        if ($this->isCsrfTokenValid('delete' . $client->getId(), $request->request->get('_token'))) {
            try {
                // Vérifier s'il y a des documents liés
                if ($client->getDocuments()->count() > 0) {
                    $this->addFlash('error', 'Impossible de supprimer ce client car il a des documents associés.');
                    return $this->redirectToRoute('app_client_show', ['id' => $client->getId()]);
                }

                $entityManager->remove($client);
                $entityManager->flush();

                $this->addFlash('success', 'Le client a été supprimé avec succès.');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de la suppression : ' . $e->getMessage());
            }
        }

        return $this->redirectToRoute('app_client_index');
    }
}
