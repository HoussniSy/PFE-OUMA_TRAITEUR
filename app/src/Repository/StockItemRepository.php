<?php

namespace App\Repository;

use App\Entity\StockItem;
use App\Entity\Company;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<StockItem>
 */
class StockItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StockItem::class);
    }

    /**
     * Récupère les articles par entreprise
     */
    public function findByCompany(?Company $company): array
    {
        $qb = $this->createQueryBuilder('s')
            ->orderBy('s.name', 'ASC');

        if ($company) {
            $qb->andWhere('s.company = :company OR s.company IS NULL')
                ->setParameter('company', $company);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Récupère les articles en stock faible
     */
    public function findLowStock(?Company $company = null): array
    {
        $qb = $this->createQueryBuilder('s')
            ->where('s.currentQuantity <= s.minimumQuantity')
            ->orderBy('s.currentQuantity', 'ASC');

        if ($company) {
            $qb->andWhere('s.company = :company OR s.company IS NULL')
                ->setParameter('company', $company);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Compte les articles en stock faible
     */
    public function countLowStock(?Company $company = null): int
    {
        $qb = $this->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->where('s.currentQuantity <= s.minimumQuantity');

        if ($company) {
            $qb->andWhere('s.company = :company OR s.company IS NULL')
                ->setParameter('company', $company);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Alias pour findLowStock — utilisé par le ApiDashboardController
     */
    public function findLowStockByCompany(Company $company): array
    {
        return $this->findLowStock($company);
    }
}
