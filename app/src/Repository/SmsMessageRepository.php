<?php

namespace App\Repository;

use App\Entity\Client;
use App\Entity\SmsMessage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SmsMessage>
 */
class SmsMessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SmsMessage::class);
    }

    /**
     * Trouve les SMS d'un client
     */
    public function findByClient(Client $client): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.client = :client')
            ->setParameter('client', $client)
            ->orderBy('s.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les SMS récents avec filtres optionnels
     */
    public function findRecent(?string $status = null, ?Client $client = null, int $limit = 50): array
    {
        $qb = $this->createQueryBuilder('s')
            ->orderBy('s.createdAt', 'DESC')
            ->setMaxResults($limit);

        if ($status) {
            $qb->andWhere('s.status = :status')
                ->setParameter('status', $status);
        }

        if ($client) {
            $qb->andWhere('s.client = :client')
                ->setParameter('client', $client);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Compte les SMS par statut
     */
    public function countByStatus(): array
    {
        $result = $this->createQueryBuilder('s')
            ->select('s.status, COUNT(s.id) as total')
            ->groupBy('s.status')
            ->getQuery()
            ->getResult();

        $counts = [];
        foreach ($result as $row) {
            $counts[$row['status']] = (int) $row['total'];
        }

        return $counts;
    }

    /**
     * Compte le total des SMS envoyés ce mois-ci
     */
    public function countSentThisMonth(): int
    {
        $firstDay = new \DateTimeImmutable('first day of this month 00:00:00');
        $lastDay = new \DateTimeImmutable('last day of this month 23:59:59');

        return (int) $this->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->where('s.sentAt >= :start')
            ->andWhere('s.sentAt <= :end')
            ->andWhere('s.status IN (:statuses)')
            ->setParameter('start', $firstDay)
            ->setParameter('end', $lastDay)
            ->setParameter('statuses', [SmsMessage::STATUS_SENT, SmsMessage::STATUS_DELIVERED, SmsMessage::STATUS_SIMULATED])
            ->getQuery()
            ->getSingleScalarResult();
    }
}
