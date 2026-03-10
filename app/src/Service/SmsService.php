<?php

namespace App\Service;

use App\Entity\Client;
use App\Entity\SmsMessage;
use App\Repository\SmsMessageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class SmsService
{
    private string $twilioAccountSid;
    private string $twilioAuthToken;
    private string $twilioFromNumber;
    private bool $isConfigured;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private SmsMessageRepository $smsMessageRepository,
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger,
        string $twilioAccountSid = '',
        string $twilioAuthToken = '',
        string $twilioFromNumber = ''
    ) {
        $this->twilioAccountSid = $twilioAccountSid;
        $this->twilioAuthToken = $twilioAuthToken;
        $this->twilioFromNumber = $twilioFromNumber;
        $this->isConfigured = !empty($twilioAccountSid) && !empty($twilioAuthToken) && !empty($twilioFromNumber);
    }

    /**
     * Vérifie si le service Twilio est configuré
     */
    public function isConfigured(): bool
    {
        return $this->isConfigured;
    }

    /**
     * Envoie un SMS à un client
     */
    public function sendSms(Client $client, string $message): SmsMessage
    {
        $phone = $client->getPhone();

        if (!$phone) {
            throw new \InvalidArgumentException('Le client n\'a pas de numéro de téléphone.');
        }

        // Normaliser le numéro de téléphone
        $phone = $this->normalizePhoneNumber($phone);

        // Créer l'enregistrement
        $smsMessage = new SmsMessage();
        $smsMessage->setClient($client);
        $smsMessage->setRecipientPhone($phone);
        $smsMessage->setRecipientName($client->getName());
        $smsMessage->setMessage($message);

        if ($this->isConfigured) {
            $this->sendViaTwilio($smsMessage);
        } else {
            // Mode simulation
            $this->simulateSend($smsMessage);
        }

        $this->entityManager->persist($smsMessage);
        $this->entityManager->flush();

        return $smsMessage;
    }

    /**
     * Envoie un SMS à un numéro directement (sans client)
     */
    public function sendSmsToNumber(string $phone, string $recipientName, string $message): SmsMessage
    {
        $phone = $this->normalizePhoneNumber($phone);

        $smsMessage = new SmsMessage();
        $smsMessage->setRecipientPhone($phone);
        $smsMessage->setRecipientName($recipientName);
        $smsMessage->setMessage($message);

        if ($this->isConfigured) {
            $this->sendViaTwilio($smsMessage);
        } else {
            $this->simulateSend($smsMessage);
        }

        $this->entityManager->persist($smsMessage);
        $this->entityManager->flush();

        return $smsMessage;
    }

    /**
     * Envoi réel via l'API Twilio
     */
    private function sendViaTwilio(SmsMessage $smsMessage): void
    {
        try {
            $url = sprintf(
                'https://api.twilio.com/2010-04-01/Accounts/%s/Messages.json',
                $this->twilioAccountSid
            );

            $response = $this->httpClient->request('POST', $url, [
                'auth_basic' => [$this->twilioAccountSid, $this->twilioAuthToken],
                'body' => [
                    'To' => $smsMessage->getRecipientPhone(),
                    'From' => $this->twilioFromNumber,
                    'Body' => $smsMessage->getMessage(),
                ],
            ]);

            $data = $response->toArray();

            $smsMessage->markAsSent($data['sid'] ?? null);
            $smsMessage->setSegmentCount($data['num_segments'] ?? null);

            $this->logger->info('SMS envoyé via Twilio', [
                'to' => $smsMessage->getRecipientPhone(),
                'sid' => $data['sid'] ?? 'unknown',
            ]);
        } catch (\Exception $e) {
            $smsMessage->markAsFailed($e->getMessage());

            $this->logger->error('Échec envoi SMS Twilio', [
                'to' => $smsMessage->getRecipientPhone(),
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Simulation d'envoi (quand Twilio n'est pas configuré)
     */
    private function simulateSend(SmsMessage $smsMessage): void
    {
        $smsMessage->markAsSimulated();

        $this->logger->info('SMS simulé (Twilio non configuré)', [
            'to' => $smsMessage->getRecipientPhone(),
            'message' => substr($smsMessage->getMessage(), 0, 50) . '...',
        ]);
    }

    /**
     * Normalise un numéro de téléphone
     */
    private function normalizePhoneNumber(string $phone): string
    {
        // Supprimer espaces, tirets, points
        $phone = preg_replace('/[\s\-\.]/', '', $phone);

        // Si pas de préfixe international, ajouter +222 (Mauritanie)
        if (!str_starts_with($phone, '+')) {
            $phone = '+222' . ltrim($phone, '0');
        }

        return $phone;
    }

    /**
     * Obtient les statistiques SMS
     */
    public function getStatistics(): array
    {
        $counts = $this->smsMessageRepository->countByStatus();

        return [
            'total' => array_sum($counts),
            'sent' => ($counts[SmsMessage::STATUS_SENT] ?? 0) + ($counts[SmsMessage::STATUS_DELIVERED] ?? 0),
            'simulated' => $counts[SmsMessage::STATUS_SIMULATED] ?? 0,
            'failed' => $counts[SmsMessage::STATUS_FAILED] ?? 0,
            'pending' => $counts[SmsMessage::STATUS_PENDING] ?? 0,
            'this_month' => $this->smsMessageRepository->countSentThisMonth(),
            'is_configured' => $this->isConfigured,
        ];
    }
}
