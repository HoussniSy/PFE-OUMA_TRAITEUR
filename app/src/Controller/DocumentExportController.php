<?php

namespace App\Controller;

use App\Repository\DocumentRepository;
use App\Service\ExportService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/document/export')]
#[IsGranted('ROLE_USER')]
class DocumentExportController extends AbstractController
{
    public function __construct(
        private DocumentRepository $documentRepository,
        private ExportService $exportService,
    ) {
    }

    /**
     * Exporte la liste des documents en Excel
     */
    #[Route('/excel', name: 'app_document_export_excel', methods: ['GET'])]
    public function exportExcel(Request $request): Response
    {
        // Récupérer les filtres depuis la requête
        $type = $request->query->get('type', 'all');
        $status = $request->query->get('status', 'all');
        $search = $request->query->get('search', '');

        // Récupérer les documents avec filtres
        $documents = $this->getFilteredDocuments($type, $status, $search);

        // Préparer les données
        $headers = [
            'Type',
            'Numéro',
            'Date',
            'Client',
            'Lieu',
            'Montant HT',
            'TVA (16%)',
            'Montant TTC',
            'Devise',
            'Statut',
            'Créé le',
        ];

        $data = [];
        $totalHt = 0;
        $totalTva = 0;
        $totalTtc = 0;

        foreach ($documents as $document) {
            $tva = $document->getTotalHt() * ($document->getTaxRate() / 100);

            $data[] = [
                $document->getType() === 'quote' ? 'Devis' : 'Facture',
                $document->getNumber(),
                $this->exportService->formatDate($document->getDate()),
                $document->getClient()->getName(),
                $document->getLocation() ?? '-',
                $this->exportService->formatCurrency($document->getTotalHt(), ''),
                $this->exportService->formatCurrency($tva, ''),
                $this->exportService->formatCurrency($document->getTotalTtc(), ''),
                $document->getCurrency(),
                $this->getStatusLabel($document->getStatus()),
                $this->exportService->formatDate($document->getCreatedAt(), 'd/m/Y H:i'),
            ];

            $totalHt += $document->getTotalHt();
            $totalTva += $tva;
            $totalTtc += $document->getTotalTtc();
        }

        // Ligne de totaux
        $totals = [
            '',
            '',
            '',
            '',
            'TOTAL :',
            $this->exportService->formatCurrency($totalHt, ''),
            $this->exportService->formatCurrency($totalTva, ''),
            $this->exportService->formatCurrency($totalTtc, ''),
            'MRU',
            '',
            '',
        ];

        // Générer le nom du fichier
        $filename = $this->exportService->generateFilename('documents');

        return $this->exportService->export($data, $headers, $filename, 'xlsx', $totals);
    }

    /**
     * Exporte la liste des documents en CSV
     */
    #[Route('/csv', name: 'app_document_export_csv', methods: ['GET'])]
    public function exportCsv(Request $request): Response
    {
        // Récupérer les filtres
        $type = $request->query->get('type', 'all');
        $status = $request->query->get('status', 'all');
        $search = $request->query->get('search', '');

        $documents = $this->getFilteredDocuments($type, $status, $search);

        $headers = [
            'Type',
            'Numéro',
            'Date',
            'Client',
            'Lieu',
            'Montant HT',
            'TVA (16%)',
            'Montant TTC',
            'Devise',
            'Statut',
            'Créé le',
        ];

        $data = [];
        foreach ($documents as $document) {
            $tva = $document->getTotalHt() * ($document->getTaxRate() / 100);

            $data[] = [
                $document->getType() === 'quote' ? 'Devis' : 'Facture',
                $document->getNumber(),
                $this->exportService->formatDate($document->getDate()),
                $document->getClient()->getName(),
                $document->getLocation() ?? '-',
                $document->getTotalHt(),
                $tva,
                $document->getTotalTtc(),
                $document->getCurrency(),
                $this->getStatusLabel($document->getStatus()),
                $this->exportService->formatDate($document->getCreatedAt(), 'd/m/Y H:i'),
            ];
        }

        $filename = $this->exportService->generateFilename('documents');

        return $this->exportService->export($data, $headers, $filename, 'csv');
    }

    /**
     * Exporte un document spécifique avec le détail des prestations
     */
    #[Route('/{id}/detail/excel', name: 'app_document_export_detail_excel', methods: ['GET'])]
    public function exportDocumentDetail(int $id): Response
    {
        $document = $this->documentRepository->find($id);

        if (!$document) {
            throw $this->createNotFoundException('Document non trouvé');
        }

        // Préparer les données
        $headers = [
            'Désignation',
            'Nb jours',
            'Nb personnes',
            'Nb services',
            'Prix unitaire',
            'Montant total',
        ];

        $data = [];
        foreach ($document->getItems() as $item) {
            $data[] = [
                $item->getDesignation(),
                $item->getNumberOfDays(),
                $item->getNumberOfPersons(),
                $item->getNumberOfServices(),
                $this->exportService->formatCurrency($item->getUnitPrice(), ''),
                $this->exportService->formatCurrency($item->getTotalAmount(), ''),
            ];
        }

        // Totaux
        $tva = $document->getTotalHt() * ($document->getTaxRate() / 100);
        $totals = [
            '',
            '',
            '',
            '',
            'Total HT :',
            $this->exportService->formatCurrency($document->getTotalHt(), ''),
        ];

        $filename = $this->exportService->generateFilename($document->getNumber() . '_detail');

        return $this->exportService->export($data, $headers, $filename, 'xlsx', $totals);
    }

    /**
     * Récupère les documents filtrés
     */
    private function getFilteredDocuments(string $type, string $status, string $search): array
    {
        $qb = $this->documentRepository->createQueryBuilder('d')
            ->leftJoin('d.client', 'c')
            ->orderBy('d.createdAt', 'DESC');

        // Filtre par type
        if ($type !== 'all') {
            $qb->andWhere('d.type = :type')
                ->setParameter('type', $type);
        }

        // Filtre par statut
        if ($status !== 'all') {
            $qb->andWhere('d.status = :status')
                ->setParameter('status', $status);
        }

        // Filtre de recherche
        if ($search) {
            $qb->andWhere('d.number LIKE :search OR c.name LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        return $qb->getQuery()->getResult();
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
