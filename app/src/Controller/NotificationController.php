<?php

namespace App\Controller;

use App\Entity\Notification;
use App\Repository\NotificationRepository;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/notification')]
#[IsGranted('ROLE_COMPTABLE')]
class NotificationController extends AbstractController
{
    public function __construct(
        private NotificationRepository $notificationRepository,
        private NotificationService $notificationService
    ) {}

    /**
     * Liste de toutes les notifications
     */
    #[Route('', name: 'app_notification_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        // Filtres
        $status = $request->query->get('status', 'all');
        $type = $request->query->get('type', 'all');
        $search = $request->query->get('search', '');

        // Construction de la requête
        $qb = $this->notificationRepository->createQueryBuilder('n')
            ->leftJoin('n.document', 'd')
            ->leftJoin('d.client', 'c')
            ->orderBy('n.scheduledAt', 'DESC');

        // Filtre par statut
        if ($status !== 'all') {
            $qb->andWhere('n.status = :status')
                ->setParameter('status', $status);
        }

        // Filtre par type
        if ($type !== 'all') {
            $qb->andWhere('n.type = :type')
                ->setParameter('type', $type);
        }

        // Recherche par numéro de document ou client
        if ($search) {
            $qb->andWhere('d.number LIKE :search OR c.name LIKE :search OR n.recipientEmail LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        $notifications = $qb->getQuery()->getResult();

        // Statistiques
        $stats = $this->notificationService->getStatistics();

        return $this->render('notification/index.html.twig', [
            'notifications' => $notifications,
            'stats' => $stats,
            'currentStatus' => $status,
            'currentType' => $type,
            'search' => $search,
        ]);
    }

    /**
     * Détails d'une notification
     */
    #[Route('/{id}', name: 'app_notification_show', methods: ['GET'])]
    public function show(Notification $notification): Response
    {
        return $this->render('notification/show.html.twig', [
            'notification' => $notification,
        ]);
    }

    /**
     * Renvoyer une notification échouée
     */
    #[Route('/{id}/retry', name: 'app_notification_retry', methods: ['POST'])]
    public function retry(
        Request $request,
        Notification $notification,
        EntityManagerInterface $entityManager
    ): Response {
        if (!$this->isCsrfTokenValid('retry' . $notification->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_notification_index');
        }

        // Vérifier que la notification est en échec
        if ($notification->getStatus() !== Notification::STATUS_FAILED) {
            $this->addFlash('warning', 'Seules les notifications en échec peuvent être renvoyées.');
            return $this->redirectToRoute('app_notification_show', ['id' => $notification->getId()]);
        }

        try {
            // Réinitialiser le statut
            $notification->setStatus(Notification::STATUS_PENDING);
            $notification->setScheduledAt(new \DateTimeImmutable());
            $notification->setErrorMessage(null);
            $entityManager->flush();

            // Tenter de renvoyer immédiatement
            $this->notificationService->sendNotification($notification);

            $this->addFlash('success', 'La notification a été renvoyée avec succès.');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors du renvoi : ' . $e->getMessage());
        }

        return $this->redirectToRoute('app_notification_show', ['id' => $notification->getId()]);
    }

    /**
     * Supprimer une notification
     */
    #[Route('/{id}/delete', name: 'app_notification_delete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(
        Request $request,
        Notification $notification,
        EntityManagerInterface $entityManager
    ): Response {
        if (!$this->isCsrfTokenValid('delete' . $notification->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_notification_index');
        }

        try {
            $entityManager->remove($notification);
            $entityManager->flush();

            $this->addFlash('success', 'La notification a été supprimée.');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de la suppression : ' . $e->getMessage());
        }

        return $this->redirectToRoute('app_notification_index');
    }

    /**
     * Planifier manuellement les notifications
     */
    #[Route('/action/schedule', name: 'app_notification_schedule', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function schedule(Request $request): Response
    {
        if (!$this->isCsrfTokenValid('schedule-notifications', $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_notification_index');
        }

        try {
            $scheduled = $this->notificationService->scheduleAutomaticNotifications();

            if ($scheduled > 0) {
                $this->addFlash('success', "{$scheduled} notification(s) planifiée(s) avec succès.");
            } else {
                $this->addFlash('info', 'Aucune nouvelle notification à planifier pour le moment.');
            }
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de la planification : ' . $e->getMessage());
        }

        return $this->redirectToRoute('app_notification_index');
    }

    /**
     * Envoyer toutes les notifications en attente
     */
    #[Route('/action/send-pending', name: 'app_notification_send_pending', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function sendPending(Request $request): Response
    {
        if (!$this->isCsrfTokenValid('send-notifications', $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_notification_index');
        }

        try {
            $results = $this->notificationService->sendScheduledNotifications();

            $message = '';
            if ($results['sent'] > 0) {
                $message .= "{$results['sent']} notification(s) envoyée(s). ";
            }
            if ($results['failed'] > 0) {
                $message .= "{$results['failed']} en échec.";
                $this->addFlash('warning', $message);
            } else if ($results['sent'] > 0) {
                $this->addFlash('success', $message);
            } else {
                $this->addFlash('info', 'Aucune notification à envoyer pour le moment.');
            }
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de l\'envoi : ' . $e->getMessage());
        }

        return $this->redirectToRoute('app_notification_index');
    }

    /**
     * Nettoyer les anciennes notifications
     */
    #[Route('/action/clean', name: 'app_notification_clean', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function clean(Request $request): Response
    {
        if (!$this->isCsrfTokenValid('clean-notifications', $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_notification_index');
        }

        try {
            $deleted = $this->notificationService->cleanOldNotifications();

            if ($deleted > 0) {
                $this->addFlash('success', "{$deleted} notification(s) supprimée(s).");
            } else {
                $this->addFlash('info', 'Aucune notification à nettoyer.');
            }
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors du nettoyage : ' . $e->getMessage());
        }

        return $this->redirectToRoute('app_notification_index');
    }
}
