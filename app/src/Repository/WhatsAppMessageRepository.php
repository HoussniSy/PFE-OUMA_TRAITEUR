<?php

namespace App\Repository;

use App\Entity\Client;
use App\Entity\WhatsAppMessage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<WhatsAppMessage>
 */
class WhatsAppMessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WhatsAppMessage::class);
    }

    /**
     * Trouve les messages d'un client
     */
    public function findByClient(Client $client): array
    {
        return $this->createQueryBuilder('w')
            ->where('w.client = :client')
            ->setParameter('client', $client)
            ->orderBy('w.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les messages récents avec filtres optionnels
     */
    public function findRecent(?string $status = null, ?string $messageType = null, ?Client $client = null, int $limit = 50): array
    {
        $qb = $this->createQueryBuilder('w')
            ->orderBy('w.createdAt', 'DESC')
            ->setMaxResults($limit);

        if ($status) {
            $qb->andWhere('w.status = :status')
                ->setParameter('status', $status);
        }

        if ($messageType) {
            $qb->andWhere('w.messageType = :messageType')
                ->setParameter('messageType', $messageType);
        }

        if ($client) {
            $qb->andWhere('w.client = :client')
                ->setParameter('client', $client);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Compte les messages par statut
     */
    public function countByStatus(): array
    {
        $result = $this->createQueryBuilder('w')
            ->select('w.status, COUNT(w.id) as total')
            ->groupBy('w.status')
            ->getQuery()
            ->getResult();

        $counts = [];
        foreach ($result as $row) {
            $counts[$row['status']] = (int) $row['total'];
        }

        return $counts;
    }

    /**
     * Compte le total des messages envoyés ce mois-ci
     */
    public function countSentThisMonth(): int
    {
        $firstDay = new \DateTimeImmutable('first day of this month 00:00:00');
        $lastDay = new \DateTimeImmutable('last day of this month 23:59:59');

        return (int) $this->createQueryBuilder('w')
            ->select('COUNT(w.id)')
            ->where('w.sentAt >= :start')
            ->andWhere('w.sentAt <= :end')
            ->andWhere('w.status IN (:statuses)')
            ->setParameter('start', $firstDay)
            ->setParameter('end', $lastDay)
            ->setParameter('statuses', [WhatsAppMessage::STATUS_SENT, WhatsAppMessage::STATUS_DELIVERED, WhatsAppMessage::STATUS_READ, WhatsAppMessage::STATUS_SIMULATED])
            ->getQuery()
            ->getSingleScalarResult();
    }
}
