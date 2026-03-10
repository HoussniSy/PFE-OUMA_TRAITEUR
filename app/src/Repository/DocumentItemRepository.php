<?php

namespace App\Repository;

use App\Entity\DocumentItem;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DocumentItem>
 */
class DocumentItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DocumentItem::class);
    }

    /**
     * Trouve les services les plus vendus (Top services)
     */
    public function findTopServices(int $limit = 10, ?int $year = null): array
    {
        $qb = $this->createQueryBuilder('di')
            ->select('di.designation', 'SUM(di.totalAmount) as totalAmount', 'COUNT(di.id) as count')
            ->leftJoin('di.document', 'd')
            ->where('d.type = :type')
            ->setParameter('type', 'invoice')
            ->groupBy('di.designation')
            ->orderBy('totalAmount', 'DESC')
            ->setMaxResults($limit);

        if ($year) {
            $qb->andWhere('YEAR(d.date) = :year')
               ->setParameter('year', $year);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Trouve les services les plus populaires
     * Alias pour findTopServices
     */
    public function findMostPopularServices(int $limit = 10, ?int $year = null): array
    {
        return $this->findTopServices($limit, $year);
    }

    /**
     * Calcule le chiffre d'affaires par service
     */
    public function getRevenueByService(?int $year = null): array
    {
        $qb = $this->createQueryBuilder('di')
            ->select('di.designation', 'SUM(di.totalAmount) as revenue')
            ->leftJoin('di.document', 'd')
            ->where('d.type = :type')
            ->setParameter('type', 'invoice')
            ->groupBy('di.designation')
            ->orderBy('revenue', 'DESC');

        if ($year) {
            $qb->andWhere('YEAR(d.date) = :year')
               ->setParameter('year', $year);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Trouve les items d'un document spécifique
     */
    public function findByDocument(int $documentId): array
    {
        return $this->createQueryBuilder('di')
            ->where('di.document = :documentId')
            ->setParameter('documentId', $documentId)
            ->orderBy('di.position', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Compte le nombre total de services vendus
     */
    public function countTotalServices(?int $year = null): int
    {
        $qb = $this->createQueryBuilder('di')
            ->select('COUNT(di.id)')
            ->leftJoin('di.document', 'd')
            ->where('d.type = :type')
            ->setParameter('type', 'invoice');

        if ($year) {
            $qb->andWhere('YEAR(d.date) = :year')
               ->setParameter('year', $year);
        }

        return $qb->getQuery()->getSingleScalarResult();
    }
}
