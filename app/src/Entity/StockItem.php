<?php

namespace App\Entity;

use App\Repository\StockItemRepository;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Delete;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    operations: [
        new GetCollection(),
        new Get(),
        new Post(security: "is_granted('ROLE_USER')"),
        new Put(security: "is_granted('ROLE_USER')"),
        new Delete(security: "is_granted('ROLE_ADMIN')"),
    ],
    normalizationContext: ['groups' => ['stock:read']],
    denormalizationContext: ['groups' => ['stock:write']],
)]
#[ORM\Entity(repositoryClass: StockItemRepository::class)]
class StockItem
{
    public const UNIT_KG = 'kg';
    public const UNIT_LITRE = 'L';
    public const UNIT_PIECE = 'pcs';
    public const UNIT_PACK = 'pack';
    public const UNIT_BOITE = 'boîte';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['stock:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le nom de l\'article est obligatoire.')]
    #[Assert\Length(max: 255, maxMessage: 'Le nom ne peut pas dépasser {{ limit }} caractères.')]
    #[Groups(['stock:read', 'stock:write'])]
    private ?string $name = null;

    #[ORM\Column(length: 500, nullable: true)]
    #[Assert\Length(max: 500, maxMessage: 'La description ne peut pas dépasser {{ limit }} caractères.')]
    #[Groups(['stock:read', 'stock:write'])]
    private ?string $description = null;

    #[ORM\Column(length: 20)]
    #[Assert\NotBlank(message: 'L\'unité est obligatoire.')]
    #[Assert\Choice(choices: [self::UNIT_KG, self::UNIT_LITRE, self::UNIT_PIECE, self::UNIT_PACK, self::UNIT_BOITE], message: 'Unité invalide.')]
    #[Groups(['stock:read', 'stock:write'])]
    private ?string $unit = self::UNIT_KG;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Assert\NotBlank(message: 'La quantité actuelle est obligatoire.')]
    #[Assert\PositiveOrZero(message: 'La quantité doit être positive ou nulle.')]
    #[Groups(['stock:read', 'stock:write'])]
    private ?string $currentQuantity = '0.00';

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Assert\NotBlank(message: 'La quantité minimale est obligatoire.')]
    #[Assert\PositiveOrZero(message: 'La quantité minimale doit être positive ou nulle.')]
    #[Groups(['stock:read', 'stock:write'])]
    private ?string $minimumQuantity = '0.00';

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    #[Assert\PositiveOrZero(message: 'Le prix unitaire doit être positif ou nul.')]
    #[Groups(['stock:read', 'stock:write'])]
    private ?string $unitPrice = null;

    #[ORM\ManyToOne(targetEntity: Company::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Company $company = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $lastRestockedAt = null;

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

    public function getUnit(): ?string
    {
        return $this->unit;
    }

    public function setUnit(string $unit): static
    {
        $this->unit = $unit;
        return $this;
    }

    public function getCurrentQuantity(): ?string
    {
        return $this->currentQuantity;
    }

    public function setCurrentQuantity(string $currentQuantity): static
    {
        $this->currentQuantity = $currentQuantity;
        return $this;
    }

    public function getMinimumQuantity(): ?string
    {
        return $this->minimumQuantity;
    }

    public function setMinimumQuantity(string $minimumQuantity): static
    {
        $this->minimumQuantity = $minimumQuantity;
        return $this;
    }

    public function getUnitPrice(): ?string
    {
        return $this->unitPrice;
    }

    public function setUnitPrice(?string $unitPrice): static
    {
        $this->unitPrice = $unitPrice;
        return $this;
    }

    public function getCompany(): ?Company
    {
        return $this->company;
    }

    public function setCompany(?Company $company): static
    {
        $this->company = $company;
        return $this;
    }

    public function getLastRestockedAt(): ?\DateTimeImmutable
    {
        return $this->lastRestockedAt;
    }

    public function setLastRestockedAt(?\DateTimeImmutable $lastRestockedAt): static
    {
        $this->lastRestockedAt = $lastRestockedAt;
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

    // --- Méthodes utilitaires ---

    /**
     * Vérifie si le stock est faible (en dessous du minimum)
     */
    public function isLowStock(): bool
    {
        return (float) $this->currentQuantity <= (float) $this->minimumQuantity;
    }

    /**
     * Retourne le pourcentage de stock restant par rapport au minimum
     */
    public function getStockPercentage(): float
    {
        if ((float) $this->minimumQuantity <= 0) {
            return 100.0;
        }
        return min(100, ((float) $this->currentQuantity / (float) $this->minimumQuantity) * 100);
    }

    /**
     * Retourne la classe CSS du badge de statut
     */
    public function getStockBadgeClass(): string
    {
        $percentage = $this->getStockPercentage();
        if ($percentage <= 50) {
            return 'bg-danger';
        }
        if ($percentage <= 100) {
            return 'bg-warning';
        }
        return 'bg-success';
    }

    /**
     * Retourne le libellé du statut de stock
     */
    public function getStockStatusLabel(): string
    {
        if ($this->isLowStock()) {
            return 'Stock faible';
        }
        return 'En stock';
    }

    /**
     * Réapprovisionner le stock
     */
    public function restock(float $quantity): static
    {
        $this->currentQuantity = number_format((float) $this->currentQuantity + $quantity, 2, '.', '');
        $this->lastRestockedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getFormattedQuantity(): string
    {
        return number_format((float) $this->currentQuantity, 2, ',', ' ') . ' ' . $this->unit;
    }

    public function __toString(): string
    {
        return $this->name ?? '';
    }
}
