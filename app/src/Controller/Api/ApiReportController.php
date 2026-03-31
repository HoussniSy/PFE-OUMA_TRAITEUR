<?php

namespace App\Controller\Api;

use App\Repository\DocumentRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class ApiReportController extends AbstractController
{
    /**
     * Obtenir le résumé pour les rapports / chiffres d'affaires.
     */
    #[Route('/api/reports/summary', name: 'api_reports_summary', methods: ['GET'])]
    public function summary(
        Request $request,
        DocumentRepository $documentRepository
    ): JsonResponse {
        $year = $request->query->getInt('year', (int) date('Y'));

        $totalQuotes = $documentRepository->countByType('quote');
        $totalInvoices = $documentRepository->countByType('invoice');
        $monthlyRevenue = $documentRepository->getMonthlyRevenue($year);
        $monthlyData = $documentRepository->getMonthlyRevenueData(12);

        return $this->json([
            'year' => $year,
            'totalQuotes' => $totalQuotes,
            'totalInvoices' => $totalInvoices,
            'monthlyRevenue' => $monthlyRevenue,
            'monthlyData' => $monthlyData,
        ]);
    }
}
