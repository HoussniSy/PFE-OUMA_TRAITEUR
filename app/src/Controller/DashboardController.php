<?php

namespace App\Controller;

use App\Entity\DashboardWidget;
use App\Repository\ClientRepository;
use App\Repository\DashboardWidgetRepository;
use App\Repository\DocumentRepository;
use App\Repository\StockItemRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class DashboardController extends AbstractController
{
    #[Route('/', name: 'app_dashboard')]
    public function index(
        DocumentRepository $documentRepository,
        ClientRepository $clientRepository,
        DashboardWidgetRepository $widgetRepository,
        StockItemRepository $stockItemRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $user = $this->getUser();

        // Charger ou créer les widgets par défaut
        $widgets = $widgetRepository->findByUser($user);
        if (empty($widgets)) {
            $widgets = $this->createDefaultWidgets($user, $entityManager);
        }

        // Statistiques principales
        $totalClients = $clientRepository->count([]);
        $totalQuotes = $documentRepository->countByType('quote');
        $totalInvoices = $documentRepository->countByType('invoice');
        $monthlyRevenue = $documentRepository->getMonthlyRevenue((int) date('Y'));

        // Documents récents (10 derniers)
        $recentDocuments = $documentRepository->findRecentDocuments(10);

        // Devis en attente (5 derniers)
        $pendingQuotes = $documentRepository->findPendingQuotes(5);

        // Données pour le graphique (6 derniers mois)
        $monthlyData = $documentRepository->getMonthlyRevenueData(6);

        // Alertes stock faible
        $user = $this->getUser();
        $company = $user->getCompany();
        $lowStockItems = $company ? $stockItemRepository->findLowStockByCompany($company) : [];

        return $this->render('dashboard/index.html.twig', [
            'totalClients' => $totalClients,
            'totalQuotes' => $totalQuotes,
            'totalInvoices' => $totalInvoices,
            'monthlyRevenue' => $monthlyRevenue,
            'recentDocuments' => $recentDocuments,
            'pendingQuotes' => $pendingQuotes,
            'monthlyData' => $monthlyData,
            'widgets' => $widgets,
            'availableWidgets' => DashboardWidget::AVAILABLE_WIDGETS,
            'lowStockItems' => $lowStockItems,
        ]);
    }

    /**
     * Sauvegarde l'ordre des widgets via AJAX
     */
    #[Route('/dashboard/widgets/save', name: 'app_dashboard_widgets_save', methods: ['POST'])]
    public function saveWidgets(
        Request $request,
        DashboardWidgetRepository $widgetRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['widgets']) || !is_array($data['widgets'])) {
            return $this->json(['error' => 'Données invalides'], 400);
        }

        $user = $this->getUser();
        $widgets = $widgetRepository->findByUser($user);

        foreach ($data['widgets'] as $widgetData) {
            foreach ($widgets as $widget) {
                if ($widget->getWidgetType() === $widgetData['type']) {
                    $widget->setPosition((int) $widgetData['position']);
                    if (isset($widgetData['visible'])) {
                        $widget->setIsVisible((bool) $widgetData['visible']);
                    }
                    break;
                }
            }
        }

        $entityManager->flush();

        return $this->json(['success' => true, 'message' => 'Widgets sauvegardés']);
    }

    /**
     * Basculer la visibilité d'un widget
     */
    #[Route('/dashboard/widgets/toggle', name: 'app_dashboard_widgets_toggle', methods: ['POST'])]
    public function toggleWidget(
        Request $request,
        DashboardWidgetRepository $widgetRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['type'])) {
            return $this->json(['error' => 'Type de widget manquant'], 400);
        }

        $user = $this->getUser();
        $widgets = $widgetRepository->findByUser($user);

        foreach ($widgets as $widget) {
            if ($widget->getWidgetType() === $data['type']) {
                $widget->setIsVisible(!$widget->isVisible());
                $entityManager->flush();

                return $this->json([
                    'success' => true,
                    'visible' => $widget->isVisible(),
                ]);
            }
        }

        return $this->json(['error' => 'Widget non trouvé'], 404);
    }

    /**
     * Crée les widgets par défaut pour un nouvel utilisateur
     *
     * @return DashboardWidget[]
     */
    private function createDefaultWidgets(mixed $user, EntityManagerInterface $entityManager): array
    {
        $defaultWidgets = [
            DashboardWidget::TYPE_STATS,
            DashboardWidget::TYPE_QUICK_ACTIONS,
            DashboardWidget::TYPE_REVENUE_CHART,
            DashboardWidget::TYPE_PENDING_QUOTES,
            DashboardWidget::TYPE_RECENT_DOCUMENTS,
        ];

        $widgets = [];
        foreach ($defaultWidgets as $position => $type) {
            $widget = new DashboardWidget();
            $widget->setUser($user);
            $widget->setWidgetType($type);
            $widget->setPosition($position);
            $widget->setIsVisible(true);

            $entityManager->persist($widget);
            $widgets[] = $widget;
        }

        $entityManager->flush();

        return $widgets;
    }
}
