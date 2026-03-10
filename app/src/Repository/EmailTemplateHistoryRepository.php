<?php

namespace App\Repository;

use App\Entity\EmailTemplate;
use App\Entity\EmailTemplateHistory;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<EmailTemplateHistory>
 */
class EmailTemplateHistoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EmailTemplateHistory::class);
    }

    /**
     * Sauvegarde une version du template dans l'historique
     */
    public function saveHistory(
        EmailTemplate $template,
        User $user,
        string $action = 'updated',
        ?string $comment = null
    ): EmailTemplateHistory {
        // Récupérer le dernier numéro de version
        $lastVersion = $this->getLastVersion($template);

        $history = new EmailTemplateHistory();
        $history->setTemplate($template)
            ->setVersion($lastVersion + 1)
            ->setSubject($template->getSubject())
            ->setBody($template->getBody())
            ->setAction($action)
            ->setComment($comment)
            ->setModifiedBy($user);

        $this->getEntityManager()->persist($history);
        $this->getEntityManager()->flush();

        return $history;
    }

    /**
     * Récupère l'historique complet d'un template
     */
    public function findByTemplate(EmailTemplate $template): array
    {
        return $this->createQueryBuilder('h')
            ->andWhere('h.template = :template')
            ->setParameter('template', $template)
            ->orderBy('h.version', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère une version spécifique
     */
    public function findVersion(EmailTemplate $template, int $version): ?EmailTemplateHistory
    {
        return $this->createQueryBuilder('h')
            ->andWhere('h.template = :template')
            ->andWhere('h.version = :version')
            ->setParameter('template', $template)
            ->setParameter('version', $version)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Récupère le dernier numéro de version
     */
    public function getLastVersion(EmailTemplate $template): int
    {
        $result = $this->createQueryBuilder('h')
            ->select('MAX(h.version)')
            ->andWhere('h.template = :template')
            ->setParameter('template', $template)
            ->getQuery()
            ->getSingleScalarResult();

        return $result ? (int) $result : 0;
    }

    /**
     * Compte le nombre de modifications par template
     */
    public function countByTemplate(EmailTemplate $template): int
    {
        return (int) $this->createQueryBuilder('h')
            ->select('COUNT(h.id)')
            ->andWhere('h.template = :template')
            ->setParameter('template', $template)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Récupère les dernières modifications (tous templates confondus)
     */
    public function findRecent(int $limit = 10): array
    {
        return $this->createQueryBuilder('h')
            ->orderBy('h.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Supprime l'historique d'un template
     */
    public function deleteByTemplate(EmailTemplate $template): void
    {
        $this->createQueryBuilder('h')
            ->delete()
            ->andWhere('h.template = :template')
            ->setParameter('template', $template)
            ->getQuery()
            ->execute();
    }
}
