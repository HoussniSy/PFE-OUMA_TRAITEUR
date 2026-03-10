<?php

namespace App\Service;

use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\MailerInterface;

/**
 * Factory pour créer des instances Mailer spécifiques à chaque utilisateur
 * en utilisant leur configuration SMTP personnelle.
 */
class UserMailerFactory
{
    private EncryptionService $encryptionService;

    public function __construct(EncryptionService $encryptionService)
    {
        $this->encryptionService = $encryptionService;
    }

    /**
     * Crée un Mailer configuré avec les paramètres SMTP de l'utilisateur.
     */
    public function createMailerForUser($user): MailerInterface
    {
        if (!$user->hasCompleteSmtpConfig()) {
            throw new \RuntimeException('L\'utilisateur n\'a pas de configuration SMTP complète.');
        }

        $password = '';
        if ($user->getSmtpPassword()) {
            $password = $this->encryptionService->decrypt($user->getSmtpPassword());
        }

        $dsn = sprintf(
            'smtp://%s:%s@%s:%d?encryption=%s',
            urlencode($user->getSmtpUsername()),
            urlencode($password),
            $user->getSmtpHost(),
            $user->getSmtpPort(),
            $user->getSmtpEncryption() ?? 'tls'
        );

        $transport = Transport::fromDsn($dsn);
        return new Mailer($transport);
    }

    /**
     * Suggère une configuration SMTP basée sur le fournisseur d'email.
     *
     * @return array|null Configuration suggérée ou null si non reconnu
     */
    public function suggestSmtpConfig(string $email): ?array
    {
        $domain = strtolower(substr($email, strrpos($email, '@') + 1));

        $configs = [
            'gmail.com' => [
                'host' => 'smtp.gmail.com',
                'port' => 587,
                'encryption' => 'tls',
                'note' => 'Pour Gmail, vous devez utiliser un "mot de passe d\'application". Activez la validation en deux étapes puis créez un mot de passe d\'application dans les paramètres de sécurité Google.',
            ],
            'outlook.com' => [
                'host' => 'smtp-mail.outlook.com',
                'port' => 587,
                'encryption' => 'tls',
                'note' => 'Utilisez votre mot de passe Outlook ou un mot de passe d\'application si la vérification en deux étapes est activée.',
            ],
            'hotmail.com' => [
                'host' => 'smtp-mail.outlook.com',
                'port' => 587,
                'encryption' => 'tls',
                'note' => 'Utilisez votre mot de passe Hotmail ou un mot de passe d\'application.',
            ],
            'yahoo.com' => [
                'host' => 'smtp.mail.yahoo.com',
                'port' => 587,
                'encryption' => 'tls',
                'note' => 'Pour Yahoo, générez un mot de passe d\'application dans les paramètres de sécurité de votre compte.',
            ],
        ];

        return $configs[$domain] ?? null;
    }
}
