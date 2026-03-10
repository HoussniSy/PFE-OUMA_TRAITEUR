<?php

namespace App\Entity;

use App\Repository\PaymentRepository;
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
        new Post(security: "is_granted('ROLE_COMPTABLE')"),
        new Put(security: "is_granted('ROLE_COMPTABLE')"),
        new Delete(security: "is_granted('ROLE_ADMIN')"),
    ],
    normalizationContext: ['groups' => ['payment:read']],
    denormalizationContext: ['groups' => ['payment:write']],
)]
#[ORM\Entity(repositoryClass: PaymentRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Payment
{
    // Constantes pour les modes de paiement
    public const MODE_ESPECES = 'especes';
    public const MODE_CHEQUE = 'cheque';
    public const MODE_VIREMENT = 'virement';
    public const MODE_CARTE = 'carte';

    // Constantes pour les statuts de paiement
    public const STATUT_EN_ATTENTE = 'en_attente';
    public const STATUT_RECU = 'recu';
    public const STATUT_ANNULE = 'annule';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'payments')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotBlank(message: 'Le document est obligatoire.')]
    private ?Document $document = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Assert\NotBlank(message: 'La date de paiement est obligatoire.')]
    private ?\DateTimeInterface $datePaiement = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Assert\NotBlank(message: 'Le montant est obligatoire.')]
    #[Assert\Positive(message: 'Le montant doit être positif.')]
    private ?string $montant = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank(message: 'Le mode de paiement est obligatoire.')]
    #[Assert\Choice(
        choices: [self::MODE_ESPECES, self::MODE_CHEQUE, self::MODE_VIREMENT, self::MODE_CARTE],
        message: 'Mode de paiement invalide.'
    )]
    private ?string $modePaiement = self::MODE_ESPECES;

    #[ORM\Column(length: 50)]
    #[Assert\Choice(
        choices: [self::STATUT_EN_ATTENTE, self::STATUT_RECU, self::STATUT_ANNULE],
        message: 'Statut invalide.'
    )]
    private ?string $statutPaiement = self::STATUT_EN_ATTENTE;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $reference = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->datePaiement = new \DateTime();
    }

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

    public function getDatePaiement(): ?\DateTimeInterface
    {
        return $this->datePaiement;
    }

    public function setDatePaiement(\DateTimeInterface $datePaiement): static
    {
        $this->datePaiement = $datePaiement;
        return $this;
    }

    public function getMontant(): ?string
    {
        return $this->montant;
    }

    public function setMontant(string $montant): static
    {
        $this->montant = $montant;
        return $this;
    }

    public function getModePaiement(): ?string
    {
        return $this->modePaiement;
    }

    public function setModePaiement(string $modePaiement): static
    {
        $this->modePaiement = $modePaiement;
        return $this;
    }

    public function getStatutPaiement(): ?string
    {
        return $this->statutPaiement;
    }

    public function setStatutPaiement(string $statutPaiement): static
    {
        $this->statutPaiement = $statutPaiement;
        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): static
    {
        $this->notes = $notes;
        return $this;
    }

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function setReference(?string $reference): static
    {
        $this->reference = $reference;
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
     * Retourne le libellé du mode de paiement
     */
    public function getModePaiementLabel(): string
    {
        return match($this->modePaiement) {
            self::MODE_ESPECES => 'Espèces',
            self::MODE_CHEQUE => 'Chèque',
            self::MODE_VIREMENT => 'Virement bancaire',
            self::MODE_CARTE => 'Carte bancaire',
            default => $this->modePaiement
        };
    }

    /**
     * Retourne le libellé du statut
     */
    public function getStatutLabel(): string
    {
        return match($this->statutPaiement) {
            self::STATUT_EN_ATTENTE => 'En attente',
            self::STATUT_RECU => 'Reçu',
            self::STATUT_ANNULE => 'Annulé',
            default => $this->statutPaiement
        };
    }

    /**
     * Retourne la classe CSS du badge selon le statut
     */
    public function getStatutBadgeClass(): string
    {
        return match($this->statutPaiement) {
            self::STATUT_EN_ATTENTE => 'bg-warning',
            self::STATUT_RECU => 'bg-success',
            self::STATUT_ANNULE => 'bg-danger',
            default => 'bg-secondary'
        };
    }

    /**
     * Retourne le montant formaté
     */
    public function getFormattedMontant(): string
    {
        return number_format((float) $this->montant, 0, ',', ' ');
    }
}
