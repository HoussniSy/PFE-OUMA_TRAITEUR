<?php

namespace App\Entity;

use App\Repository\UserRepository;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    operations: [
        new GetCollection(security: "is_granted('ROLE_ADMIN')"),
        new Get(security: "is_granted('ROLE_ADMIN') or object == user"),
    ],
    normalizationContext: ['groups' => ['user:read']],
)]
#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[UniqueEntity(fields: ['email'], message: 'Un compte existe déjà avec cet email.')]
#[ORM\HasLifecycleCallbacks]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    public const ROLE_USER = 'ROLE_USER';
    public const ROLE_COMPTABLE = 'ROLE_COMPTABLE';
    public const ROLE_ADMIN = 'ROLE_ADMIN';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['user:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    #[Assert\NotBlank(message: 'L\'email ne peut pas être vide.')]
    #[Assert\Email(message: 'L\'email {{ value }} n\'est pas valide.')]
    #[Groups(['user:read'])]
    private ?string $email = null;

    #[ORM\Column]
    private array $roles = [];

    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'Le nom ne peut pas être vide.')]
    #[Assert\Length(
        min: 2,
        max: 100,
        minMessage: 'Le nom doit contenir au moins {{ limit }} caractères.',
        maxMessage: 'Le nom ne peut pas dépasser {{ limit }} caractères.'
    )]
    #[Groups(['user:read'])]
    private ?string $nom = null;

    #[ORM\Column(length: 100, nullable: true)]
    #[Assert\Length(max: 100, maxMessage: 'Le prénom ne peut pas dépasser {{ limit }} caractères.')]
    #[Groups(['user:read'])]
    private ?string $prenom = null;

    #[ORM\Column]
    private bool $isActive = true;

    #[ORM\Column]
    private bool $isVerified = false;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $emailVerificationToken = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $emailVerificationTokenExpiresAt = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $resetToken = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $resetTokenExpiresAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    // --- NOUVEAUX CHAMPS ---

    #[ORM\Column(length: 20, nullable: true)]
    #[Assert\Length(max: 20, maxMessage: 'Le téléphone ne peut pas dépasser {{ limit }} caractères.')]
    #[Groups(['user:read'])]
    private ?string $phone = null;

    #[ORM\Column(length: 100, nullable: true)]
    #[Assert\Length(max: 100, maxMessage: 'Le poste ne peut pas dépasser {{ limit }} caractères.')]
    #[Groups(['user:read'])]
    private ?string $poste = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['user:read'])]
    private ?string $avatar = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $lastLoginAt = null;

    #[ORM\ManyToOne(targetEntity: Company::class, inversedBy: 'users')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Company $company = null;

    // --- Champs SMTP pour envoi d'emails personnalisé ---

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $smtpHost = null;

    #[ORM\Column(nullable: true)]
    private ?int $smtpPort = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $smtpUsername = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $smtpPassword = null;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $smtpEncryption = null;

    #[ORM\Column]
    private bool $emailConfigured = false;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $emailConfiguredAt = null;

    /**
     * @var Collection<int, UserAuditLog>
     */
    #[ORM\OneToMany(targetEntity: UserAuditLog::class, mappedBy: 'performedBy')]
    private Collection $userAuditLogs;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->roles = [self::ROLE_USER];
        $this->userAuditLogs = new ArrayCollection();
    }

    // --- Getters et Setters de base ---
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;
        return $this;
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @return list<string>
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = self::ROLE_USER;
        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;
        return $this;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;
        return $this;
    }

    public function eraseCredentials(): void
    {
        // Effacer les données sensibles temporaires si nécessaire
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;
        return $this;
    }

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(?string $prenom): static
    {
        $this->prenom = $prenom;
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

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    // --- Méthodes pour la vérification d'email ---
    public function isVerified(): bool
    {
        return $this->isVerified;
    }

    public function setIsVerified(bool $isVerified): static
    {
        $this->isVerified = $isVerified;
        return $this;
    }

    public function getEmailVerificationToken(): ?string
    {
        return $this->emailVerificationToken;
    }

    public function setEmailVerificationToken(?string $emailVerificationToken): static
    {
        $this->emailVerificationToken = $emailVerificationToken;
        return $this;
    }

    public function getEmailVerificationTokenExpiresAt(): ?\DateTimeImmutable
    {
        return $this->emailVerificationTokenExpiresAt;
    }

    public function setEmailVerificationTokenExpiresAt(\DateTimeInterface|null $emailVerificationTokenExpiresAt): static
    {
        // Convertir DateTime en DateTimeImmutable si nécessaire
        if ($emailVerificationTokenExpiresAt instanceof \DateTime) {
            $emailVerificationTokenExpiresAt = \DateTimeImmutable::createFromMutable($emailVerificationTokenExpiresAt);
        }

        $this->emailVerificationTokenExpiresAt = $emailVerificationTokenExpiresAt;
        return $this;
    }

    // --- Méthodes pour la réinitialisation de mot de passe ---
    public function getResetToken(): ?string
    {
        return $this->resetToken;
    }

    public function setResetToken(?string $resetToken): static
    {
        $this->resetToken = $resetToken;
        return $this;
    }

    public function getResetTokenExpiresAt(): ?\DateTimeImmutable
    {
        return $this->resetTokenExpiresAt;
    }

    public function setResetTokenExpiresAt(\DateTimeInterface|null $resetTokenExpiresAt): static
    {
        // Convertir DateTime en DateTimeImmutable si nécessaire
        if ($resetTokenExpiresAt instanceof \DateTime) {
            $resetTokenExpiresAt = \DateTimeImmutable::createFromMutable($resetTokenExpiresAt);
        }

        $this->resetTokenExpiresAt = $resetTokenExpiresAt;
        return $this;
    }

    // --- GETTERS/SETTERS NOUVEAUX CHAMPS ---

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): static
    {
        $this->phone = $phone;
        return $this;
    }

    public function getPoste(): ?string
    {
        return $this->poste;
    }

    public function setPoste(?string $poste): static
    {
        $this->poste = $poste;
        return $this;
    }

    public function getAvatar(): ?string
    {
        return $this->avatar;
    }

    public function setAvatar(?string $avatar): static
    {
        $this->avatar = $avatar;
        return $this;
    }

    public function getLastLoginAt(): ?\DateTimeImmutable
    {
        return $this->lastLoginAt;
    }

    public function setLastLoginAt(?\DateTimeImmutable $lastLoginAt): static
    {
        $this->lastLoginAt = $lastLoginAt;
        return $this;
    }

    // --- Méthodes utilitaires ---
    public function getCompany(): ?Company
    {
        return $this->company;
    }

    public function setCompany(?Company $company): static
    {
        $this->company = $company;
        return $this;
    }

    public function getFullName(): string
    {
        return trim(($this->prenom ?? '') . ' ' . $this->nom);
    }

    public function hasRole(string $role): bool
    {
        return in_array($role, $this->getRoles(), true);
    }

    public function isAdmin(): bool
    {
        return $this->hasRole(self::ROLE_ADMIN);
    }

    public function isComptable(): bool
    {
        return $this->hasRole(self::ROLE_COMPTABLE);
    }

    /**
     * Retourne le badge HTML du rôle principal
     */
    public function getRoleBadge(): string
    {
        if ($this->isAdmin()) {
            return '<span class="badge bg-danger"><i class="bi bi-shield-fill-check"></i> Administrateur</span>';
        }
        if ($this->isComptable()) {
            return '<span class="badge bg-warning"><i class="bi bi-calculator"></i> Comptable</span>';
        }
        return '<span class="badge bg-info"><i class="bi bi-person"></i> Utilisateur</span>';
    }

    /**
     * Retourne le nom du rôle principal
     */
    public function getRoleName(): string
    {
        if ($this->isAdmin()) {
            return 'Administrateur';
        }
        if ($this->isComptable()) {
            return 'Comptable';
        }
        return 'Utilisateur';
    }

    /**
     * Retourne le chemin complet de l'avatar
     */
    public function getAvatarPath(): string
    {
        if ($this->avatar) {
            return '/uploads/avatars/' . $this->avatar;
        }
        // Avatar par défaut (initiales)
        return $this->getDefaultAvatar();
    }

    /**
     * Génère un avatar par défaut avec initiales
     */
    public function getDefaultAvatar(): string
    {
        // Utilise UI Avatars pour générer un avatar avec initiales
        return 'https://ui-avatars.com/api/?name=' . urlencode($this->getFullName()) . '&background=00a651&color=fff&size=128';
    }

    /**
     * @return Collection<int, UserAuditLog>
     */
    public function getUserAuditLogs(): Collection
    {
        return $this->userAuditLogs;
    }

    public function addUserAuditLog(UserAuditLog $userAuditLog): static
    {
        if (!$this->userAuditLogs->contains($userAuditLog)) {
            $this->userAuditLogs->add($userAuditLog);
            $userAuditLog->setPerformedBy($this);
        }

        return $this;
    }

    public function removeUserAuditLog(UserAuditLog $userAuditLog): static
    {
        if ($this->userAuditLogs->removeElement($userAuditLog)) {
            // set the owning side to null (unless already changed)
            if ($userAuditLog->getPerformedBy() === $this) {
                $userAuditLog->setPerformedBy(null);
            }
        }

        return $this;
    }

    public function __toString(): string
    {
        return $this->getFullName();
    }

    // --- SMTP Getters/Setters ---

    public function getSmtpHost(): ?string
    {
        return $this->smtpHost;
    }

    public function setSmtpHost(?string $smtpHost): static
    {
        $this->smtpHost = $smtpHost;
        return $this;
    }

    public function getSmtpPort(): ?int
    {
        return $this->smtpPort;
    }

    public function setSmtpPort(?int $smtpPort): static
    {
        $this->smtpPort = $smtpPort;
        return $this;
    }

    public function getSmtpUsername(): ?string
    {
        return $this->smtpUsername;
    }

    public function setSmtpUsername(?string $smtpUsername): static
    {
        $this->smtpUsername = $smtpUsername;
        return $this;
    }

    public function getSmtpPassword(): ?string
    {
        return $this->smtpPassword;
    }

    public function setSmtpPassword(?string $smtpPassword): static
    {
        $this->smtpPassword = $smtpPassword;
        return $this;
    }

    public function getSmtpEncryption(): ?string
    {
        return $this->smtpEncryption;
    }

    public function setSmtpEncryption(?string $smtpEncryption): static
    {
        $this->smtpEncryption = $smtpEncryption;
        return $this;
    }

    public function isEmailConfigured(): bool
    {
        return $this->emailConfigured;
    }

    public function setEmailConfigured(bool $emailConfigured): static
    {
        $this->emailConfigured = $emailConfigured;
        return $this;
    }

    public function getEmailConfiguredAt(): ?\DateTimeImmutable
    {
        return $this->emailConfiguredAt;
    }

    public function setEmailConfiguredAt(?\DateTimeImmutable $emailConfiguredAt): static
    {
        $this->emailConfiguredAt = $emailConfiguredAt;
        return $this;
    }

    /**
     * Vérifie si la configuration SMTP est complète.
     */
    public function hasCompleteSmtpConfig(): bool
    {
        return $this->smtpHost !== null
            && $this->smtpPort !== null
            && $this->smtpUsername !== null
            && $this->smtpPassword !== null;
    }
}

