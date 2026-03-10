<?php

namespace App\Controller;

use App\Repository\ClientRepository;
use App\Repository\DocumentItemRepository;
use App\Repository\DocumentRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_COMPTABLE')]
class ReportController extends AbstractController
{
    #[Route('/reports', name: 'app_reports_index', methods: ['GET'])]
    public function index(
        Request $request,
        DocumentRepository $documentRepository,
        ClientRepository $clientRepository,
        DocumentItemRepository $documentItemRepository
    ): Response {
        $year = (int) $request->query->get('year', date('Y'));

        // Statistiques annuelles
        $yearlyRevenue = $documentRepository->getYearlyRevenue($year);
        $totalInvoices = $documentRepository->countByType('invoice');
        $conversionRate = $documentRepository->getConversionRate($year);

        // Ventes mensuelles pour le graphique
        $monthlySales = $documentRepository->getMonthlySalesStats($year);

        // Top 10 clients par CA
        $topClients = $clientRepository->findTopClientsByRevenue(10, $year);

        // Top 10 services populaires
        $topServices = $documentItemRepository->findTopServices(10, $year);

        // Calcul du panier moyen
        $averageBasket = $totalInvoices > 0 ? $yearlyRevenue / $totalInvoices : 0;

        return $this->render('reports/index.html.twig', [
            'year' => $year,
            'yearlyRevenue' => $yearlyRevenue,
            'totalInvoices' => $totalInvoices,
            'conversionRate' => $conversionRate,
            'averageBasket' => $averageBasket,
            'monthlySales' => $monthlySales,
            'topClients' => $topClients,
            'topServices' => $topServices,
        ]);
    }
}
