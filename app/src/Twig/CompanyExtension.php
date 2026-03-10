<?php

namespace App\Twig;

use App\Repository\CompanyRepository;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

/**
 * Extension Twig pour exposer les couleurs de l'entreprise dans tous les templates.
 */
class CompanyExtension extends AbstractExtension implements GlobalsInterface
{
    // Thèmes prédéfinis avec leurs couleurs principales
    private const THEME_COLORS = [
        'green' => '#00a651',
        'ocean' => '#0077b6',
        'sunset' => '#e07b39',
        'purple' => '#7c3aed',
        'red' => '#dc2626',
        'royal' => '#1d4ed8',
    ];

    public function __construct(
        private CompanyRepository $companyRepository
    ) {
    }

    public function getGlobals(): array
    {
        $company = $this->companyRepository->findFirst();

        if (!$company) {
            return [
                'companyPrimaryColor' => '#00a651',
                'companyDarkColor' => '#008040',
                'companyLightColor' => '#e6f9f0',
                'companyEntity' => null,
            ];
        }

        // Si un thème prédéfini est sélectionné, utiliser sa couleur
        $primaryColor = $company->getPrimaryColor() ?? '#00a651';
        if ($company->getColorTheme() && isset(self::THEME_COLORS[$company->getColorTheme()])) {
            $primaryColor = self::THEME_COLORS[$company->getColorTheme()];
        }

        return [
            'companyPrimaryColor' => $primaryColor,
            'companyDarkColor' => $this->darkenColor($primaryColor),
            'companyLightColor' => $this->lightenColor($primaryColor),
            'companyEntity' => $company,
        ];
    }

    /**
     * Assombrir une couleur hex de 30 unités par canal.
     */
    private function darkenColor(string $hex): string
    {
        $hex = ltrim($hex, '#');
        $r = max(0, hexdec(substr($hex, 0, 2)) - 30);
        $g = max(0, hexdec(substr($hex, 2, 2)) - 30);
        $b = max(0, hexdec(substr($hex, 4, 2)) - 30);
        return sprintf('#%02x%02x%02x', $r, $g, $b);
    }

    /**
     * Éclaircir une couleur hex (mélange 90% blanc).
     */
    private function lightenColor(string $hex): string
    {
        $hex = ltrim($hex, '#');
        $r = (int) (hexdec(substr($hex, 0, 2)) + (255 - hexdec(substr($hex, 0, 2))) * 0.9);
        $g = (int) (hexdec(substr($hex, 2, 2)) + (255 - hexdec(substr($hex, 2, 2))) * 0.9);
        $b = (int) (hexdec(substr($hex, 4, 2)) + (255 - hexdec(substr($hex, 4, 2))) * 0.9);
        return sprintf('#%02x%02x%02x', $r, $g, $b);
    }

    /**
     * Retourne la couleur pour un thème prédéfini donné.
     */
    public static function getThemeColor(string $theme): ?string
    {
        return self::THEME_COLORS[$theme] ?? null;
    }
}
