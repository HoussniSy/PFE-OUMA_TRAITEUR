<?php

namespace App\Repository;

use App\Entity\Document;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Document>
 */
class DocumentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Document::class);
    }

    /**
     * Génère le prochain numéro de document pour l'année en cours
     * Format: DEV-001/2025 ou FAC-001/2025
     */
    public function generateNumber(string $type): string
    {
        $year = date('Y');
        $prefix = $type === Document::TYPE_QUOTE ? 'DEV' : 'FAC';

        $lastDocument = $this->createQueryBuilder('d')
            ->where('d.type = :type')
            ->andWhere('YEAR(d.date) = :year')
            ->setParameter('type', $type)
            ->setParameter('year', $year)
            ->orderBy('d.id', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        $sequence = 1;
        if ($lastDocument) {
            $lastNumber = $lastDocument->getNumber();
            $parts = explode('/', $lastNumber);
            if (isset($parts[0])) {
                $sequence = (int) str_replace($prefix . '-', '', $parts[0]) + 1;
            }
        }

        return sprintf('%s-%03d/%s', $prefix, $sequence, $year);
    }

    /**
     * Trouve tous les documents avec filtres
     */
    public function findWithFilters(?string $type = null, ?string $status = null, ?string $search = null, ?int $limit = null, ?int $offset = null): array
    {
        $qb = $this->createQueryBuilder('d')
            ->leftJoin('d.client', 'c')
            ->addSelect('c');

        if ($type) {
            $qb->andWhere('d.type = :type')
                ->setParameter('type', $type);
        }

        if ($status) {
            $qb->andWhere('d.status = :status')
                ->setParameter('status', $status);
        }

        if ($search) {
            $qb->andWhere('d.number LIKE :search OR c.name LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        if ($limit) {
            $qb->setMaxResults($limit);
        }

        if ($offset) {
            $qb->setFirstResult($offset);
        }

        return $qb->orderBy('d.date', 'DESC')
            ->addOrderBy('d.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les N derniers documents créés
     */
    public function findRecentDocuments(int $limit = 10): array
    {
        return $this->createQueryBuilder('d')
            ->leftJoin('d.client', 'c')
            ->addSelect('c')
            ->orderBy('d.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les devis en attente (status = draft)
     */
    public function findPendingQuotes(int $limit = 5): array
    {
        return $this->createQueryBuilder('d')
            ->leftJoin('d.client', 'c')
            ->addSelect('c')
            ->where('d.type = :type')
            ->andWhere('d.status = :status')
            ->setParameter('type', Document::TYPE_QUOTE)
            ->setParameter('status', Document::STATUS_DRAFT)
            ->orderBy('d.date', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Compte les documents par type
     */
    public function countByType(string $type): int
    {
        return $this->createQueryBuilder('d')
            ->select('COUNT(d.id)')
            ->where('d.type = :type')
            ->setParameter('type', $type)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Calcule le CA mensuel pour une année donnée
     */
    public function getMonthlyRevenue(int $year): float
    {
        $month = date('m');

        $result = $this->createQueryBuilder('d')
            ->select('SUM(d.totalTtc)')
            ->where('d.type = :type')
            ->andWhere('YEAR(d.date) = :year')
            ->andWhere('MONTH(d.date) = :month')
            ->setParameter('type', Document::TYPE_INVOICE)
            ->setParameter('year', $year)
            ->setParameter('month', $month)
            ->getQuery()
            ->getSingleScalarResult();

        return (float) ($result ?? 0);
    }

    /**
     * Récupère les données mensuelles pour un graphique (6 derniers mois)
     */
    public function getMonthlyRevenueData(int $monthsBack = 6): array
    {
        $data = [];

        for ($i = $monthsBack - 1; $i >= 0; $i--) {
            $date = new \DateTime("-$i months");
            $year = (int) $date->format('Y');
            $month = (int) $date->format('m');
            $monthName = $date->format('M Y');

            $result = $this->createQueryBuilder('d')
                ->select('SUM(d.totalTtc)')
                ->where('d.type = :type')
                ->andWhere('YEAR(d.date) = :year')
                ->andWhere('MONTH(d.date) = :month')
                ->setParameter('type', Document::TYPE_INVOICE)
                ->setParameter('year', $year)
                ->setParameter('month', $month)
                ->getQuery()
                ->getSingleScalarResult();

            $data[] = [
                'month' => $monthName,
                'revenue' => (float) ($result ?? 0)
            ];
        }

        return $data;
    }

    /**
     * Récupère les ventes mensuelles avec détails pour une année
     */
    public function getMonthlySalesStats(?int $year = null): array
    {
        $year = $year ?? date('Y');

        return $this->createQueryBuilder('d')
            ->select('MONTH(d.date) as month', 'SUM(d.totalTtc) as revenue', 'COUNT(d.id) as count')
            ->where('d.type = :type')
            ->andWhere('YEAR(d.date) = :year')
            ->setParameter('type', Document::TYPE_INVOICE)
            ->setParameter('year', $year)
            ->groupBy('month')
            ->orderBy('month', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Calcule le taux de conversion devis -> factures
     */
    public function getConversionRate(?int $year = null): float
    {
        $year = $year ?? date('Y');

        $quotes = $this->createQueryBuilder('d')
            ->select('COUNT(d.id)')
            ->where('d.type = :type')
            ->andWhere('YEAR(d.date) = :year')
            ->setParameter('type', Document::TYPE_QUOTE)
            ->setParameter('year', $year)
            ->getQuery()
            ->getSingleScalarResult();

        $invoices = $this->createQueryBuilder('d')
            ->select('COUNT(d.id)')
            ->where('d.type = :type')
            ->andWhere('YEAR(d.date) = :year')
            ->setParameter('type', Document::TYPE_INVOICE)
            ->setParameter('year', $year)
            ->getQuery()
            ->getSingleScalarResult();

        if ($quotes === 0) {
            return 0;
        }

        return ($invoices / $quotes) * 100;
    }

    /**
     * Trouve les documents par client
     */
    public function findByClient(int $clientId, ?string $type = null): array
    {
        $qb = $this->createQueryBuilder('d')
            ->where('d.client = :clientId')
            ->setParameter('clientId', $clientId)
            ->orderBy('d.date', 'DESC');

        if ($type) {
            $qb->andWhere('d.type = :type')
                ->setParameter('type', $type);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Calcule le CA annuel
     */
    public function getYearlyRevenue(?int $year = null): float
    {
        $year = $year ?? date('Y');

        $result = $this->createQueryBuilder('d')
            ->select('SUM(d.totalTtc)')
            ->where('d.type = :type')
            ->andWhere('YEAR(d.date) = :year')
            ->setParameter('type', Document::TYPE_INVOICE)
            ->setParameter('year', $year)
            ->getQuery()
            ->getSingleScalarResult();

        return (float) ($result ?? 0);
    }

    /**
     * Recherche globale dans les documents (numéro, client)
     * Utilisé par la barre de recherche globale
     *
     * @return Document[]
     */
    public function searchGlobal(string $query, int $limit = 5): array
    {
        return $this->createQueryBuilder('d')
            ->leftJoin('d.client', 'c')
            ->addSelect('c')
            ->where('d.number LIKE :query')
            ->orWhere('c.name LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->orderBy('d.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
