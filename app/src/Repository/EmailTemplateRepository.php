<?php

namespace App\Repository;

use App\Entity\EmailTemplate;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<EmailTemplate>
 */
class EmailTemplateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EmailTemplate::class);
    }

    /**
     * Trouve un template par son code
     */
    public function findByCode(string $code): ?EmailTemplate
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.code = :code')
            ->andWhere('t.isActive = :active')
            ->setParameter('code', $code)
            ->setParameter('active', true)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Trouve tous les templates actifs par catégorie
     */
    public function findByCategory(string $category): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.category = :category')
            ->andWhere('t.isActive = :active')
            ->setParameter('category', $category)
            ->setParameter('active', true)
            ->orderBy('t.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve tous les templates par défaut
     */
    public function findDefaults(): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.isDefault = :default')
            ->setParameter('default', true)
            ->orderBy('t.category', 'ASC')
            ->addOrderBy('t.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve tous les templates personnalisés (non par défaut)
     */
    public function findCustom(): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.isDefault = :default')
            ->setParameter('default', false)
            ->orderBy('t.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Compte les templates par catégorie
     */
    public function countByCategory(): array
    {
        $results = $this->createQueryBuilder('t')
            ->select('t.category, COUNT(t.id) as count')
            ->groupBy('t.category')
            ->getQuery()
            ->getResult();

        $counts = [];
        foreach ($results as $result) {
            $counts[$result['category']] = (int) $result['count'];
        }

        return $counts;
    }

    /**
     * Vérifie si un code existe déjà
     */
    public function codeExists(string $code, ?int $excludeId = null): bool
    {
        $qb = $this->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->andWhere('t.code = :code')
            ->setParameter('code', $code);

        if ($excludeId) {
            $qb->andWhere('t.id != :id')
                ->setParameter('id', $excludeId);
        }

        return (int) $qb->getQuery()->getSingleScalarResult() > 0;
    }

    /**
     * Recherche de templates
     */
    public function search(string $query): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.name LIKE :query OR t.description LIKE :query OR t.code LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->orderBy('t.updatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
