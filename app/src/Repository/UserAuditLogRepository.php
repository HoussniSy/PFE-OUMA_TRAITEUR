<?php

namespace App\Repository;

use App\Entity\User;
use App\Entity\UserAuditLog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserAuditLog>
 */
class UserAuditLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserAuditLog::class);
    }

    /**
     * Enregistre une nouvelle entrée dans le journal d'audit
     */
    public function logAction(
        User $performedBy,
        ?User $targetUser,
        string $targetUserEmail,
        string $action,
        ?array $details = null,
        ?string $ipAddress = null
    ): UserAuditLog {
        $log = new UserAuditLog();
        $log->setPerformedBy($performedBy)
            ->setTargetUser($targetUser)
            ->setTargetUserEmail($targetUserEmail)
            ->setAction($action)
            ->setIpAddress($ipAddress);

        if ($details) {
            $log->setDetailsArray($details);
        }

        $this->getEntityManager()->persist($log);
        $this->getEntityManager()->flush();

        return $log;
    }

    /**
     * Récupère les dernières entrées du journal d'audit
     */
    public function findRecent(int $limit = 50): array
    {
        return $this->createQueryBuilder('l')
            ->orderBy('l.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les entrées du journal pour un utilisateur spécifique
     */
    public function findByTargetUser(User $user, int $limit = 20): array
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.targetUser = :user')
            ->setParameter('user', $user)
            ->orderBy('l.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les entrées du journal effectuées par un admin
     */
    public function findByPerformedBy(User $admin, int $limit = 20): array
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.performedBy = :admin')
            ->setParameter('admin', $admin)
            ->orderBy('l.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les entrées par type d'action
     */
    public function findByAction(string $action, int $limit = 20): array
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.action = :action')
            ->setParameter('action', $action)
            ->orderBy('l.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Compte le nombre d'actions effectuées aujourd'hui
     */
    public function countTodayActions(): int
    {
        $today = new \DateTime('today');

        return (int) $this->createQueryBuilder('l')
            ->select('COUNT(l.id)')
            ->andWhere('l.createdAt >= :today')
            ->setParameter('today', $today)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Récupère les statistiques par action
     */
    public function getActionStats(): array
    {
        return $this->createQueryBuilder('l')
            ->select('l.action, COUNT(l.id) as count')
            ->groupBy('l.action')
            ->orderBy('count', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
