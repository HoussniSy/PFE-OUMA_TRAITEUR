<?php

namespace App\Repository;

use App\Entity\Notification;
use App\Entity\Document;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Notification>
 */
class NotificationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Notification::class);
    }

    /**
     * Récupère toutes les notifications prêtes à être envoyées
     *
     * @return Notification[]
     */
    public function findReadyToSend(): array
    {
        return $this->createQueryBuilder('n')
            ->where('n.status = :status')
            ->andWhere('n.scheduledAt <= :now')
            ->setParameter('status', Notification::STATUS_PENDING)
            ->setParameter('now', new \DateTimeImmutable())
            ->orderBy('n.scheduledAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les notifications d'un document
     *
     * @param Document $document
     * @return Notification[]
     */
    public function findByDocument(Document $document): array
    {
        return $this->createQueryBuilder('n')
            ->where('n.document = :document')
            ->setParameter('document', $document)
            ->orderBy('n.scheduledAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Vérifie si une notification du même type existe déjà pour ce document
     *
     * @param Document $document
     * @param string $type
     * @param int|null $reminderNumber
     * @return bool
     */
    public function existsForDocument(Document $document, string $type, ?int $reminderNumber = null): bool
    {
        $qb = $this->createQueryBuilder('n')
            ->select('COUNT(n.id)')
            ->where('n.document = :document')
            ->andWhere('n.type = :type')
            ->setParameter('document', $document)
            ->setParameter('type', $type);

        if ($reminderNumber !== null) {
            $qb->andWhere('n.reminderNumber = :reminderNumber')
                ->setParameter('reminderNumber', $reminderNumber);
        }

        return (int) $qb->getQuery()->getSingleScalarResult() > 0;
    }

    /**
     * Compte les notifications en échec
     *
     * @return int
     */
    public function countFailed(): int
    {
        return (int) $this->createQueryBuilder('n')
            ->select('COUNT(n.id)')
            ->where('n.status = :status')
            ->setParameter('status', Notification::STATUS_FAILED)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Compte les notifications envoyées sur une période
     *
     * @param \DateTimeInterface $from
     * @param \DateTimeInterface $to
     * @return int
     */
    public function countSentBetween(\DateTimeInterface $from, \DateTimeInterface $to): int
    {
        return (int) $this->createQueryBuilder('n')
            ->select('COUNT(n.id)')
            ->where('n.status = :status')
            ->andWhere('n.sentAt BETWEEN :from AND :to')
            ->setParameter('status', Notification::STATUS_SENT)
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Récupère les dernières notifications envoyées
     *
     * @param int $limit
     * @return Notification[]
     */
    public function findRecentSent(int $limit = 10): array
    {
        return $this->createQueryBuilder('n')
            ->where('n.status = :status')
            ->setParameter('status', Notification::STATUS_SENT)
            ->orderBy('n.sentAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Supprime les anciennes notifications (plus de 6 mois)
     *
     * @return int Nombre de notifications supprimées
     */
    public function deleteOldNotifications(): int
    {
        $sixMonthsAgo = new \DateTimeImmutable('-6 months');

        return $this->createQueryBuilder('n')
            ->delete()
            ->where('n.createdAt < :date')
            ->andWhere('n.status = :status')
            ->setParameter('date', $sixMonthsAgo)
            ->setParameter('status', Notification::STATUS_SENT)
            ->getQuery()
            ->execute();
    }

    /**
     * Récupère le dernier numéro de rappel pour un document
     *
     * @param Document $document
     * @param string $type
     * @return int
     */
    public function getLastReminderNumber(Document $document, string $type): int
    {
        $result = $this->createQueryBuilder('n')
            ->select('MAX(n.reminderNumber)')
            ->where('n.document = :document')
            ->andWhere('n.type = :type')
            ->setParameter('document', $document)
            ->setParameter('type', $type)
            ->getQuery()
            ->getSingleScalarResult();

        return $result ? (int) $result : 0;
    }
}
