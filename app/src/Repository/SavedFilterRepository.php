<?php

namespace App\Repository;

use App\Entity\SavedFilter;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SavedFilter>
 */
class SavedFilterRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SavedFilter::class);
    }

    /**
     * Récupère tous les filtres sauvegardés d'un utilisateur pour une page
     */
    public function findByUserAndPage(User $user, string $pageType): array
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.user = :user')
            ->andWhere('f.pageType = :pageType')
            ->setParameter('user', $user)
            ->setParameter('pageType', $pageType)
            ->orderBy('f.displayOrder', 'ASC')
            ->addOrderBy('f.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Vérifie si un nom de filtre existe déjà pour cet utilisateur
     */
    public function filterNameExists(User $user, string $name, string $pageType, ?int $excludeId = null): bool
    {
        $qb = $this->createQueryBuilder('f')
            ->select('COUNT(f.id)')
            ->andWhere('f.user = :user')
            ->andWhere('f.name = :name')
            ->andWhere('f.pageType = :pageType')
            ->setParameter('user', $user)
            ->setParameter('name', $name)
            ->setParameter('pageType', $pageType);

        if ($excludeId) {
            $qb->andWhere('f.id != :excludeId')
                ->setParameter('excludeId', $excludeId);
        }

        return (int) $qb->getQuery()->getSingleScalarResult() > 0;
    }

    /**
     * Sauvegarde un nouveau filtre
     */
    public function saveFilter(User $user, string $name, string $pageType, array $filters): SavedFilter
    {
        $savedFilter = new SavedFilter();
        $savedFilter->setUser($user)
            ->setName($name)
            ->setPageType($pageType)
            ->setFiltersArray($filters);

        $this->getEntityManager()->persist($savedFilter);
        $this->getEntityManager()->flush();

        return $savedFilter;
    }

    /**
     * Met à jour un filtre existant
     */
    public function updateFilter(SavedFilter $filter, string $name, array $filters): void
    {
        $filter->setName($name)
            ->setFiltersArray($filters);

        $this->getEntityManager()->flush();
    }

    /**
     * Supprime un filtre
     */
    public function deleteFilter(SavedFilter $filter): void
    {
        $this->getEntityManager()->remove($filter);
        $this->getEntityManager()->flush();
    }

    /**
     * Compte le nombre de filtres d'un utilisateur pour une page
     */
    public function countByUserAndPage(User $user, string $pageType): int
    {
        return (int) $this->createQueryBuilder('f')
            ->select('COUNT(f.id)')
            ->andWhere('f.user = :user')
            ->andWhere('f.pageType = :pageType')
            ->setParameter('user', $user)
            ->setParameter('pageType', $pageType)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
