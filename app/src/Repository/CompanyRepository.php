<?php

namespace App\Repository;

use App\Entity\Company;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Company>
 */
class CompanyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Company::class);
    }

    /**
     * Récupère la première entreprise (normalement il n'y en a qu'une seule)
     */
    public function findFirst(): ?Company
    {
        return $this->createQueryBuilder('c')
            ->orderBy('c.id', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Récupère les informations de l'entreprise
     * Alias pour findFirst()
     */
    public function getCompanyInfo(): ?Company
    {
        return $this->findFirst();
    }

    /**
     * Vérifie si une entreprise existe déjà
     */
    public function companyExists(): bool
    {
        return $this->count([]) > 0;
    }

    /**
     * Met à jour les informations de l'entreprise
     */
    public function updateCompany(Company $company): void
    {
        $this->getEntityManager()->persist($company);
        $this->getEntityManager()->flush();
    }

    /**
     * Crée ou met à jour l'entreprise unique
     */
    public function createOrUpdate(Company $company): void
    {
        $existingCompany = $this->findFirst();

        if ($existingCompany && $existingCompany->getId() !== $company->getId()) {
            // Si une entreprise existe déjà et c'est pas celle qu'on modifie,
            // on met à jour l'existante
            $existingCompany->setName($company->getName());
            $existingCompany->setNameArabic($company->getNameArabic());
            $existingCompany->setRegistrationNumber($company->getRegistrationNumber());
            $existingCompany->setNif($company->getNif());
            $existingCompany->setPhone($company->getPhone());
            $existingCompany->setAddress($company->getAddress());
            $existingCompany->setBankName($company->getBankName());
            $existingCompany->setBankAccount($company->getBankAccount());

            if ($company->getLogo()) {
                $existingCompany->setLogo($company->getLogo());
            }

            $this->getEntityManager()->flush();
        } else {
            // Sinon on persiste la nouvelle
            $this->getEntityManager()->persist($company);
            $this->getEntityManager()->flush();
        }
    }
}
