<?php

namespace App\Repository;

use App\Entity\DashboardWidget;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DashboardWidget>
 */
class DashboardWidgetRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DashboardWidget::class);
    }

    /**
     * Retourne les widgets d'un utilisateur, ordonnés par position
     *
     * @return DashboardWidget[]
     */
    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('w')
            ->where('w.user = :user')
            ->setParameter('user', $user)
            ->orderBy('w.position', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Retourne les widgets visibles d'un utilisateur, ordonnés par position
     *
     * @return DashboardWidget[]
     */
    public function findVisibleByUser(User $user): array
    {
        return $this->createQueryBuilder('w')
            ->where('w.user = :user')
            ->andWhere('w.isVisible = :visible')
            ->setParameter('user', $user)
            ->setParameter('visible', true)
            ->orderBy('w.position', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
