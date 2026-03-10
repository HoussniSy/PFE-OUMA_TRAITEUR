<?php

namespace App\Entity;

use App\Repository\EmailTemplateRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EmailTemplateRepository::class)]
#[ORM\Table(name: 'email_template')]
#[ORM\HasLifecycleCallbacks]
class EmailTemplate
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * Code unique du template (ex: quote_send, invoice_send)
     */
    #[ORM\Column(length: 50, unique: true)]
    private ?string $code = null;

    /**
     * Nom du template (ex: "Envoi de devis")
     */
    #[ORM\Column(length: 100)]
    private ?string $name = null;

    /**
     * Description du template
     */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    /**
     * Sujet de l'email
     */
    #[ORM\Column(length: 255)]
    private ?string $subject = null;

    /**
     * Contenu HTML du template
     */
    #[ORM\Column(type: Types::TEXT)]
    private ?string $body = null;

    /**
     * Variables disponibles (stockées en JSON)
     * Ex: ["{{nom_client}}", "{{montant_ttc}}"]
     */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $availableVariables = null;

    /**
     * Template par défaut du système (non supprimable)
     */
    #[ORM\Column]
    private bool $isDefault = false;

    /**
     * Template actif
     */
    #[ORM\Column]
    private bool $isActive = true;

    /**
     * Catégorie du template
     */
    #[ORM\Column(length: 50)]
    private ?string $category = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    /**
     * Utilisateur qui a créé le template
     */
    #[ORM\ManyToOne(targetEntity: User::class)]
    private ?User $createdBy = null;

    /**
     * Utilisateur qui a modifié le template en dernier
     */
    #[ORM\ManyToOne(targetEntity: User::class)]
    private ?User $updatedBy = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->isActive = true;
        $this->isDefault = false;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): static
    {
        $this->code = $code;
        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
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

    public function getAvailableVariables(): ?string
    {
        return $this->availableVariables;
    }

    public function setAvailableVariables(?string $availableVariables): static
    {
        $this->availableVariables = $availableVariables;
        return $this;
    }

    /**
     * Retourne les variables disponibles sous forme de tableau
     */
    public function getAvailableVariablesArray(): array
    {
        if (!$this->availableVariables) {
            return [];
        }

        $decoded = json_decode($this->availableVariables, true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Définit les variables disponibles depuis un tableau
     */
    public function setAvailableVariablesArray(array $variables): static
    {
        $this->availableVariables = json_encode($variables, JSON_UNESCAPED_UNICODE);
        return $this;
    }

    public function isDefault(): bool
    {
        return $this->isDefault;
    }

    public function setIsDefault(bool $isDefault): static
    {
        $this->isDefault = $isDefault;
        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;
        return $this;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(string $category): static
    {
        $this->category = $category;
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

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?User $createdBy): static
    {
        $this->createdBy = $createdBy;
        return $this;
    }

    public function getUpdatedBy(): ?User
    {
        return $this->updatedBy;
    }

    public function setUpdatedBy(?User $updatedBy): static
    {
        $this->updatedBy = $updatedBy;
        return $this;
    }

    /**
     * Met à jour automatiquement la date de modification
     */
    #[ORM\PreUpdate]
    public function updateTimestamp(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    /**
     * Retourne le badge HTML de la catégorie
     */
    public function getCategoryBadge(): string
    {
        return match($this->category) {
            'document' => '<span class="badge bg-primary">Document</span>',
            'user' => '<span class="badge bg-info">Utilisateur</span>',
            'payment' => '<span class="badge bg-success">Paiement</span>',
            'reminder' => '<span class="badge bg-warning">Relance</span>',
            default => '<span class="badge bg-secondary">' . ucfirst($this->category) . '</span>',
        };
    }

    /**
     * Retourne le libellé de la catégorie
     */
    public function getCategoryLabel(): string
    {
        return match($this->category) {
            'document' => 'Documents',
            'user' => 'Utilisateurs',
            'payment' => 'Paiements',
            'reminder' => 'Relances',
            default => ucfirst($this->category),
        };
    }
}
