<?php

namespace App\Entity;

use App\Repository\DocumentRepository;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Delete;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
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
    normalizationContext: ['groups' => ['document:read']],
    denormalizationContext: ['groups' => ['document:write']],
)]
#[ORM\Entity(repositoryClass: DocumentRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Document
{
    // Constantes pour les types de document
    public const TYPE_QUOTE = 'quote';
    public const TYPE_INVOICE = 'invoice';

    // Constantes pour les statuts
    public const STATUS_DRAFT = 'draft';
    public const STATUS_SENT = 'sent';
    public const STATUS_PARTIALLY_PAID = 'partially_paid';
    public const STATUS_PAID = 'paid';
    public const STATUS_CANCELLED = 'cancelled';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['document:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank(message: 'Le type est obligatoire.')]
    #[Assert\Choice(choices: [self::TYPE_QUOTE, self::TYPE_INVOICE], message: 'Type invalide.')]
    #[Groups(['document:read', 'document:write'])]
    private ?string $type = null;

    #[ORM\Column(length: 255, unique: true)]
    #[Assert\NotBlank(message: 'Le numéro est obligatoire.')]
    #[Groups(['document:read', 'document:write'])]
    private ?string $number = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Assert\NotBlank(message: 'La date est obligatoire.')]
    #[Groups(['document:read', 'document:write'])]
    private ?\DateTimeInterface $date = null;

    #[ORM\ManyToOne(inversedBy: 'documents')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotBlank(message: 'Le client est obligatoire.')]
    #[Groups(['document:read', 'document:write'])]
    private ?Client $client = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['document:read', 'document:write'])]
    private ?string $location = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Groups(['document:read'])]
    private ?string $totalHt = '0.00';

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2)]
    #[Groups(['document:read', 'document:write'])]
    private ?string $taxRate = '16.00';

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Groups(['document:read'])]
    private ?string $totalTtc = '0.00';

    #[ORM\Column(length: 10)]
    #[Groups(['document:read', 'document:write'])]
    private ?string $currency = 'MRU';

    #[ORM\Column(type: Types::INTEGER)]
    private ?int $paymentTerms = 30;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dueDate = null;

    #[ORM\Column(length: 50)]
    #[Assert\Choice(
        choices: [self::STATUS_DRAFT, self::STATUS_SENT, self::STATUS_PARTIALLY_PAID, self::STATUS_PAID, self::STATUS_CANCELLED],
        message: 'Statut invalide.'
    )]
    #[Groups(['document:read', 'document:write'])]
    private ?string $status = self::STATUS_DRAFT;

    #[ORM\OneToMany(targetEntity: DocumentItem::class, mappedBy: 'document', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC'])]
    #[Assert\Valid]
    #[Groups(['document:read'])]
    private Collection $items;

    #[ORM\OneToMany(targetEntity: Payment::class, mappedBy: 'document', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $payments;

    #[ORM\Column]
    #[Groups(['document:read'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\ManyToOne(targetEntity: Company::class, inversedBy: 'documents')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Company $company = null;

    public function __construct()
    {
        $this->items = new ArrayCollection();
        $this->payments = new ArrayCollection();
        $this->date = new \DateTime();
        $this->createdAt = new \DateTimeImmutable();
    }

    // --- Getters et Setters ---
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;
        return $this;
    }

    public function getNumber(): ?string
    {
        return $this->number;
    }

    public function setNumber(string $number): static
    {
        $this->number = $number;
        return $this;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): static
    {
        $this->date = $date;
        return $this;
    }

    public function getClient(): ?Client
    {
        return $this->client;
    }

    public function setClient(?Client $client): static
    {
        $this->client = $client;
        return $this;
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function setLocation(?string $location): static
    {
        $this->location = $location;
        return $this;
    }

    public function getTotalHt(): ?string
    {
        return $this->totalHt;
    }

    public function setTotalHt(string $totalHt): static
    {
        $this->totalHt = $totalHt;
        return $this;
    }

    public function getTaxRate(): ?string
    {
        return $this->taxRate;
    }

    public function setTaxRate(string $taxRate): static
    {
        $this->taxRate = $taxRate;
        return $this;
    }

    public function getTotalTtc(): ?string
    {
        return $this->totalTtc;
    }

    public function setTotalTtc(string $totalTtc): static
    {
        $this->totalTtc = $totalTtc;
        return $this;
    }

    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): static
    {
        $this->currency = $currency;
        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
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

    public function getCompany(): ?Company
    {
        return $this->company;
    }

    public function setCompany(?Company $company): static
    {
        $this->company = $company;
        return $this;
    }

    public function getPaymentTerms(): ?int
    {
        return $this->paymentTerms;
    }

    public function setPaymentTerms(int $paymentTerms): static
    {
        $this->paymentTerms = $paymentTerms;
        return $this;
    }

    public function getDueDate(): ?\DateTimeInterface
    {
        return $this->dueDate;
    }

    public function setDueDate(?\DateTimeInterface $dueDate): static
    {
        $this->dueDate = $dueDate;
        return $this;
    }

    // --- Méthodes pour les relations ---
    /**
     * @return Collection<int, DocumentItem>
     */
    public function getItems(): Collection
    {
        return $this->items;
    }

    public function addItem(DocumentItem $item): static
    {
        if (!$this->items->contains($item)) {
            $this->items->add($item);
            $item->setDocument($this);
        }
        return $this;
    }

    public function removeItem(DocumentItem $item): static
    {
        if ($this->items->removeElement($item)) {
            if ($item->getDocument() === $this) {
                $item->setDocument(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, Payment>
     */
    public function getPayments(): Collection
    {
        return $this->payments;
    }

    public function addPayment(Payment $payment): static
    {
        if (!$this->payments->contains($payment)) {
            $this->payments->add($payment);
            $payment->setDocument($this);
        }
        return $this;
    }

    public function removePayment(Payment $payment): static
    {
        if ($this->payments->removeElement($payment)) {
            if ($payment->getDocument() === $this) {
                $payment->setDocument(null);
            }
        }
        return $this;
    }

    // --- Méthodes utilitaires ---
    /**
     * Calcule automatiquement les totaux du document
     */
    public function calculateTotals(): void
    {
        $totalHt = 0;
        foreach ($this->items as $item) {
            $totalHt += (float) $item->getTotalAmount();
        }

        $this->totalHt = number_format($totalHt, 2, '.', '');

        $taxAmount = $totalHt * ((float) $this->taxRate / 100);
        $totalTtc = $totalHt + $taxAmount;

        $this->totalTtc = number_format($totalTtc, 2, '.', '');
    }

    /**
     * Calcule le montant total des paiements
     */
    public function getTotalPaid(): float
    {
        $total = 0;
        foreach ($this->payments as $payment) {
            if ($payment->getStatutPaiement() === Payment::STATUT_RECU) {
                $total += (float) $payment->getMontant();
            }
        }
        return $total;
    }

    /**
     * Calcule le reste à payer
     */
    public function getRemainingAmount(): float
    {
        return (float) $this->totalTtc - $this->getTotalPaid();
    }

    /**
     * Vérifie si le document est complètement payé
     */
    public function isFullyPaid(): bool
    {
        return $this->getRemainingAmount() <= 0.01;
    }

    /**
     * Met à jour automatiquement le statut du document en fonction des paiements
     * À appeler après chaque ajout/suppression de paiement
     */
    public function updatePaymentStatus(): void
    {
        // Ne s'applique qu'aux factures
        if ($this->type !== self::TYPE_INVOICE) {
            return;
        }

        // Si le document est annulé, on ne change pas le statut
        if ($this->status === self::STATUS_CANCELLED) {
            return;
        }

        $totalPaid = $this->getTotalPaid();
        $totalTtc = (float) $this->totalTtc;

        if ($totalPaid <= 0) {
            // Aucun paiement : retour à "Envoyé" si pas en brouillon
            if ($this->status !== self::STATUS_DRAFT) {
                $this->status = self::STATUS_SENT;
            }
        } elseif ($totalPaid >= $totalTtc - 0.01) {
            // Complètement payé
            $this->status = self::STATUS_PAID;
        } else {
            // Partiellement payé
            $this->status = self::STATUS_PARTIALLY_PAID;
        }
    }

    /**
     * Retourne le libellé du type
     */
    public function getTypeLabel(): string
    {
        return $this->type === self::TYPE_QUOTE ? 'Devis' : 'Facture';
    }

    /**
     * Retourne le libellé du statut
     */
    public function getStatusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT => 'Brouillon',
            self::STATUS_SENT => 'Envoyé',
            self::STATUS_PARTIALLY_PAID => 'Partiellement payé',
            self::STATUS_PAID => 'Payé',
            self::STATUS_CANCELLED => 'Annulé',
            default => $this->status
        };
    }

    /**
     * Retourne la couleur du badge selon le statut
     */
    public function getStatusBadgeClass(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT => 'bg-secondary',
            self::STATUS_SENT => 'bg-info',
            self::STATUS_PARTIALLY_PAID => 'bg-warning',
            self::STATUS_PAID => 'bg-success',
            self::STATUS_CANCELLED => 'bg-danger',
            default => 'bg-secondary'
        };
    }

    /**
     * Retourne le montant total TTC formaté
     */
    public function getFormattedTotalTtc(): string
    {
        return number_format((float) $this->totalTtc, 0, ',', ' ');
    }

    /**
     * Calcule et met à jour la date d'échéance automatiquement
     * À appeler après modification de la date ou du délai
     */
    public function calculateDueDate(): void
    {
        if ($this->type === self::TYPE_INVOICE && $this->date && $this->paymentTerms) {
            $this->dueDate = (clone $this->date)->modify('+' . $this->paymentTerms . ' days');
        }
    }

    /**
     * Vérifie si la facture est en retard de paiement
     */
    public function isOverdue(): bool
    {
        if ($this->type !== self::TYPE_INVOICE) {
            return false;
        }

        if (!$this->dueDate) {
            return false;
        }

        if ($this->isFullyPaid()) {
            return false;
        }

        return $this->dueDate < new \DateTime();
    }

    /**
     * Retourne le nombre de jours de retard
     */
    public function getDaysOverdue(): int
    {
        if (!$this->isOverdue()) {
            return 0;
        }

        $now = new \DateTime();
        $interval = $now->diff($this->dueDate);
        return $interval->days;
    }

    /**
     * Retourne le nombre de jours avant l'échéance
     */
    public function getDaysUntilDue(): int
    {
        if (!$this->dueDate || $this->type !== self::TYPE_INVOICE) {
            return 0;
        }

        $now = new \DateTime();
        if ($this->dueDate < $now) {
            return 0;
        }

        $interval = $now->diff($this->dueDate);
        return $interval->days;
    }

}
