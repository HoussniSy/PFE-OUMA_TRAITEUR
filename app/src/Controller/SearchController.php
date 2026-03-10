<?php

namespace App\Controller;

use App\Repository\ClientRepository;
use App\Repository\DocumentRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Contrôleur pour la recherche globale et les endpoints API internes
 */
#[IsGranted('ROLE_USER')]
class SearchController extends AbstractController
{
    /**
     * Recherche globale dans les documents et clients
     * Retourne les résultats en JSON pour la barre de recherche AJAX
     */
    #[Route('/search', name: 'app_search', methods: ['GET'])]
    public function search(
        Request $request,
        DocumentRepository $documentRepository,
        ClientRepository $clientRepository
    ): JsonResponse {
        $query = trim($request->query->get('q', ''));

        if (strlen($query) < 2) {
            return $this->json(['documents' => [], 'clients' => []]);
        }

        // Recherche dans les documents (numéro, client)
        $documents = $documentRepository->searchGlobal($query, 5);
        $documentResults = array_map(function ($doc) {
            return [
                'id' => $doc->getId(),
                'type' => $doc->getType(),
                'typeLabel' => $doc->getTypeLabel(),
                'number' => $doc->getNumber(),
                'client' => $doc->getClient()?->getName(),
                'totalTtc' => $doc->getFormattedTotalTtc(),
                'status' => $doc->getStatusLabel(),
                'statusClass' => $doc->getStatusBadgeClass(),
                'url' => $this->generateUrl('app_document_show', ['id' => $doc->getId()]),
            ];
        }, $documents);

        // Recherche dans les clients
        $clients = $clientRepository->search($query);
        $clientResults = array_map(function ($client) {
            return [
                'id' => $client->getId(),
                'name' => $client->getName(),
                'phone' => $client->getPhone(),
                'email' => $client->getEmail(),
                'url' => $this->generateUrl('app_client_show', ['id' => $client->getId()]),
            ];
        }, array_slice($clients, 0, 5));

        return $this->json([
            'documents' => $documentResults,
            'clients' => $clientResults,
        ]);
    }

    /**
     * API endpoint pour les statistiques du dashboard
     */
    #[Route('/api/dashboard/stats', name: 'api_dashboard_stats', methods: ['GET'])]
    public function dashboardStats(
        DocumentRepository $documentRepository,
        ClientRepository $clientRepository
    ): JsonResponse {
        $year = (int) date('Y');

        return $this->json([
            'totalClients' => $clientRepository->count([]),
            'totalQuotes' => $documentRepository->countByType('quote'),
            'totalInvoices' => $documentRepository->countByType('invoice'),
            'monthlyRevenue' => $documentRepository->getMonthlyRevenue($year),
            'yearlyRevenue' => $documentRepository->getYearlyRevenue($year),
            'monthlyData' => $documentRepository->getMonthlyRevenueData(6),
        ]);
    }
}
