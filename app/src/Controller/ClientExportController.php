<?php

namespace App\Controller;

use App\Repository\ClientRepository;
use App\Service\ExportService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/client/export')]
#[IsGranted('ROLE_USER')]
class ClientExportController extends AbstractController
{
    public function __construct(
        private ClientRepository $clientRepository,
        private ExportService $exportService,
    ) {
    }

    /**
     * Exporte la liste des clients en Excel
     */
    #[Route('/excel', name: 'app_client_export_excel', methods: ['GET'])]
    public function exportExcel(Request $request): Response
    {
        $search = $request->query->get('search', '');
        $clients = $this->getFilteredClients($search);

        $headers = [
            'Nom',
            'Adresse',
            'Téléphone',
            'Email',
            'Nombre de documents',
            'Chiffre d\'affaires total',
            'Date de création',
        ];

        $data = [];
        $totalCa = 0;
        $totalDocuments = 0;

        foreach ($clients as $client) {
            $ca = $this->calculateClientRevenue($client);
            $nbDocuments = $client->getDocuments()->count();

            $data[] = [
                $client->getName(),
                $client->getAddress() ?? '-',
                $client->getPhone() ?? '-',
                $client->getEmail() ?? '-',
                $nbDocuments,
                $this->exportService->formatCurrency($ca, ''),
                $this->exportService->formatDate($client->getCreatedAt()),
            ];

            $totalCa += $ca;
            $totalDocuments += $nbDocuments;
        }

        // Ligne de totaux
        $totals = [
            'TOTAL (' . count($clients) . ' clients)',
            '',
            '',
            '',
            $totalDocuments,
            $this->exportService->formatCurrency($totalCa, ''),
            '',
        ];

        $filename = $this->exportService->generateFilename('clients');

        return $this->exportService->export($data, $headers, $filename, 'xlsx', $totals);
    }

    /**
     * Exporte la liste des clients en CSV
     */
    #[Route('/csv', name: 'app_client_export_csv', methods: ['GET'])]
    public function exportCsv(Request $request): Response
    {
        $search = $request->query->get('search', '');
        $clients = $this->getFilteredClients($search);

        $headers = [
            'Nom',
            'Adresse',
            'Téléphone',
            'Email',
            'Nombre de documents',
            'Chiffre d\'affaires total',
            'Date de création',
        ];

        $data = [];
        foreach ($clients as $client) {
            $ca = $this->calculateClientRevenue($client);

            $data[] = [
                $client->getName(),
                $client->getAddress() ?? '-',
                $client->getPhone() ?? '-',
                $client->getEmail() ?? '-',
                $client->getDocuments()->count(),
                $ca,
                $this->exportService->formatDate($client->getCreatedAt()),
            ];
        }

        $filename = $this->exportService->generateFilename('clients');

        return $this->exportService->export($data, $headers, $filename, 'csv');
    }

    /**
     * Exporte l'historique d'un client spécifique
     */
    #[Route('/{id}/history/excel', name: 'app_client_export_history_excel', methods: ['GET'])]
    public function exportClientHistory(int $id): Response
    {
        $client = $this->clientRepository->find($id);

        if (!$client) {
            throw $this->createNotFoundException('Client non trouvé');
        }

        $headers = [
            'Type',
            'Numéro',
            'Date',
            'Lieu',
            'Montant HT',
            'Montant TTC',
            'Statut',
        ];

        $data = [];
        $totalHt = 0;
        $totalTtc = 0;

        foreach ($client->getDocuments() as $document) {
            $data[] = [
                $document->getType() === 'quote' ? 'Devis' : 'Facture',
                $document->getNumber(),
                $this->exportService->formatDate($document->getDate()),
                $document->getLocation() ?? '-',
                $this->exportService->formatCurrency($document->getTotalHt(), ''),
                $this->exportService->formatCurrency($document->getTotalTtc(), ''),
                $this->getStatusLabel($document->getStatus()),
            ];

            $totalHt += $document->getTotalHt();
            $totalTtc += $document->getTotalTtc();
        }

        $totals = [
            '',
            '',
            '',
            'TOTAL :',
            $this->exportService->formatCurrency($totalHt, ''),
            $this->exportService->formatCurrency($totalTtc, ''),
            '',
        ];

        $filename = $this->exportService->generateFilename('client_' . $client->getId() . '_historique');

        return $this->exportService->export($data, $headers, $filename, 'xlsx', $totals);
    }

    /**
     * Récupère les clients filtrés
     */
    private function getFilteredClients(string $search): array
    {
        $qb = $this->clientRepository->createQueryBuilder('c')
            ->orderBy('c.name', 'ASC');

        if ($search) {
            $qb->andWhere('c.name LIKE :search OR c.email LIKE :search OR c.phone LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Calcule le CA total d'un client
     */
    private function calculateClientRevenue($client): float
    {
        $total = 0;
        foreach ($client->getDocuments() as $document) {
            if ($document->getType() === 'invoice') {
                $total += $document->getTotalTtc();
            }
        }
        return $total;
    }

    /**
     * Retourne le libellé du statut
     */
    private function getStatusLabel(string $status): string
    {
        return match($status) {
            'draft' => 'Brouillon',
            'sent' => 'Envoyé',
            'paid' => 'Payé',
            'cancelled' => 'Annulé',
            default => $status,
        };
    }
}
