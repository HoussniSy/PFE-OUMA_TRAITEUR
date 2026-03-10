<?php

namespace App\Entity;

use App\Repository\CompanyRepository;
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
        new Post(security: "is_granted('ROLE_ADMIN')"),
        new Put(security: "is_granted('ROLE_ADMIN')"),
        new Delete(security: "is_granted('ROLE_ADMIN')"),
    ],
    normalizationContext: ['groups' => ['company:read']],
    denormalizationContext: ['groups' => ['company:write']],
)]
#[ORM\Entity(repositoryClass: CompanyRepository::class)]
class Company
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le nom de l'entreprise est obligatoire.")]
    #[Assert\Length(max: 255, maxMessage: "Le nom ne peut pas dépasser {{ limit }} caractères.")]
    #[Groups(['company:read', 'company:write'])]
    private ?string $name = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Length(max: 255, maxMessage: "Le nom en arabe ne peut pas dépasser {{ limit }} caractères.")]
    #[Groups(['company:read', 'company:write'])]
    private ?string $nameArabic = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le numéro d'enregistrement est obligatoire.")]
    #[Assert\Length(max: 255, maxMessage: "Le numéro d'enregistrement ne peut pas dépasser {{ limit }} caractères.")]
    #[Groups(['company:read', 'company:write'])]
    private ?string $registrationNumber = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le NIF est obligatoire.")]
    #[Assert\Length(max: 255, maxMessage: "Le NIF ne peut pas dépasser {{ limit }} caractères.")]
    #[Groups(['company:read', 'company:write'])]
    private ?string $nif = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le téléphone est obligatoire.")]
    #[Assert\Length(max: 255, maxMessage: "Le téléphone ne peut pas dépasser {{ limit }} caractères.")]
    private ?string $phone = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le nom de la banque est obligatoire.")]
    #[Assert\Length(max: 255, maxMessage: "Le nom de la banque ne peut pas dépasser {{ limit }} caractères.")]
    private ?string $bankName = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le numéro de compte bancaire est obligatoire.")]
    #[Assert\Length(max: 255, maxMessage: "Le numéro de compte bancaire ne peut pas dépasser {{ limit }} caractères.")]
    private ?string $bankAccount = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Length(max: 255, maxMessage: "L'adresse ne peut pas dépasser {{ limit }} caractères.")]
    private ?string $address = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $logo = null;

    // --- Personnalisation (Feature 27 & 28) ---
    #[ORM\Column(length: 7)]
    #[Groups(['company:read', 'company:write'])]
    private ?string $primaryColor = '#00a651';

    #[ORM\Column(length: 20, nullable: true)]
    #[Groups(['company:read', 'company:write'])]
    private ?string $colorTheme = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $logoQuote = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $logoInvoice = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    #[Groups(['company:read', 'company:write'])]
    private bool $quoteWatermark = false;

    // --- Paramètres par défaut ---
    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2)]
    #[Groups(['company:read', 'company:write'])]
    private ?string $defaultTaxRate = '16.00';

    #[ORM\Column(type: Types::INTEGER)]
    #[Groups(['company:read', 'company:write'])]
    private ?int $defaultPaymentTerms = 30;

    #[ORM\Column(length: 10)]
    #[Groups(['company:read', 'company:write'])]
    private ?string $defaultCurrency = 'MRU';

    // --- Relations multi-entreprises ---
    #[ORM\OneToMany(targetEntity: User::class, mappedBy: 'company')]
    private Collection $users;

    #[ORM\OneToMany(targetEntity: Document::class, mappedBy: 'company')]
    private Collection $documents;

    #[ORM\OneToMany(targetEntity: Client::class, mappedBy: 'company')]
    private Collection $clients;

    public function __construct()
    {
        $this->users = new ArrayCollection();
        $this->documents = new ArrayCollection();
        $this->clients = new ArrayCollection();
    }

    // --- Getters et Setters ---
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

    public function getNameArabic(): ?string
    {
        return $this->nameArabic;
    }

    public function setNameArabic(?string $nameArabic): static
    {
        $this->nameArabic = $nameArabic;
        return $this;
    }

    public function getRegistrationNumber(): ?string
    {
        return $this->registrationNumber;
    }

    public function setRegistrationNumber(string $registrationNumber): static
    {
        $this->registrationNumber = $registrationNumber;
        return $this;
    }

    public function getNif(): ?string
    {
        return $this->nif;
    }

    public function setNif(string $nif): static
    {
        $this->nif = $nif;
        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(string $phone): static
    {
        $this->phone = $phone;
        return $this;
    }

    public function getBankName(): ?string
    {
        return $this->bankName;
    }

    public function setBankName(string $bankName): static
    {
        $this->bankName = $bankName;
        return $this;
    }

    public function getBankAccount(): ?string
    {
        return $this->bankAccount;
    }

    public function setBankAccount(string $bankAccount): static
    {
        $this->bankAccount = $bankAccount;
        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address): static
    {
        $this->address = $address;
        return $this;
    }

    public function getLogo(): ?string
    {
        return $this->logo;
    }

    public function setLogo(?string $logo): static
    {
        $this->logo = $logo;
        return $this;
    }

    // --- Getters/Setters Paramètres par défaut ---
    public function getDefaultTaxRate(): ?string
    {
        return $this->defaultTaxRate;
    }

    public function setDefaultTaxRate(string $defaultTaxRate): static
    {
        $this->defaultTaxRate = $defaultTaxRate;
        return $this;
    }

    public function getDefaultPaymentTerms(): ?int
    {
        return $this->defaultPaymentTerms;
    }

    public function setDefaultPaymentTerms(int $defaultPaymentTerms): static
    {
        $this->defaultPaymentTerms = $defaultPaymentTerms;
        return $this;
    }

    public function getDefaultCurrency(): ?string
    {
        return $this->defaultCurrency;
    }

    public function setDefaultCurrency(string $defaultCurrency): static
    {
        $this->defaultCurrency = $defaultCurrency;
        return $this;
    }

    // --- Relations multi-entreprises ---
    /**
     * @return Collection<int, User>
     */
    public function getUsers(): Collection
    {
        return $this->users;
    }

    public function addUser(User $user): static
    {
        if (!$this->users->contains($user)) {
            $this->users->add($user);
            $user->setCompany($this);
        }
        return $this;
    }

    public function removeUser(User $user): static
    {
        if ($this->users->removeElement($user)) {
            if ($user->getCompany() === $this) {
                $user->setCompany(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, Document>
     */
    public function getDocuments(): Collection
    {
        return $this->documents;
    }

    /**
     * @return Collection<int, Client>
     */
    public function getClients(): Collection
    {
        return $this->clients;
    }

    // --- Getters/Setters Personnalisation ---
    public function getPrimaryColor(): ?string
    {
        return $this->primaryColor;
    }

    public function setPrimaryColor(string $primaryColor): static
    {
        $this->primaryColor = $primaryColor;
        return $this;
    }

    public function getColorTheme(): ?string
    {
        return $this->colorTheme;
    }

    public function setColorTheme(?string $colorTheme): static
    {
        $this->colorTheme = $colorTheme;
        return $this;
    }

    public function getLogoQuote(): ?string
    {
        return $this->logoQuote;
    }

    public function setLogoQuote(?string $logoQuote): static
    {
        $this->logoQuote = $logoQuote;
        return $this;
    }

    public function getLogoInvoice(): ?string
    {
        return $this->logoInvoice;
    }

    public function setLogoInvoice(?string $logoInvoice): static
    {
        $this->logoInvoice = $logoInvoice;
        return $this;
    }

    public function isQuoteWatermark(): bool
    {
        return $this->quoteWatermark;
    }

    public function setQuoteWatermark(bool $quoteWatermark): static
    {
        $this->quoteWatermark = $quoteWatermark;
        return $this;
    }

    /**
     * Retourne le logo adapté au type de document.
     * Fallback: logo spécifique > logo principal > null
     */
    public function getLogoForType(string $type): ?string
    {
        if ($type === 'quote' && $this->logoQuote) {
            return $this->logoQuote;
        }
        if ($type === 'invoice' && $this->logoInvoice) {
            return $this->logoInvoice;
        }
        return $this->logo;
    }

    /**
     * Calcule une couleur plus foncée à partir de la couleur principale.
     */
    public function getDarkColor(): string
    {
        $hex = ltrim($this->primaryColor ?? '#00a651', '#');
        $r = max(0, hexdec(substr($hex, 0, 2)) - 30);
        $g = max(0, hexdec(substr($hex, 2, 2)) - 30);
        $b = max(0, hexdec(substr($hex, 4, 2)) - 30);
        return sprintf('#%02x%02x%02x', $r, $g, $b);
    }

    /**
     * Calcule une couleur très claire (pour les backgrounds) à partir de la couleur principale.
     */
    public function getLightColor(): string
    {
        $hex = ltrim($this->primaryColor ?? '#00a651', '#');
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        // Mélanger avec du blanc à 90%
        $r = (int) ($r + (255 - $r) * 0.9);
        $g = (int) ($g + (255 - $g) * 0.9);
        $b = (int) ($b + (255 - $b) * 0.9);
        return sprintf('#%02x%02x%02x', $r, $g, $b);
    }

    // --- Méthodes utilitaires ---
    public function getLogoPath(): string
    {
        return '/uploads/logos/' . $this->logo;
    }

    public function getFullInfo(): string
    {
        $info = $this->name;
        if ($this->nameArabic) {
            $info .= " (" . $this->nameArabic . ")";
        }
        if ($this->address) {
            $info .= " - " . $this->address;
        }
        if ($this->phone) {
            $info .= " (Tel: " . $this->phone . ")";
        }
        return $info;
    }

    public function getBankInfo(): string
    {
        return $this->bankName . " - " . $this->bankAccount;
    }

    public function __toString(): string
    {
        return $this->name ?? '';
    }
}
