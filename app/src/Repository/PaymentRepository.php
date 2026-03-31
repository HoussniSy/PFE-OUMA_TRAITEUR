<?php

namespace App\Repository;

use App\Entity\Payment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Payment>
 */
class PaymentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Payment::class);
    }

    /**
     * Trouve tous les paiements d'un document
     *
     * @return Payment[]
     */
    public function findByDocument(int $documentId): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.document = :documentId')
            ->setParameter('documentId', $documentId)
            ->orderBy('p.datePaiement', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Calcule le total des paiements reçus pour un document
     */
    public function getTotalPaidForDocument(int $documentId): float
    {
        $result = $this->createQueryBuilder('p')
            ->select('SUM(p.montant)')
            ->where('p.document = :documentId')
            ->andWhere('p.statutPaiement = :statut')
            ->setParameter('documentId', $documentId)
            ->setParameter('statut', Payment::STATUT_RECU)
            ->getQuery()
            ->getSingleScalarResult();

        return (float) ($result ?? 0);
    }

    /**
     * Trouve les paiements récents
     *
     * @return Payment[]
     */
    public function findRecent(int $limit = 10): array
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.document', 'd')
            ->addSelect('d')
            ->orderBy('p.datePaiement', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Calcule le total des encaissements du mois
     */
    public function getMonthlyTotal(?int $year = null, ?int $month = null): float
    {
        $year = $year ?? date('Y');
        $month = $month ?? date('m');

        $result = $this->createQueryBuilder('p')
            ->select('SUM(p.montant)')
            ->where('YEAR(p.datePaiement) = :year')
            ->andWhere('MONTH(p.datePaiement) = :month')
            ->andWhere('p.statutPaiement = :statut')
            ->setParameter('year', $year)
            ->setParameter('month', $month)
            ->setParameter('statut', Payment::STATUT_RECU)
            ->getQuery()
            ->getSingleScalarResult();

        return (float) ($result ?? 0);
    }

    /**
     * Trouve les paiements par mode de paiement
     */
    public function getPaymentsByMode(?int $year = null): array
    {
        $qb = $this->createQueryBuilder('p')
            ->select('p.modePaiement', 'COUNT(p.id) as count', 'SUM(p.montant) as total')
            ->where('p.statutPaiement = :statut')
            ->setParameter('statut', Payment::STATUT_RECU)
            ->groupBy('p.modePaiement')
            ->orderBy('total', 'DESC');

        if ($year) {
            $qb->andWhere('YEAR(p.datePaiement) = :year')
               ->setParameter('year', $year);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Trouve les paiements en attente
     */
    public function findPendingPayments(): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.statutPaiement = :statut')
            ->setParameter('statut', Payment::STATUT_EN_ATTENTE)
            ->orderBy('p.datePaiement', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
