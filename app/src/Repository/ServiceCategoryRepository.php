<?php

namespace App\Repository;

use App\Entity\ServiceCategory;
use App\Entity\Company;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ServiceCategory>
 */
class ServiceCategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ServiceCategory::class);
    }

    /**
     * Récupère les catégories d'une entreprise
     */
    public function findByCompany(?Company $company): array
    {
        $qb = $this->createQueryBuilder('sc')
            ->orderBy('sc.name', 'ASC');

        if ($company) {
            $qb->andWhere('sc.company = :company OR sc.company IS NULL')
                ->setParameter('company', $company);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Récupère les catégories avec le nombre d'items associés
     */
    public function findWithItemCount(?Company $company = null): array
    {
        $qb = $this->createQueryBuilder('sc')
            ->select('sc', 'COUNT(di.id) as itemCount')
            ->leftJoin('sc.items', 'di')
            ->groupBy('sc.id')
            ->orderBy('sc.name', 'ASC');

        if ($company) {
            $qb->andWhere('sc.company = :company OR sc.company IS NULL')
                ->setParameter('company', $company);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Statistiques par catégorie pour les rapports
     */
    public function getCategoryStats(int $year, ?Company $company = null): array
    {
        $qb = $this->getEntityManager()->createQueryBuilder()
            ->select('sc.id, sc.name, sc.color, COUNT(di.id) as itemCount, SUM(di.totalAmount) as totalRevenue')
            ->from(ServiceCategory::class, 'sc')
            ->leftJoin('sc.items', 'di')
            ->leftJoin('di.document', 'd')
            ->where('YEAR(d.date) = :year OR d.date IS NULL')
            ->setParameter('year', $year)
            ->groupBy('sc.id, sc.name, sc.color')
            ->orderBy('totalRevenue', 'DESC');

        if ($company) {
            $qb->andWhere('sc.company = :company OR sc.company IS NULL')
                ->setParameter('company', $company);
        }

        return $qb->getQuery()->getResult();
    }
}
