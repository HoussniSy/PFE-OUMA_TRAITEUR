<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\Request;

class FilterService
{
    /**
     * Retourne les raccourcis de période disponibles
     */
    public function getPeriodShortcuts(): array
    {
        return [
            'today' => 'Aujourd\'hui',
            'this_week' => 'Cette semaine',
            'this_month' => 'Ce mois',
            'this_year' => 'Cette année',
            'yesterday' => 'Hier',
            'last_week' => 'Semaine dernière',
            'last_month' => 'Mois dernier',
            'last_30_days' => '30 derniers jours',
            'last_90_days' => '90 derniers jours',
        ];
    }

    /**
     * Calcule les dates à partir d'un raccourci
     */
    public function getPeriodDatesFromShortcut(string $shortcut): array
    {
        $now = new \DateTime();

        return match($shortcut) {
            'today' => [
                'from' => (clone $now)->setTime(0, 0, 0),
                'to' => (clone $now)->setTime(23, 59, 59),
            ],
            'yesterday' => [
                'from' => (clone $now)->modify('-1 day')->setTime(0, 0, 0),
                'to' => (clone $now)->modify('-1 day')->setTime(23, 59, 59),
            ],
            'this_week' => [
                'from' => (clone $now)->modify('monday this week')->setTime(0, 0, 0),
                'to' => (clone $now)->modify('sunday this week')->setTime(23, 59, 59),
            ],
            'last_week' => [
                'from' => (clone $now)->modify('monday last week')->setTime(0, 0, 0),
                'to' => (clone $now)->modify('sunday last week')->setTime(23, 59, 59),
            ],
            'this_month' => [
                'from' => (clone $now)->modify('first day of this month')->setTime(0, 0, 0),
                'to' => (clone $now)->modify('last day of this month')->setTime(23, 59, 59),
            ],
            'last_month' => [
                'from' => (clone $now)->modify('first day of last month')->setTime(0, 0, 0),
                'to' => (clone $now)->modify('last day of last month')->setTime(23, 59, 59),
            ],
            'this_year' => [
                'from' => (clone $now)->setDate((int)$now->format('Y'), 1, 1)->setTime(0, 0, 0),
                'to' => (clone $now)->setDate((int)$now->format('Y'), 12, 31)->setTime(23, 59, 59),
            ],
            'last_30_days' => [
                'from' => (clone $now)->modify('-30 days')->setTime(0, 0, 0),
                'to' => (clone $now)->setTime(23, 59, 59),
            ],
            'last_90_days' => [
                'from' => (clone $now)->modify('-90 days')->setTime(0, 0, 0),
                'to' => (clone $now)->setTime(23, 59, 59),
            ],
            default => [
                'from' => null,
                'to' => null,
            ],
        };
    }

    /**
     * Extrait les paramètres de filtre depuis la requête
     */
    public function extractFiltersFromRequest(Request $request): array
    {
        $filters = [];

        // Filtres standards
        if ($request->query->has('type') && $request->query->get('type') !== 'all') {
            $filters['type'] = $request->query->get('type');
        }

        if ($request->query->has('status') && $request->query->get('status') !== 'all') {
            $filters['status'] = $request->query->get('status');
        }

        if ($request->query->has('search') && $request->query->get('search') !== '') {
            $filters['search'] = $request->query->get('search');
        }

        if ($request->query->has('client_id') && $request->query->get('client_id') !== 'all') {
            $filters['client_id'] = $request->query->get('client_id');
        }

        // Filtres de période
        if ($request->query->has('period_shortcut') && $request->query->get('period_shortcut') !== '') {
            $filters['period_shortcut'] = $request->query->get('period_shortcut');
        }

        if ($request->query->has('date_from') && $request->query->get('date_from') !== '') {
            $filters['date_from'] = $request->query->get('date_from');
        }

        if ($request->query->has('date_to') && $request->query->get('date_to') !== '') {
            $filters['date_to'] = $request->query->get('date_to');
        }

        // Filtres de montant
        if ($request->query->has('amount_min') && $request->query->get('amount_min') !== '') {
            $filters['amount_min'] = (float) $request->query->get('amount_min');
        }

        if ($request->query->has('amount_max') && $request->query->get('amount_max') !== '') {
            $filters['amount_max'] = (float) $request->query->get('amount_max');
        }

        return $filters;
    }

    /**
     * Valide les filtres et retourne les erreurs
     */
    public function validateFilters(array $filters): array
    {
        $errors = [];

        // Validation des dates
        if (isset($filters['date_from']) && isset($filters['date_to'])) {
            $dateFrom = new \DateTime($filters['date_from']);
            $dateTo = new \DateTime($filters['date_to']);

            if ($dateFrom > $dateTo) {
                $errors[] = 'La date de fin doit être supérieure ou égale à la date de début.';
            }
        }

        // Validation des montants
        if (isset($filters['amount_min']) && isset($filters['amount_max'])) {
            if ($filters['amount_min'] > $filters['amount_max']) {
                $errors[] = 'Le montant maximum doit être supérieur ou égal au montant minimum.';
            }
        }

        if (isset($filters['amount_min']) && $filters['amount_min'] < 0) {
            $errors[] = 'Le montant minimum ne peut pas être négatif.';
        }

        if (isset($filters['amount_max']) && $filters['amount_max'] < 0) {
            $errors[] = 'Le montant maximum ne peut pas être négatif.';
        }

        return $errors;
    }

    /**
     * Construit l'URL avec les paramètres de filtre
     */
    public function buildFilterUrl(string $baseRoute, array $filters): string
    {
        $params = [];

        foreach ($filters as $key => $value) {
            if ($value !== null && $value !== '' && $value !== 'all') {
                $params[$key] = $value;
            }
        }

        if (empty($params)) {
            return $baseRoute;
        }

        return $baseRoute . '?' . http_build_query($params);
    }

    /**
     * Compte le nombre de filtres actifs
     */
    public function countActiveFilters(array $filters): int
    {
        $count = 0;

        foreach ($filters as $value) {
            if ($value !== null && $value !== '' && $value !== 'all') {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Retourne un résumé textuel des filtres actifs
     */
    public function getFilterSummary(array $filters): string
    {
        $parts = [];

        if (isset($filters['type'])) {
            $parts[] = match($filters['type']) {
                'quote' => 'Devis',
                'invoice' => 'Factures',
                default => ucfirst($filters['type']),
            };
        }

        if (isset($filters['status'])) {
            $parts[] = match($filters['status']) {
                'draft' => 'Brouillon',
                'sent' => 'Envoyés',
                'paid' => 'Payés',
                'cancelled' => 'Annulés',
                default => ucfirst($filters['status']),
            };
        }

        if (isset($filters['period_shortcut'])) {
            $shortcuts = $this->getPeriodShortcuts();
            if (isset($shortcuts[$filters['period_shortcut']])) {
                $parts[] = $shortcuts[$filters['period_shortcut']];
            }
        } elseif (isset($filters['date_from']) || isset($filters['date_to'])) {
            $datePart = 'Période: ';
            if (isset($filters['date_from'])) {
                $datePart .= (new \DateTime($filters['date_from']))->format('d/m/Y');
            }
            $datePart .= ' → ';
            if (isset($filters['date_to'])) {
                $datePart .= (new \DateTime($filters['date_to']))->format('d/m/Y');
            }
            $parts[] = $datePart;
        }

        if (isset($filters['amount_min']) || isset($filters['amount_max'])) {
            $amountPart = 'Montant: ';
            if (isset($filters['amount_min'])) {
                $amountPart .= number_format($filters['amount_min'], 0, ',', ' ');
            }
            $amountPart .= ' → ';
            if (isset($filters['amount_max'])) {
                $amountPart .= number_format($filters['amount_max'], 0, ',', ' ');
            }
            $amountPart .= ' MRU';
            $parts[] = $amountPart;
        }

        if (isset($filters['search'])) {
            $parts[] = 'Recherche: "' . $filters['search'] . '"';
        }

        return implode(' • ', $parts);
    }
}
