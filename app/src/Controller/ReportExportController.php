<?php

namespace App\Controller;

use App\Repository\DocumentRepository;
use App\Service\ExportService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/reports/export')]
#[IsGranted('ROLE_COMPTABLE')]
class ReportExportController extends AbstractController
{
    public function __construct(
        private DocumentRepository $documentRepository,
        private ExportService $exportService,
    ) {
    }

    /**
     * Exporte tous les rapports en Excel multi-onglets
     */
    #[Route('/excel', name: 'app_reports_export_excel', methods: ['GET'])]
    public function exportExcel(Request $request): Response
    {
        $year = $request->query->get('year', date('Y'));

        // Récupérer les données
        $monthlySales = $this->getMonthlySales($year);
        $topClients = $this->getTopClients($year);
        $topServices = $this->getTopServices($year);

        // Préparer les onglets
        $sheets = [];

        // Onglet 1 : Ventes mensuelles
        $sheets[] = [
            'name' => 'Ventes mensuelles ' . $year,
            'headers' => ['Mois', 'Chiffre d\'affaires', 'Nb Factures', 'Nb Devis', 'Taux conversion'],
            'data' => $this->prepareMonthlySalesData($monthlySales),
            'totals' => $this->calculateMonthlySalesTotals($monthlySales),
        ];

        // Onglet 2 : Top 10 Clients
        $sheets[] = [
            'name' => 'Top 10 Clients',
            'headers' => ['#', 'Client', 'Chiffre d\'affaires', 'Nb Documents'],
            'data' => $this->prepareTopClientsData($topClients),
            'totals' => $this->calculateTopClientsTotals($topClients),
        ];

        // Onglet 3 : Top 10 Services
        $sheets[] = [
            'name' => 'Top 10 Services',
            'headers' => ['#', 'Service', 'Chiffre d\'affaires', 'Quantité vendue'],
            'data' => $this->prepareTopServicesData($topServices),
            'totals' => $this->calculateTopServicesTotals($topServices),
        ];

        $filename = $this->exportService->generateFilename('rapports_' . $year);

        return $this->exportService->exportMultiSheet($sheets, $filename);
    }

    /**
     * Récupère les ventes mensuelles
     */
    private function getMonthlySales(int $year): array
    {
        $sales = [];

        for ($month = 1; $month <= 12; $month++) {
            $startDate = new \DateTime("$year-$month-01");
            $endDate = clone $startDate;
            $endDate->modify('last day of this month')->setTime(23, 59, 59);

            $documents = $this->documentRepository->createQueryBuilder('d')
                ->where('d.date >= :startDate')
                ->andWhere('d.date <= :endDate')
                ->setParameter('startDate', $startDate)
                ->setParameter('endDate', $endDate)
                ->getQuery()
                ->getResult();

            $ca = 0;
            $nbInvoices = 0;
            $nbQuotes = 0;

            foreach ($documents as $doc) {
                if ($doc->getType() === 'invoice') {
                    $ca += $doc->getTotalTtc();
                    $nbInvoices++;
                } else {
                    $nbQuotes++;
                }
            }

            $conversionRate = $nbQuotes > 0 ? ($nbInvoices / ($nbInvoices + $nbQuotes)) * 100 : 0;

            $sales[] = [
                'month' => $startDate->format('F Y'),
                'ca' => $ca,
                'nbInvoices' => $nbInvoices,
                'nbQuotes' => $nbQuotes,
                'conversionRate' => $conversionRate,
            ];
        }

        return $sales;
    }

    /**
     * Récupère le top 10 des clients
     */
    private function getTopClients(int $year): array
    {
        $startDate = new \DateTime("$year-01-01");
        $endDate = new \DateTime("$year-12-31 23:59:59");

        $results = $this->documentRepository->createQueryBuilder('d')
            ->select('c.name as clientName, SUM(d.totalTtc) as ca, COUNT(d.id) as nbDocs')
            ->leftJoin('d.client', 'c')
            ->where('d.type = :type')
            ->andWhere('d.date >= :startDate')
            ->andWhere('d.date <= :endDate')
            ->setParameter('type', 'invoice')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->groupBy('c.id')
            ->orderBy('ca', 'DESC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();

        return $results;
    }

    /**
     * Récupère le top 10 des services
     */
    private function getTopServices(int $year): array
    {
        $startDate = new \DateTime("$year-01-01");
        $endDate = new \DateTime("$year-12-31 23:59:59");

        $results = $this->documentRepository->createQueryBuilder('d')
            ->select('i.designation as service, SUM(i.totalAmount) as ca, SUM(i.numberOfServices) as quantity')
            ->leftJoin('d.items', 'i')
            ->where('d.type = :type')
            ->andWhere('d.date >= :startDate')
            ->andWhere('d.date <= :endDate')
            ->setParameter('type', 'invoice')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->groupBy('i.designation')
            ->orderBy('ca', 'DESC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();

        return $results;
    }

    /**
     * Prépare les données des ventes mensuelles pour l'export
     */
    private function prepareMonthlySalesData(array $sales): array
    {
        $data = [];
        foreach ($sales as $sale) {
            $data[] = [
                $sale['month'],
                $this->exportService->formatCurrency($sale['ca'], ''),
                $sale['nbInvoices'],
                $sale['nbQuotes'],
                number_format($sale['conversionRate'], 2) . '%',
            ];
        }
        return $data;
    }

    /**
     * Calcule les totaux des ventes mensuelles
     */
    private function calculateMonthlySalesTotals(array $sales): array
    {
        $totalCa = 0;
        $totalInvoices = 0;
        $totalQuotes = 0;

        foreach ($sales as $sale) {
            $totalCa += $sale['ca'];
            $totalInvoices += $sale['nbInvoices'];
            $totalQuotes += $sale['nbQuotes'];
        }

        $avgConversion = ($totalInvoices + $totalQuotes) > 0
            ? ($totalInvoices / ($totalInvoices + $totalQuotes)) * 100
            : 0;

        return [
            'TOTAL',
            $this->exportService->formatCurrency($totalCa, ''),
            $totalInvoices,
            $totalQuotes,
            number_format($avgConversion, 2) . '%',
        ];
    }

    /**
     * Prépare les données du top clients
     */
    private function prepareTopClientsData(array $clients): array
    {
        $data = [];
        $rank = 1;
        foreach ($clients as $client) {
            $data[] = [
                $rank++,
                $client['clientName'],
                $this->exportService->formatCurrency($client['ca'], ''),
                $client['nbDocs'],
            ];
        }
        return $data;
    }

    /**
     * Calcule les totaux du top clients
     */
    private function calculateTopClientsTotals(array $clients): array
    {
        $totalCa = 0;
        $totalDocs = 0;

        foreach ($clients as $client) {
            $totalCa += $client['ca'];
            $totalDocs += $client['nbDocs'];
        }

        return [
            '',
            'TOTAL',
            $this->exportService->formatCurrency($totalCa, ''),
            $totalDocs,
        ];
    }

    /**
     * Prépare les données du top services
     */
    private function prepareTopServicesData(array $services): array
    {
        $data = [];
        $rank = 1;
        foreach ($services as $service) {
            $data[] = [
                $rank++,
                $service['service'],
                $this->exportService->formatCurrency($service['ca'], ''),
                $service['quantity'],
            ];
        }
        return $data;
    }

    /**
     * Calcule les totaux du top services
     */
    private function calculateTopServicesTotals(array $services): array
    {
        $totalCa = 0;
        $totalQty = 0;

        foreach ($services as $service) {
            $totalCa += $service['ca'];
            $totalQty += $service['quantity'];
        }

        return [
            '',
            'TOTAL',
            $this->exportService->formatCurrency($totalCa, ''),
            $totalQty,
        ];
    }
}
