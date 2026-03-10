<?php

namespace App\Entity;

use App\Repository\EmailTemplateHistoryRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EmailTemplateHistoryRepository::class)]
#[ORM\Table(name: 'email_template_history')]
class EmailTemplateHistory
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * Template concerné
     */
    #[ORM\ManyToOne(targetEntity: EmailTemplate::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?EmailTemplate $template = null;

    /**
     * Version du template (auto-incrémentée)
     */
    #[ORM\Column]
    private int $version = 1;

    /**
     * Sujet à ce moment-là
     */
    #[ORM\Column(length: 255)]
    private ?string $subject = null;

    /**
     * Contenu HTML à ce moment-là
     */
    #[ORM\Column(type: Types::TEXT)]
    private ?string $body = null;

    /**
     * Action effectuée (created, updated, restored)
     */
    #[ORM\Column(length: 20)]
    private ?string $action = null;

    /**
     * Commentaire sur la modification
     */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $comment = null;

    /**
     * Utilisateur qui a effectué la modification
     */
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $modifiedBy = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTemplate(): ?EmailTemplate
    {
        return $this->template;
    }

    public function setTemplate(?EmailTemplate $template): static
    {
        $this->template = $template;
        return $this;
    }

    public function getVersion(): int
    {
        return $this->version;
    }

    public function setVersion(int $version): static
    {
        $this->version = $version;
        return $this;
    }

    public function getSubject(): ?string
    {
        return $this->subject;
    }

    public function setSubject(string $subject): static
    {
        $this->subject = $subject;
        return $this;
    }

    public function getBody(): ?string
    {
        return $this->body;
    }

    public function setBody(string $body): static
    {
        $this->body = $body;
        return $this;
    }

    public function getAction(): ?string
    {
        return $this->action;
    }

    public function setAction(string $action): static
    {
        $this->action = $action;
        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): static
    {
        $this->comment = $comment;
        return $this;
    }

    public function getModifiedBy(): ?User
    {
        return $this->modifiedBy;
    }

    public function setModifiedBy(?User $modifiedBy): static
    {
        $this->modifiedBy = $modifiedBy;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    /**
     * Retourne le badge HTML de l'action
     */
    public function getActionBadge(): string
    {
        return match($this->action) {
            'created' => '<span class="badge bg-success">Créé</span>',
            'updated' => '<span class="badge bg-info">Modifié</span>',
            'restored' => '<span class="badge bg-warning">Restauré</span>',
            default => '<span class="badge bg-secondary">' . ucfirst($this->action) . '</span>',
        };
    }

    /**
     * Retourne un résumé de la modification
     */
    public function getSummary(): string
    {
        $user = $this->modifiedBy ? $this->modifiedBy->getFullName() : 'Inconnu';
        $action = match($this->action) {
            'created' => 'a créé',
            'updated' => 'a modifié',
            'restored' => 'a restauré',
            default => 'a effectué une action sur',
        };

        return sprintf(
            '%s %s la version %d le %s',
            $user,
            $action,
            $this->version,
            $this->createdAt->format('d/m/Y à H:i')
        );
    }
}
