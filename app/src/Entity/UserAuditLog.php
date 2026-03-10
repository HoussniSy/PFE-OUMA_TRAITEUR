<?php

namespace App\Entity;

use App\Repository\UserAuditLogRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserAuditLogRepository::class)]
#[ORM\Table(name: 'user_audit_log')]
class UserAuditLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * L'utilisateur qui a effectué l'action (admin)
     */
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $performedBy = null;

    /**
     * L'utilisateur sur lequel l'action a été effectuée (peut être null si supprimé)
     */
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $targetUser = null;

    /**
     * Email de l'utilisateur cible (conservé même après suppression)
     */
    #[ORM\Column(length: 180)]
    private ?string $targetUserEmail = null;

    /**
     * Type d'action : created, updated, deleted, activated, deactivated, role_changed
     */
    #[ORM\Column(length: 50)]
    private ?string $action = null;

    /**
     * Détails de l'action (JSON)
     * Exemple: {"old_role": "ROLE_USER", "new_role": "ROLE_ADMIN"}
     */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $details = null;

    /**
     * Adresse IP de l'admin qui a effectué l'action
     */
    #[ORM\Column(length: 45, nullable: true)]
    private ?string $ipAddress = null;

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

    public function getPerformedBy(): ?User
    {
        return $this->performedBy;
    }

    public function setPerformedBy(?User $performedBy): static
    {
        $this->performedBy = $performedBy;
        return $this;
    }

    public function getTargetUser(): ?User
    {
        return $this->targetUser;
    }

    public function setTargetUser(?User $targetUser): static
    {
        $this->targetUser = $targetUser;
        return $this;
    }

    public function getTargetUserEmail(): ?string
    {
        return $this->targetUserEmail;
    }

    public function setTargetUserEmail(string $targetUserEmail): static
    {
        $this->targetUserEmail = $targetUserEmail;
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

    public function getDetails(): ?string
    {
        return $this->details;
    }

    public function setDetails(?string $details): static
    {
        $this->details = $details;
        return $this;
    }

    /**
     * Retourne les détails décodés depuis JSON
     */
    public function getDetailsArray(): array
    {
        if (!$this->details) {
            return [];
        }

        $decoded = json_decode($this->details, true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Définit les détails à partir d'un tableau (encodage JSON automatique)
     */
    public function setDetailsArray(array $details): static
    {
        $this->details = json_encode($details, JSON_UNESCAPED_UNICODE);
        return $this;
    }

    public function getIpAddress(): ?string
    {
        return $this->ipAddress;
    }

    public function setIpAddress(?string $ipAddress): static
    {
        $this->ipAddress = $ipAddress;
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
     * Retourne un libellé lisible de l'action
     */
    public function getActionLabel(): string
    {
        return match($this->action) {
            'created' => 'Création',
            'updated' => 'Modification',
            'deleted' => 'Suppression',
            'activated' => 'Activation',
            'deactivated' => 'Désactivation',
            'role_changed' => 'Changement de rôle',
            'password_reset' => 'Réinitialisation mot de passe',
            default => $this->action,
        };
    }

    /**
     * Retourne le badge HTML de l'action
     */
    public function getActionBadge(): string
    {
        return match($this->action) {
            'created' => '<span class="badge bg-success">Création</span>',
            'updated' => '<span class="badge bg-primary">Modification</span>',
            'deleted' => '<span class="badge bg-danger">Suppression</span>',
            'activated' => '<span class="badge bg-success">Activation</span>',
            'deactivated' => '<span class="badge bg-warning">Désactivation</span>',
            'role_changed' => '<span class="badge bg-info">Changement rôle</span>',
            'password_reset' => '<span class="badge bg-secondary">Reset MDP</span>',
            default => '<span class="badge bg-secondary">' . htmlspecialchars($this->action) . '</span>',
        };
    }

    /**
     * Retourne une description complète de l'action
     */
    public function getDescription(): string
    {
        $admin = $this->performedBy ? $this->performedBy->getFullName() : 'Admin';
        $target = $this->targetUserEmail;

        $description = match($this->action) {
            'created' => "$admin a créé l'utilisateur $target",
            'updated' => "$admin a modifié l'utilisateur $target",
            'deleted' => "$admin a supprimé l'utilisateur $target",
            'activated' => "$admin a activé le compte de $target",
            'deactivated' => "$admin a désactivé le compte de $target",
            'role_changed' => "$admin a changé le rôle de $target",
            'password_reset' => "$admin a réinitialisé le mot de passe de $target",
            default => "$admin a effectué une action sur $target",
        };

        // Ajouter les détails si disponibles
        $details = $this->getDetailsArray();
        if (!empty($details)) {
            if (isset($details['old_role']) && isset($details['new_role'])) {
                $description .= " de {$details['old_role']} à {$details['new_role']}";
            }
        }

        return $description;
    }
}
