<?php

namespace App\Service;

use App\Entity\User;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

class UserEmailService
{
    public function __construct(
        private MailerInterface $mailer,
    ) {
    }

    /**
     * Envoie un email de bienvenue avec le mot de passe temporaire
     */
    public function sendWelcomeEmail(User $user, string $temporaryPassword): void
    {
        $email = (new TemplatedEmail())
            ->from(new Address('noreply@oumatraiteur.mr', 'Ouma Traiteur'))
            ->to(new Address($user->getEmail(), $user->getFullName()))
            ->subject('Bienvenue sur Ouma Traiteur - Votre compte a été créé')
            ->htmlTemplate('email/user_welcome.html.twig')
            ->context([
                'user' => $user,
                'temporaryPassword' => $temporaryPassword,
            ]);

        try {
            $this->mailer->send($email);
        } catch (\Exception $e) {
            // Log l'erreur mais ne bloque pas le processus
            error_log('Erreur envoi email bienvenue: ' . $e->getMessage());
        }
    }

    /**
     * Envoie un email de notification de désactivation de compte
     */
    public function sendAccountDisabledEmail(User $user): void
    {
        $email = (new TemplatedEmail())
            ->from(new Address('noreply@oumatraiteur.mr', 'Ouma Traiteur'))
            ->to(new Address($user->getEmail(), $user->getFullName()))
            ->subject('Votre compte Ouma Traiteur a été désactivé')
            ->htmlTemplate('email/user_disabled.html.twig')
            ->context([
                'user' => $user,
            ]);

        try {
            $this->mailer->send($email);
        } catch (\Exception $e) {
            error_log('Erreur envoi email désactivation: ' . $e->getMessage());
        }
    }

    /**
     * Envoie un email de notification d'activation de compte
     */
    public function sendAccountEnabledEmail(User $user): void
    {
        $email = (new TemplatedEmail())
            ->from(new Address('noreply@oumatraiteur.mr', 'Ouma Traiteur'))
            ->to(new Address($user->getEmail(), $user->getFullName()))
            ->subject('Votre compte Ouma Traiteur a été activé')
            ->htmlTemplate('email/user_enabled.html.twig')
            ->context([
                'user' => $user,
            ]);

        try {
            $this->mailer->send($email);
        } catch (\Exception $e) {
            error_log('Erreur envoi email activation: ' . $e->getMessage());
        }
    }

    /**
     * Envoie un email de notification de changement de rôle
     */
    public function sendRoleChangedEmail(User $user, string $oldRole, string $newRole): void
    {
        $email = (new TemplatedEmail())
            ->from(new Address('noreply@oumatraiteur.mr', 'Ouma Traiteur'))
            ->to(new Address($user->getEmail(), $user->getFullName()))
            ->subject('Votre rôle sur Ouma Traiteur a été modifié')
            ->htmlTemplate('email/user_role_changed.html.twig')
            ->context([
                'user' => $user,
                'oldRole' => $oldRole,
                'newRole' => $newRole,
            ]);

        try {
            $this->mailer->send($email);
        } catch (\Exception $e) {
            error_log('Erreur envoi email changement rôle: ' . $e->getMessage());
        }
    }

    /**
     * Envoie un email de notification de modification de profil
     */
    public function sendProfileUpdatedEmail(User $user): void
    {
        $email = (new TemplatedEmail())
            ->from(new Address('noreply@oumatraiteur.mr', 'Ouma Traiteur'))
            ->to(new Address($user->getEmail(), $user->getFullName()))
            ->subject('Votre profil Ouma Traiteur a été modifié')
            ->htmlTemplate('email/user_profile_updated.html.twig')
            ->context([
                'user' => $user,
            ]);

        try {
            $this->mailer->send($email);
        } catch (\Exception $e) {
            error_log('Erreur envoi email modification profil: ' . $e->getMessage());
        }
    }

    /**
     * Génère un mot de passe aléatoire sécurisé
     */
    public function generateTemporaryPassword(int $length = 12): string
    {
        $uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $lowercase = 'abcdefghijklmnopqrstuvwxyz';
        $numbers = '0123456789';
        $special = '!@#$%^&*';

        $all = $uppercase . $lowercase . $numbers . $special;

        $password = '';
        $password .= $uppercase[random_int(0, strlen($uppercase) - 1)];
        $password .= $lowercase[random_int(0, strlen($lowercase) - 1)];
        $password .= $numbers[random_int(0, strlen($numbers) - 1)];
        $password .= $special[random_int(0, strlen($special) - 1)];

        for ($i = 4; $i < $length; $i++) {
            $password .= $all[random_int(0, strlen($all) - 1)];
        }

        return str_shuffle($password);
    }
}
