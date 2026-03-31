<?php

namespace App\Repository;

use App\Entity\Client;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Client>
 */
class ClientRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Client::class);
    }

    /**
     * Trouve tous les clients ordonnés par nom
     *
     * @return Client[]
     */
    public function findAllOrdered(): array
    {
        return $this->createQueryBuilder('c')
            ->orderBy('c.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche des clients par nom, email ou téléphone
     *
     * @return Client[]
     */
    public function search(string $query): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.name LIKE :query')
            ->orWhere('c.email LIKE :query')
            ->orWhere('c.phone LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->orderBy('c.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les clients avec leur nombre de documents et CA total
     */
    public function findWithStats(): array
    {
        return $this->createQueryBuilder('c')
            ->select('c', 'COUNT(d.id) as documentCount', 'SUM(d.totalTtc) as totalRevenue')
            ->leftJoin('c.documents', 'd')
            ->groupBy('c.id')
            ->orderBy('totalRevenue', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les top N clients par chiffre d'affaires
     *
     * @return array
     */
    public function findTopClientsByRevenue(int $limit = 10, ?int $year = null): array
    {
        $qb = $this->createQueryBuilder('c')
            ->select('c.name', 'c.id', 'SUM(d.totalTtc) as totalRevenue', 'COUNT(d.id) as documentCount')
            ->leftJoin('c.documents', 'd')
            ->where('d.type = :type')
            ->setParameter('type', 'invoice')
            ->groupBy('c.id')
            ->orderBy('totalRevenue', 'DESC')
            ->setMaxResults($limit);

        if ($year) {
            $qb->andWhere('YEAR(d.date) = :year')
               ->setParameter('year', $year);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Compte le nombre total de clients
     */
    public function countTotal(): int
    {
        return $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Compte les nouveaux clients du mois en cours
     */
    public function countNewThisMonth(): int
    {
        $firstDayOfMonth = new \DateTime('first day of this month 00:00:00');

        return $this->createQueryBuilder('c')
            ->select('COUNT(DISTINCT c.id)')
            ->leftJoin('c.documents', 'd')
            ->where('d.createdAt >= :firstDay')
            ->setParameter('firstDay', $firstDayOfMonth)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Trouve les clients avec des documents non payés
     */
    public function findClientsWithUnpaidDocuments(): array
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.documents', 'd')
            ->where('d.type = :type')
            ->andWhere('d.status != :status')
            ->setParameter('type', 'invoice')
            ->setParameter('status', 'paid')
            ->groupBy('c.id')
            ->getQuery()
            ->getResult();
    }
}
