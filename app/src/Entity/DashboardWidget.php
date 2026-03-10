<?php

namespace App\Entity;

use App\Repository\DashboardWidgetRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Entité pour stocker la configuration des widgets du dashboard par utilisateur.
 * Chaque utilisateur peut personnaliser l'ordre et la visibilité des widgets.
 */
#[ORM\Entity(repositoryClass: DashboardWidgetRepository::class)]
#[ORM\Table(name: 'dashboard_widget')]
#[ORM\UniqueConstraint(columns: ['user_id', 'widget_type'])]
class DashboardWidget
{
    // Types de widgets disponibles
    public const TYPE_STATS = 'stats';
    public const TYPE_QUICK_ACTIONS = 'quick_actions';
    public const TYPE_REVENUE_CHART = 'revenue_chart';
    public const TYPE_PENDING_QUOTES = 'pending_quotes';
    public const TYPE_RECENT_DOCUMENTS = 'recent_documents';

    public const AVAILABLE_WIDGETS = [
        self::TYPE_STATS => [
            'label' => 'Statistiques',
            'icon' => 'bi-bar-chart-line',
            'description' => 'Cartes statistiques (clients, devis, factures, CA)',
        ],
        self::TYPE_QUICK_ACTIONS => [
            'label' => 'Actions rapides',
            'icon' => 'bi-lightning-charge',
            'description' => 'Boutons d\'actions rapides',
        ],
        self::TYPE_REVENUE_CHART => [
            'label' => 'Graphique CA',
            'icon' => 'bi-graph-up',
            'description' => 'Chiffre d\'affaires des 6 derniers mois',
        ],
        self::TYPE_PENDING_QUOTES => [
            'label' => 'Devis en attente',
            'icon' => 'bi-clock-history',
            'description' => 'Liste des devis en attente',
        ],
        self::TYPE_RECENT_DOCUMENTS => [
            'label' => 'Documents récents',
            'icon' => 'bi-clock',
            'description' => 'Les derniers documents créés',
        ],
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank]
    #[Assert\Choice(callback: 'getAvailableWidgetTypes')]
    private ?string $widgetType = null;

    #[ORM\Column(type: 'integer')]
    private int $position = 0;

    #[ORM\Column(type: 'boolean')]
    private bool $isVisible = true;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $config = null;

    // --- Getters et Setters ---

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

    public function getWidgetType(): ?string
    {
        return $this->widgetType;
    }

    public function setWidgetType(string $widgetType): static
    {
        $this->widgetType = $widgetType;
        return $this;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): static
    {
        $this->position = $position;
        return $this;
    }

    public function isVisible(): bool
    {
        return $this->isVisible;
    }

    public function setIsVisible(bool $isVisible): static
    {
        $this->isVisible = $isVisible;
        return $this;
    }

    public function getConfig(): ?array
    {
        return $this->config;
    }

    public function setConfig(?array $config): static
    {
        $this->config = $config;
        return $this;
    }

    // --- Méthodes utilitaires ---

    public function getLabel(): string
    {
        return self::AVAILABLE_WIDGETS[$this->widgetType]['label'] ?? $this->widgetType;
    }

    public function getIcon(): string
    {
        return self::AVAILABLE_WIDGETS[$this->widgetType]['icon'] ?? 'bi-square';
    }

    public function getDescription(): string
    {
        return self::AVAILABLE_WIDGETS[$this->widgetType]['description'] ?? '';
    }

    public static function getAvailableWidgetTypes(): array
    {
        return array_keys(self::AVAILABLE_WIDGETS);
    }
}
