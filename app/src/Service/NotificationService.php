<?php

namespace App\Service;

use App\Entity\Document;
use App\Entity\Notification;
use App\Entity\Company;
use App\Repository\DocumentRepository;
use App\Repository\NotificationRepository;
use App\Repository\CompanyRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class NotificationService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private NotificationRepository $notificationRepository,
        private DocumentRepository $documentRepository,
        private CompanyRepository $companyRepository,
        private EmailService $emailService,
        private LoggerInterface $logger
    ) {}

    /**
     * Planifie toutes les notifications automatiques pour les factures
     * À appeler quotidiennement via cron
     */
    public function scheduleAutomaticNotifications(): int
    {
        $scheduled = 0;

        // 1. Notifications AVANT échéance (7 jours avant)
        $scheduled += $this->scheduleBeforeDueReminders();

        // 2. Notifications APRÈS échéance (7j, 15j, 30j)
        $scheduled += $this->scheduleOverdueReminders();

        return $scheduled;
    }

    /**
     * Planifie les rappels AVANT échéance (7 jours avant)
     */
    private function scheduleBeforeDueReminders(): int
    {
        $scheduled = 0;
        $sevenDaysFromNow = new \DateTimeImmutable('+7 days');

        // Récupérer toutes les factures non payées avec échéance dans 7 jours
        $invoices = $this->documentRepository->createQueryBuilder('d')
            ->where('d.type = :invoice')
            ->andWhere('d.status IN (:statuses)')
            ->andWhere('d.dueDate = :dueDate')
            ->setParameter('invoice', Document::TYPE_INVOICE)
            ->setParameter('statuses', [Document::STATUS_SENT, Document::STATUS_PARTIALLY_PAID])
            ->setParameter('dueDate', $sevenDaysFromNow->format('Y-m-d'))
            ->getQuery()
            ->getResult();

        foreach ($invoices as $invoice) {
            // Vérifier si une notification n'existe pas déjà
            if (!$this->notificationRepository->existsForDocument($invoice, Notification::TYPE_REMINDER_BEFORE_DUE)) {
                $this->createNotification(
                    $invoice,
                    Notification::TYPE_REMINDER_BEFORE_DUE,
                    new \DateTimeImmutable('tomorrow 09:00'),
                    null
                );
                $scheduled++;
            }
        }

        return $scheduled;
    }

    /**
     * Planifie les rappels APRÈS échéance (7j, 15j, 30j)
     */
    private function scheduleOverdueReminders(): int
    {
        $scheduled = 0;

        // Définir les paliers de rappel (7, 15, 30 jours après échéance)
        $reminderIntervals = [
            1 => 7,   // 1er rappel : 7 jours après échéance
            2 => 15,  // 2ème rappel : 15 jours après échéance
            3 => 30,  // 3ème rappel : 30 jours après échéance
        ];

        foreach ($reminderIntervals as $reminderNumber => $daysOverdue) {
            $targetDate = new \DateTimeImmutable('-' . $daysOverdue . ' days');

            // Récupérer les factures impayées avec échéance = targetDate
            $invoices = $this->documentRepository->createQueryBuilder('d')
                ->where('d.type = :invoice')
                ->andWhere('d.status IN (:statuses)')
                ->andWhere('d.dueDate = :dueDate')
                ->setParameter('invoice', Document::TYPE_INVOICE)
                ->setParameter('statuses', [Document::STATUS_SENT, Document::STATUS_PARTIALLY_PAID])
                ->setParameter('dueDate', $targetDate->format('Y-m-d'))
                ->getQuery()
                ->getResult();

            foreach ($invoices as $invoice) {
                // Vérifier si ce rappel n'existe pas déjà
                if (!$this->notificationRepository->existsForDocument(
                    $invoice,
                    Notification::TYPE_REMINDER_OVERDUE,
                    $reminderNumber
                )) {
                    $this->createNotification(
                        $invoice,
                        Notification::TYPE_REMINDER_OVERDUE,
                        new \DateTimeImmutable('tomorrow 09:00'),
                        $reminderNumber
                    );
                    $scheduled++;
                }
            }
        }

        return $scheduled;
    }

    /**
     * Crée une notification
     */
    public function createNotification(
        Document $document,
        string $type,
        \DateTimeImmutable $scheduledAt,
        ?int $reminderNumber = null,
        ?string $customMessage = null
    ): ?Notification {
        // Vérifier que le document a un client avec email
        if (!$document->getClient() || !$document->getClient()->getEmail()) {
            $this->logger->warning('Impossible de créer une notification : client sans email', [
                'document_id' => $document->getId(),
                'document_number' => $document->getNumber()
            ]);
            return null;
        }

        $notification = new Notification();
        $notification->setDocument($document);
        $notification->setType($type);
        $notification->setScheduledAt($scheduledAt);
        $notification->setRecipientEmail($document->getClient()->getEmail());
        $notification->setRecipientName($document->getClient()->getName());
        $notification->setReminderNumber($reminderNumber);

        if ($customMessage) {
            $notification->setMessage($customMessage);
        }

        $this->entityManager->persist($notification);
        $this->entityManager->flush();

        $this->logger->info('Notification créée', [
            'notification_id' => $notification->getId(),
            'document_id' => $document->getId(),
            'type' => $type,
            'scheduled_at' => $scheduledAt->format('Y-m-d H:i:s')
        ]);

        return $notification;
    }

    /**
     * Envoie toutes les notifications planifiées
     * À appeler régulièrement via cron
     */
    public function sendScheduledNotifications(): array
    {
        $results = [
            'sent' => 0,
            'failed' => 0,
            'errors' => []
        ];

        $notifications = $this->notificationRepository->findReadyToSend();

        foreach ($notifications as $notification) {
            try {
                $this->sendNotification($notification);
                $results['sent']++;
            } catch (\Exception $e) {
                $notification->markAsFailed($e->getMessage());
                $this->entityManager->flush();

                $results['failed']++;
                $results['errors'][] = [
                    'notification_id' => $notification->getId(),
                    'error' => $e->getMessage()
                ];

                $this->logger->error('Échec envoi notification', [
                    'notification_id' => $notification->getId(),
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $results;
    }

    /**
     * Envoie une notification spécifique
     */
    public function sendNotification(Notification $notification): void
    {
        $document = $notification->getDocument();
        $company = $this->companyRepository->findFirst();

        if (!$company) {
            throw new \Exception('Aucune entreprise configurée');
        }

        $message = $notification->getMessage() ?? $this->getDefaultMessage($notification);

        // Envoi selon le type de notification
        switch ($notification->getType()) {
            case Notification::TYPE_REMINDER_BEFORE_DUE:
                $this->sendBeforeDueReminder($document, $company, $notification, $message);
                break;

            case Notification::TYPE_REMINDER_OVERDUE:
                $this->sendOverdueReminder($document, $company, $notification, $message);
                break;

            case Notification::TYPE_REMINDER_MANUAL:
                $this->emailService->sendPaymentReminder(
                    $document,
                    $company,
                    $notification->getRecipientEmail(),
                    $notification->getRecipientName(),
                    $message
                );
                break;

            default:
                throw new \Exception('Type de notification inconnu: ' . $notification->getType());
        }

        // Marquer comme envoyée
        $notification->markAsSent();
        $this->entityManager->flush();

        $this->logger->info('Notification envoyée', [
            'notification_id' => $notification->getId(),
            'document_id' => $document->getId(),
            'type' => $notification->getType()
        ]);
    }

    /**
     * Envoie un rappel AVANT échéance
     */
    private function sendBeforeDueReminder(
        Document $document,
        Company $company,
        Notification $notification,
        string $message
    ): void {
        $subject = "Rappel : Échéance facture N° {$document->getNumber()} dans 7 jours";

        $body = "Bonjour,\n\n";
        $body .= "Nous vous informons que la facture N° {$document->getNumber()} ";
        $body .= "d'un montant de " . number_format((float) $document->getTotalTtc(), 0, ',', ' ') . " {$document->getCurrency()} ";
        $body .= "arrive à échéance le {$document->getDueDate()->format('d/m/Y')} (dans 7 jours).\n\n";

        if ($message) {
            $body .= $message . "\n\n";
        }

        $body .= "Merci de bien vouloir procéder au règlement avant cette date.\n\n";
        $body .= "Cordialement,\n{$company->getName()}";

        $this->emailService->sendNotification(
            $notification->getRecipientEmail(),
            $subject,
            $body,
            $company,
            $notification->getRecipientName()
        );
    }

    /**
     * Envoie un rappel APRÈS échéance (impayée)
     */
    private function sendOverdueReminder(
        Document $document,
        Company $company,
        Notification $notification,
        string $message
    ): void {
        $daysOverdue = $document->getDaysOverdue();
        $reminderNumber = $notification->getReminderNumber() ?? 1;

        $subject = "Rappel #{$reminderNumber} : Facture N° {$document->getNumber()} impayée";

        $body = "Bonjour,\n\n";
        $body .= "Nous constatons que la facture N° {$document->getNumber()} ";
        $body .= "d'un montant de " . number_format((float) $document->getTotalTtc(), 0, ',', ' ') . " {$document->getCurrency()} ";
        $body .= "n'a toujours pas été réglée.\n\n";
        $body .= "Échéance dépassée de {$daysOverdue} jour(s) (date d'échéance : {$document->getDueDate()->format('d/m/Y')}).\n\n";

        if ($message) {
            $body .= $message . "\n\n";
        }

        $body .= "Merci de procéder au règlement dans les plus brefs délais.\n\n";
        $body .= "Pour tout renseignement, n'hésitez pas à nous contacter au {$company->getPhone()}.\n\n";
        $body .= "Cordialement,\n{$company->getName()}";

        $this->emailService->sendNotification(
            $notification->getRecipientEmail(),
            $subject,
            $body,
            $company,
            $notification->getRecipientName()
        );
    }

    /**
     * Génère un message par défaut selon le type de notification
     */
    private function getDefaultMessage(Notification $notification): string
    {
        return match($notification->getType()) {
            Notification::TYPE_REMINDER_BEFORE_DUE =>
                "Nous vous rappelons que l'échéance de paiement approche.",
            Notification::TYPE_REMINDER_OVERDUE =>
                "Nous vous remercions de bien vouloir régulariser cette situation rapidement.",
            Notification::TYPE_REMINDER_MANUAL =>
                "Merci de procéder au règlement dans les meilleurs délais.",
            default => ""
        };
    }

    /**
     * Crée une notification manuelle (depuis l'interface)
     */
    public function createManualReminder(
        Document $document,
        ?string $customMessage = null
    ): ?Notification {
        return $this->createNotification(
            $document,
            Notification::TYPE_REMINDER_MANUAL,
            new \DateTimeImmutable(),
            null,
            $customMessage
        );
    }

    /**
     * Annule toutes les notifications en attente pour un document
     */
    public function cancelPendingNotifications(Document $document): int
    {
        $notifications = $this->notificationRepository->findByDocument($document);
        $cancelled = 0;

        foreach ($notifications as $notification) {
            if ($notification->getStatus() === Notification::STATUS_PENDING) {
                $this->entityManager->remove($notification);
                $cancelled++;
            }
        }

        if ($cancelled > 0) {
            $this->entityManager->flush();
        }

        return $cancelled;
    }

    /**
     * Nettoie les anciennes notifications (plus de 6 mois)
     */
    public function cleanOldNotifications(): int
    {
        return $this->notificationRepository->deleteOldNotifications();
    }

    /**
     * Obtient les statistiques des notifications
     */
    public function getStatistics(): array
    {
        $today = new \DateTimeImmutable();
        $thisMonth = new \DateTimeImmutable('first day of this month');
        $nextMonth = new \DateTimeImmutable('last day of this month');

        return [
            'pending' => count($this->notificationRepository->findBy(['status' => Notification::STATUS_PENDING])),
            'failed' => $this->notificationRepository->countFailed(),
            'sent_this_month' => $this->notificationRepository->countSentBetween($thisMonth, $nextMonth),
            'ready_to_send' => count($this->notificationRepository->findReadyToSend()),
        ];
    }
}
