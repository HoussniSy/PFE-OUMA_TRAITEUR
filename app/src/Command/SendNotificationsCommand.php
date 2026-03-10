<?php

namespace App\Command;

use App\Service\NotificationService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:send-notifications',
    description: 'Envoie les notifications planifiées (rappels de paiement)',
)]
class SendNotificationsCommand extends Command
{
    public function __construct(
        private NotificationService $notificationService
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('schedule', 's', InputOption::VALUE_NONE, 'Planifier automatiquement les notifications')
            ->addOption('send', null, InputOption::VALUE_NONE, 'Envoyer les notifications en attente')
            ->addOption('clean', 'c', InputOption::VALUE_NONE, 'Nettoyer les anciennes notifications')
            ->addOption('stats', null, InputOption::VALUE_NONE, 'Afficher les statistiques')
            ->setHelp(
                <<<'HELP'
                Cette commande gère l'envoi automatique des notifications de rappel de paiement.

                <info>Exemples d'utilisation :</info>

                # Planifier les notifications automatiques (à lancer quotidiennement)
                php bin/console app:send-notifications --schedule

                # Envoyer les notifications en attente
                php bin/console app:send-notifications --send

                # Tout en une fois (planifier + envoyer)
                php bin/console app:send-notifications --schedule --send

                # Nettoyer les anciennes notifications (>6 mois)
                php bin/console app:send-notifications --clean

                # Afficher les statistiques
                php bin/console app:send-notifications --stats

                <info>Configuration CRON recommandée :</info>

                # Chaque jour à 8h00 : planifier + envoyer
                0 8 * * * cd /path/to/project && php bin/console app:send-notifications --schedule --send

                # Chaque dimanche à 3h00 : nettoyage
                0 3 * * 0 cd /path/to/project && php bin/console app:send-notifications --clean
                HELP
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('🔔 Gestion des Notifications - Ouma Traiteur');

        $hasAction = false;

        // Option 1 : Planifier les notifications
        if ($input->getOption('schedule')) {
            $hasAction = true;
            $io->section('📅 Planification des notifications automatiques');

            try {
                $scheduled = $this->notificationService->scheduleAutomaticNotifications();

                if ($scheduled > 0) {
                    $io->success("✅ {$scheduled} notification(s) planifiée(s) avec succès");
                } else {
                    $io->info("ℹ️  Aucune nouvelle notification à planifier");
                }
            } catch (\Exception $e) {
                $io->error("❌ Erreur lors de la planification : " . $e->getMessage());
                return Command::FAILURE;
            }
        }

        // Option 2 : Envoyer les notifications en attente
        if ($input->getOption('send')) {
            $hasAction = true;
            $io->section('📧 Envoi des notifications planifiées');

            try {
                $results = $this->notificationService->sendScheduledNotifications();

                if ($results['sent'] > 0) {
                    $io->success("✅ {$results['sent']} notification(s) envoyée(s)");
                }

                if ($results['failed'] > 0) {
                    $io->warning("⚠️  {$results['failed']} notification(s) en échec");

                    if (!empty($results['errors'])) {
                        $io->section('Détails des erreurs :');
                        foreach ($results['errors'] as $error) {
                            $io->text("• Notification #{$error['notification_id']}: {$error['error']}");
                        }
                    }
                }

                if ($results['sent'] === 0 && $results['failed'] === 0) {
                    $io->info("ℹ️  Aucune notification à envoyer pour le moment");
                }
            } catch (\Exception $e) {
                $io->error("❌ Erreur lors de l'envoi : " . $e->getMessage());
                return Command::FAILURE;
            }
        }

        // Option 3 : Nettoyer les anciennes notifications
        if ($input->getOption('clean')) {
            $hasAction = true;
            $io->section('🧹 Nettoyage des anciennes notifications');

            try {
                $deleted = $this->notificationService->cleanOldNotifications();

                if ($deleted > 0) {
                    $io->success("✅ {$deleted} notification(s) supprimée(s)");
                } else {
                    $io->info("ℹ️  Aucune notification à nettoyer");
                }
            } catch (\Exception $e) {
                $io->error("❌ Erreur lors du nettoyage : " . $e->getMessage());
                return Command::FAILURE;
            }
        }

        // Option 4 : Statistiques
        if ($input->getOption('stats')) {
            $hasAction = true;
            $io->section('📊 Statistiques des notifications');

            try {
                $stats = $this->notificationService->getStatistics();

                $io->table(
                    ['Métrique', 'Valeur'],
                    [
                        ['En attente', $stats['pending']],
                        ['Prêtes à envoyer', $stats['ready_to_send']],
                        ['Envoyées ce mois', $stats['sent_this_month']],
                        ['En échec', $stats['failed']],
                    ]
                );
            } catch (\Exception $e) {
                $io->error("❌ Erreur lors de la récupération des stats : " . $e->getMessage());
                return Command::FAILURE;
            }
        }

        // Si aucune option n'est fournie, afficher l'aide
        if (!$hasAction) {
            $io->warning("⚠️  Aucune action spécifiée");
            $io->text([
                '',
                'Options disponibles :',
                '  --schedule  (-s)  : Planifier les notifications automatiques',
                '  --send            : Envoyer les notifications en attente',
                '  --clean     (-c)  : Nettoyer les anciennes notifications',
                '  --stats           : Afficher les statistiques',
                '',
                'Exemple : php bin/console app:send-notifications --schedule --send',
                '',
                'Pour plus d\'aide : php bin/console app:send-notifications --help',
            ]);
            return Command::INVALID;
        }

        $io->newLine();
        $io->text('✨ Opération terminée avec succès');

        return Command::SUCCESS;
    }
}
