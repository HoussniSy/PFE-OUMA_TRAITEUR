<?php

namespace App\Entity;

use App\Repository\DocumentItemRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: DocumentItemRepository::class)]
#[ORM\HasLifecycleCallbacks]
class DocumentItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'items')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Document $document = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'La désignation est obligatoire.')]
    #[Assert\Length(max: 255)]
    private ?string $designation = null;

    #[ORM\Column]
    #[Assert\NotBlank(message: 'Le nombre de jours est obligatoire.')]
    #[Assert\Positive(message: 'Le nombre de jours doit être positif.')]
    private ?int $numberOfDays = null;

    #[ORM\Column]
    #[Assert\NotBlank(message: 'Le nombre de personnes est obligatoire.')]
    #[Assert\Positive(message: 'Le nombre de personnes doit être positif.')]
    private ?int $numberOfPersons = null;

    #[ORM\Column]
    #[Assert\NotBlank(message: 'Le nombre de services est obligatoire.')]
    #[Assert\Positive(message: 'Le nombre de services doit être positif.')]
    private ?int $numberOfServices = 1;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Assert\NotBlank(message: 'Le prix unitaire est obligatoire.')]
    #[Assert\PositiveOrZero(message: 'Le prix unitaire doit être positif ou nul.')]
    private ?string $unitPrice = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $totalAmount = '0.00';

    #[ORM\Column(nullable: true)]
    private ?int $position = null;

    #[ORM\ManyToOne(targetEntity: ServiceCategory::class, inversedBy: 'items')]
    #[ORM\JoinColumn(nullable: true)]
    private ?ServiceCategory $category = null;

    // --- Getters et Setters ---
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDocument(): ?Document
    {
        return $this->document;
    }

    public function setDocument(?Document $document): static
    {
        $this->document = $document;
        return $this;
    }

    public function getDesignation(): ?string
    {
        return $this->designation;
    }

    public function setDesignation(string $designation): static
    {
        $this->designation = $designation;
        return $this;
    }

    public function getNumberOfDays(): ?int
    {
        return $this->numberOfDays;
    }

    public function setNumberOfDays(int $numberOfDays): static
    {
        $this->numberOfDays = $numberOfDays;
        $this->calculateTotal();
        return $this;
    }

    public function getNumberOfPersons(): ?int
    {
        return $this->numberOfPersons;
    }

    public function setNumberOfPersons(int $numberOfPersons): static
    {
        $this->numberOfPersons = $numberOfPersons;
        $this->calculateTotal();
        return $this;
    }

    public function getNumberOfServices(): ?int
    {
        return $this->numberOfServices;
    }

    public function setNumberOfServices(int $numberOfServices): static
    {
        $this->numberOfServices = $numberOfServices;
        $this->calculateTotal();
        return $this;
    }

    public function getUnitPrice(): ?string
    {
        return $this->unitPrice;
    }

    public function setUnitPrice(string $unitPrice): static
    {
        $this->unitPrice = $unitPrice;
        $this->calculateTotal();
        return $this;
    }

    public function getTotalAmount(): ?string
    {
        return $this->totalAmount;
    }

    public function setTotalAmount(string $totalAmount): static
    {
        $this->totalAmount = $totalAmount;
        return $this;
    }

    public function getPosition(): ?int
    {
        return $this->position;
    }

    public function setPosition(?int $position): static
    {
        $this->position = $position;
        return $this;
    }

    public function getCategory(): ?ServiceCategory
    {
        return $this->category;
    }

    public function setCategory(?ServiceCategory $category): static
    {
        $this->category = $category;
        return $this;
    }

    // --- Méthodes utilitaires ---
    /**
     * Calcule automatiquement le montant total
     * Formule : numberOfDays × numberOfPersons × numberOfServices × unitPrice
     */
    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function calculateTotal(): void
    {
        if ($this->numberOfDays && $this->numberOfPersons && $this->numberOfServices && $this->unitPrice) {
            $total = $this->numberOfDays *
                $this->numberOfPersons *
                $this->numberOfServices *
                (float) $this->unitPrice;

            $this->totalAmount = number_format($total, 2, '.', '');
        }
    }

    /**
     * Retourne le montant total formaté
     */
    public function getFormattedTotalAmount(): string
    {
        return number_format((float) $this->totalAmount, 0, ',', ' ');
    }

    /**
     * Retourne le prix unitaire formaté
     */
    public function getFormattedUnitPrice(): string
    {
        return number_format((float) $this->unitPrice, 0, ',', ' ');
    }
}
