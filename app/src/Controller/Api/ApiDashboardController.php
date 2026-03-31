<?php

namespace App\Controller\Api;

use App\Repository\ClientRepository;
use App\Repository\DocumentRepository;
use App\Repository\StockItemRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class ApiDashboardController extends AbstractController
{
    /**
     * Retourne les statistiques du tableau de bord.
     */
    #[Route('/api/dashboard', name: 'api_dashboard', methods: ['GET'])]
    public function index(
        DocumentRepository $documentRepository,
        ClientRepository $clientRepository,
        StockItemRepository $stockItemRepository
    ): JsonResponse {
        $totalClients = $clientRepository->count([]);
        $totalQuotes = $documentRepository->countByType('quote');
        $totalInvoices = $documentRepository->countByType('invoice');
        $monthlyRevenue = $documentRepository->getMonthlyRevenue((int) date('Y'));
        $recentDocuments = $documentRepository->findRecentDocuments(10);
        $pendingQuotes = $documentRepository->findPendingQuotes(5);
        $monthlyData = $documentRepository->getMonthlyRevenueData(6);

        // Sérialisation des documents récents
        $recentDocs = array_map(fn($doc) => [
            'id' => $doc->getId(),
            'type' => $doc->getType(),
            'number' => $doc->getNumber(),
            'status' => $doc->getStatus(),
            'totalTtc' => $doc->getTotalTtc(),
            'currency' => $doc->getCurrency(),
            'date' => $doc->getDate()?->format('Y-m-d'),
            'client' => $doc->getClient() ? [
                'id' => $doc->getClient()->getId(),
                'name' => $doc->getClient()->getName(),
            ] : null,
        ], $recentDocuments);

        $pending = array_map(fn($doc) => [
            'id' => $doc->getId(),
            'number' => $doc->getNumber(),
            'totalTtc' => $doc->getTotalTtc(),
            'currency' => $doc->getCurrency(),
            'date' => $doc->getDate()?->format('Y-m-d'),
            'client' => $doc->getClient() ? [
                'id' => $doc->getClient()->getId(),
                'name' => $doc->getClient()->getName(),
            ] : null,
        ], $pendingQuotes);

        return $this->json([
            'totalClients' => $totalClients,
            'totalQuotes' => $totalQuotes,
            'totalInvoices' => $totalInvoices,
            'monthlyRevenue' => $monthlyRevenue,
            'recentDocuments' => $recentDocs,
            'pendingQuotes' => $pending,
            'monthlyData' => $monthlyData,
        ]);
    }
}
