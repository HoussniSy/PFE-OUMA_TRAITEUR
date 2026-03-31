<?php

namespace App\Entity;

use App\Repository\NotificationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: NotificationRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Notification
{
    // Types de notifications
    public const TYPE_REMINDER_BEFORE_DUE = 'reminder_before_due';
    public const TYPE_REMINDER_OVERDUE = 'reminder_overdue';
    public const TYPE_REMINDER_MANUAL = 'reminder_manual';

    // Statuts
    public const STATUS_PENDING = 'pending';
    public const STATUS_SENT = 'sent';
    public const STATUS_FAILED = 'failed';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Document::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Document $document = null;

    #[ORM\Column(length: 50)]
    private ?string $type = null;

    #[ORM\Column(length: 50)]
    private ?string $status = self::STATUS_PENDING;

    #[ORM\Column(length: 255)]
    private ?string $recipientEmail = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $recipientName = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $message = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $scheduledAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $sentAt = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $errorMessage = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?int $reminderNumber = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->status = self::STATUS_PENDING;
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

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;
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

    public function getRecipientEmail(): ?string
    {
        return $this->recipientEmail;
    }

    public function setRecipientEmail(string $recipientEmail): static
    {
        $this->recipientEmail = $recipientEmail;
        return $this;
    }

    public function getRecipientName(): ?string
    {
        return $this->recipientName;
    }

    public function setRecipientName(?string $recipientName): static
    {
        $this->recipientName = $recipientName;
        return $this;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(?string $message): static
    {
        $this->message = $message;
        return $this;
    }

    public function getScheduledAt(): ?\DateTimeImmutable
    {
        return $this->scheduledAt;
    }

    public function setScheduledAt(\DateTimeImmutable $scheduledAt): static
    {
        $this->scheduledAt = $scheduledAt;
        return $this;
    }

    public function getSentAt(): ?\DateTimeImmutable
    {
        return $this->sentAt;
    }

    public function setSentAt(?\DateTimeImmutable $sentAt): static
    {
        $this->sentAt = $sentAt;
        return $this;
    }

    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }

    public function setErrorMessage(?string $errorMessage): static
    {
        $this->errorMessage = $errorMessage;
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

    public function getReminderNumber(): ?int
    {
        return $this->reminderNumber;
    }

    public function setReminderNumber(?int $reminderNumber): static
    {
        $this->reminderNumber = $reminderNumber;
        return $this;
    }

    // --- Méthodes utilitaires ---

    /**
     * Marque la notification comme envoyée
     */
    public function markAsSent(): void
    {
        $this->status = self::STATUS_SENT;
        $this->sentAt = new \DateTimeImmutable();
        $this->errorMessage = null;
    }

    /**
     * Marque la notification comme échouée
     */
    public function markAsFailed(string $errorMessage): void
    {
        $this->status = self::STATUS_FAILED;
        $this->errorMessage = $errorMessage;
    }

    /**
     * Vérifie si la notification est prête à être envoyée
     */
    public function isReadyToSend(): bool
    {
        return $this->status === self::STATUS_PENDING
            && $this->scheduledAt <= new \DateTimeImmutable();
    }

    /**
     * Retourne le libellé du type de notification
     */
    public function getTypeLabel(): string
    {
        return match($this->type) {
            self::TYPE_REMINDER_BEFORE_DUE => 'Rappel avant échéance',
            self::TYPE_REMINDER_OVERDUE => 'Rappel facture impayée',
            self::TYPE_REMINDER_MANUAL => 'Rappel manuel',
            default => $this->type
        };
    }

    /**
     * Retourne le libellé du statut
     */
    public function getStatusLabel(): string
    {
        return match($this->status) {
            self::STATUS_PENDING => 'En attente',
            self::STATUS_SENT => 'Envoyé',
            self::STATUS_FAILED => 'Échec',
            default => $this->status
        };
    }

    /**
     * Retourne la couleur du badge selon le statut
     */
    public function getStatusBadgeClass(): string
    {
        return match($this->status) {
            self::STATUS_PENDING => 'bg-warning',
            self::STATUS_SENT => 'bg-success',
            self::STATUS_FAILED => 'bg-danger',
            default => 'bg-secondary'
        };
    }
}
