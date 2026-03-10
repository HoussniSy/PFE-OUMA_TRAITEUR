<?php

namespace App\Controller;

use App\Form\UserEmailSettingsType;
use App\Service\EncryptionService;
use App\Service\UserMailerFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Mime\Email;

#[Route('/settings')]
#[IsGranted('ROLE_USER')]
class UserSettingsController extends AbstractController
{
    #[Route('/', name: 'app_user_settings', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('user_settings/index.html.twig', [
            'user' => $this->getUser(),
        ]);
    }

    #[Route('/email', name: 'app_user_settings_email', methods: ['GET', 'POST'])]
    public function emailSettings(
        Request $request,
        EntityManagerInterface $entityManager,
        EncryptionService $encryptionService,
        UserMailerFactory $mailerFactory
    ): Response {
        $user = $this->getUser();

        // Données initiales du formulaire
        $formData = [
            'smtpHost' => $user->getSmtpHost(),
            'smtpPort' => $user->getSmtpPort() ?? 587,
            'smtpUsername' => $user->getSmtpUsername() ?? $user->getEmail(),
            'smtpEncryption' => $user->getSmtpEncryption() ?? 'tls',
        ];

        $form = $this->createForm(UserEmailSettingsType::class, $formData);
        $form->handleRequest($request);

        // Suggestions de configuration automatique
        $suggestions = $mailerFactory->suggestSmtpConfig($user->getEmail());

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $data = $form->getData();

                // Mettre à jour les paramètres SMTP
                $user->setSmtpHost($data['smtpHost']);
                $user->setSmtpPort($data['smtpPort']);
                $user->setSmtpUsername($data['smtpUsername']);
                $user->setSmtpEncryption($data['smtpEncryption']);

                // Crypter et sauvegarder le mot de passe seulement s'il a été fourni
                $plainPassword = $form->get('smtpPassword')->getData();
                if (!empty($plainPassword)) {
                    $encryptedPassword = $encryptionService->encrypt($plainPassword);
                    $user->setSmtpPassword($encryptedPassword);
                }

                // Marquer comme configuré
                $user->setEmailConfigured(true);
                $user->setEmailConfiguredAt(new \DateTime());

                $entityManager->flush();

                $this->addFlash('success', 'Vos paramètres email ont été enregistrés avec succès.');
                return $this->redirectToRoute('app_user_settings_email');

            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de l\'enregistrement : ' . $e->getMessage());
            }
        }

        return $this->render('user_settings/email.html.twig', [
            'form' => $form,
            'user' => $user,
            'suggestions' => $suggestions,
            'hasConfig' => $user->hasCompleteSmtpConfig(),
        ]);
    }

    #[Route('/email/test', name: 'app_user_settings_email_test', methods: ['POST'])]
    public function testEmailConnection(
        UserMailerFactory $mailerFactory
    ): Response {
        $user = $this->getUser();

        if (!$user->hasCompleteSmtpConfig()) {
            $this->addFlash('error', 'Veuillez d\'abord configurer vos paramètres SMTP.');
            return $this->redirectToRoute('app_user_settings_email');
        }

        try {
            // Créer un mailer pour cet utilisateur
            $mailer = $mailerFactory->createMailerForUser($user);

            // Envoyer un email de test
            $email = (new Email())
                ->from($user->getSmtpUsername())
                ->to($user->getSmtpUsername())
                ->subject('Test de configuration SMTP - Ouma Traiteur')
                ->html($this->getTestEmailHtml($user));

            $mailer->send($email);

            $this->addFlash('success', 'Email de test envoyé avec succès ! Vérifiez votre boîte de réception.');

        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de l\'envoi de l\'email de test : ' . $e->getMessage());
        }

        return $this->redirectToRoute('app_user_settings_email');
    }

    #[Route('/email/auto-configure', name: 'app_user_settings_email_auto', methods: ['POST'])]
    public function autoConfigureEmail(
        Request $request,
        EntityManagerInterface $entityManager,
        UserMailerFactory $mailerFactory
    ): Response {
        $user = $this->getUser();
        $suggestions = $mailerFactory->suggestSmtpConfig($user->getEmail());

        if (!$suggestions) {
            $this->addFlash('warning', 'Aucune configuration automatique disponible pour votre fournisseur d\'email.');
            return $this->redirectToRoute('app_user_settings_email');
        }

        try {
            // Appliquer la configuration suggérée
            $user->setSmtpHost($suggestions['host']);
            $user->setSmtpPort($suggestions['port']);
            $user->setSmtpEncryption($suggestions['encryption']);
            $user->setSmtpUsername($user->getEmail());

            $entityManager->flush();

            $this->addFlash('success', 'Configuration automatique appliquée. Il ne vous reste plus qu\'à saisir votre mot de passe d\'application.');
            $this->addFlash('info', $suggestions['note']);

        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de la configuration automatique : ' . $e->getMessage());
        }

        return $this->redirectToRoute('app_user_settings_email');
    }

    #[Route('/email/reset', name: 'app_user_settings_email_reset', methods: ['POST'])]
    public function resetEmailSettings(
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        if (!$this->isCsrfTokenValid('reset-email', $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_user_settings_email');
        }

        $user = $this->getUser();

        try {
            // Réinitialiser tous les paramètres SMTP
            $user->setSmtpHost(null);
            $user->setSmtpPort(null);
            $user->setSmtpUsername(null);
            $user->setSmtpPassword(null);
            $user->setSmtpEncryption(null);
            $user->setEmailConfigured(false);
            $user->setEmailConfiguredAt(null);

            $entityManager->flush();

            $this->addFlash('success', 'Vos paramètres email ont été réinitialisés.');

        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de la réinitialisation : ' . $e->getMessage());
        }

        return $this->redirectToRoute('app_user_settings_email');
    }

    /**
     * Génère le contenu HTML de l'email de test
     */
    private function getTestEmailHtml($user): string
    {
        return sprintf('
            <!DOCTYPE html>
            <html lang="fr">
            <head>
                <meta charset="UTF-8">
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { background-color: #00a651; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
                    .content { background-color: #ffffff; padding: 30px; border: 1px solid #ddd; }
                    .success-badge { background-color: #28a745; color: white; padding: 10px 20px; border-radius: 5px; display: inline-block; margin: 20px 0; }
                    .info-box { background-color: #f8f9fa; padding: 15px; border-left: 4px solid #00a651; margin: 20px 0; }
                    .footer { background-color: #f5f5f5; padding: 15px; text-align: center; font-size: 12px; color: #666; border-radius: 0 0 8px 8px; }
                </style>
            </head>
            <body>
                <div class="container">
                    <div class="header">
                        <h1>🎉 Configuration Réussie !</h1>
                    </div>
                    <div class="content">
                        <div class="success-badge">
                            ✓ Votre configuration SMTP fonctionne parfaitement
                        </div>
                        <p>Bonjour <strong>%s</strong>,</p>
                        <p>Félicitations ! Vous avez correctement configuré vos paramètres d\'envoi d\'emails.</p>
                        <div class="info-box">
                            <strong>Détails de la configuration :</strong>
                            <ul>
                                <li><strong>Serveur :</strong> %s</li>
                                <li><strong>Port :</strong> %s</li>
                                <li><strong>Email :</strong> %s</li>
                                <li><strong>Cryptage :</strong> %s</li>
                            </ul>
                        </div>
                        <p>Vous pouvez maintenant envoyer des devis et factures directement depuis votre adresse email personnelle.</p>
                        <p>Cordialement,<br><strong>Ouma Traiteur</strong></p>
                    </div>
                    <div class="footer">
                        <p>Cet email a été envoyé automatiquement pour tester votre configuration SMTP.</p>
                        <p>Date : %s</p>
                    </div>
                </div>
            </body>
            </html>
        ',
            $user->getFullName(),
            $user->getSmtpHost(),
            $user->getSmtpPort(),
            $user->getSmtpUsername(),
            strtoupper($user->getSmtpEncryption() ?? 'TLS'),
            (new \DateTime())->format('d/m/Y à H:i:s')
        );
    }
}
