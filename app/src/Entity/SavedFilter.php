<?php

namespace App\Entity;

use App\Repository\SavedFilterRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SavedFilterRepository::class)]
#[ORM\Table(name: 'saved_filter')]
class SavedFilter
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * L'utilisateur qui a créé ce filtre
     */
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    /**
     * Nom du filtre (ex: "Factures payées 2026")
     */
    #[ORM\Column(length: 100)]
    private ?string $name = null;

    /**
     * Type de page (document, client, etc.)
     */
    #[ORM\Column(length: 50)]
    private ?string $pageType = null;

    /**
     * Paramètres du filtre en JSON
     * Ex: {"type":"invoice","status":"paid","dateFrom":"2026-01-01"}
     */
    #[ORM\Column(type: Types::TEXT)]
    private ?string $filters = null;

    /**
     * Ordre d'affichage (pour trier les filtres favoris)
     */
    #[ORM\Column]
    private int $displayOrder = 0;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
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

    public function getPageType(): ?string
    {
        return $this->pageType;
    }

    public function setPageType(string $pageType): static
    {
        $this->pageType = $pageType;
        return $this;
    }

    public function getFilters(): ?string
    {
        return $this->filters;
    }

    public function setFilters(string $filters): static
    {
        $this->filters = $filters;
        return $this;
    }

    /**
     * Retourne les filtres décodés depuis JSON
     */
    public function getFiltersArray(): array
    {
        if (!$this->filters) {
            return [];
        }

        $decoded = json_decode($this->filters, true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Définit les filtres à partir d'un tableau (encodage JSON automatique)
     */
    public function setFiltersArray(array $filters): static
    {
        $this->filters = json_encode($filters, JSON_UNESCAPED_UNICODE);
        return $this;
    }

    public function getDisplayOrder(): int
    {
        return $this->displayOrder;
    }

    public function setDisplayOrder(int $displayOrder): static
    {
        $this->displayOrder = $displayOrder;
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

    /**
     * Met à jour la date de modification
     */
    #[ORM\PreUpdate]
    public function updateTimestamp(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
