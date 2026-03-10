<?php

namespace App\Service;

use App\Entity\Client;
use App\Entity\WhatsAppMessage;
use App\Repository\WhatsAppMessageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class WhatsAppService
{
    private string $apiToken;
    private string $phoneNumberId;
    private string $businessAccountId;
    private bool $isConfigured;

    private const API_BASE_URL = 'https://graph.facebook.com/v18.0';

    public function __construct(
        private EntityManagerInterface $entityManager,
        private WhatsAppMessageRepository $whatsAppMessageRepository,
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger,
        string $whatsappApiToken = '',
        string $whatsappPhoneNumberId = '',
        string $whatsappBusinessAccountId = ''
    ) {
        $this->apiToken = $whatsappApiToken;
        $this->phoneNumberId = $whatsappPhoneNumberId;
        $this->businessAccountId = $whatsappBusinessAccountId;
        $this->isConfigured = !empty($whatsappApiToken) && !empty($whatsappPhoneNumberId);
    }

    /**
     * Vérifie si le service WhatsApp est configuré
     */
    public function isConfigured(): bool
    {
        return $this->isConfigured;
    }

    /**
     * Envoie un message texte WhatsApp à un client
     */
    public function sendMessage(Client $client, string $message): WhatsAppMessage
    {
        $phone = $client->getPhone();

        if (!$phone) {
            throw new \InvalidArgumentException('Le client n\'a pas de numéro de téléphone.');
        }

        $phone = $this->normalizePhoneNumber($phone);

        $waMessage = new WhatsAppMessage();
        $waMessage->setClient($client);
        $waMessage->setRecipientPhone($phone);
        $waMessage->setRecipientName($client->getName());
        $waMessage->setMessage($message);
        $waMessage->setMessageType(WhatsAppMessage::TYPE_TEXT);

        if ($this->isConfigured) {
            $this->sendTextViaApi($waMessage);
        } else {
            $this->simulateSend($waMessage);
        }

        $this->entityManager->persist($waMessage);
        $this->entityManager->flush();

        return $waMessage;
    }

    /**
     * Envoie un message à un numéro directement (sans client)
     */
    public function sendMessageToNumber(string $phone, string $recipientName, string $message): WhatsAppMessage
    {
        $phone = $this->normalizePhoneNumber($phone);

        $waMessage = new WhatsAppMessage();
        $waMessage->setRecipientPhone($phone);
        $waMessage->setRecipientName($recipientName);
        $waMessage->setMessage($message);
        $waMessage->setMessageType(WhatsAppMessage::TYPE_TEXT);

        if ($this->isConfigured) {
            $this->sendTextViaApi($waMessage);
        } else {
            $this->simulateSend($waMessage);
        }

        $this->entityManager->persist($waMessage);
        $this->entityManager->flush();

        return $waMessage;
    }

    /**
     * Envoie un document via WhatsApp à un client
     */
    public function sendDocument(Client $client, string $documentPath, string $documentName, ?string $caption = null): WhatsAppMessage
    {
        $phone = $client->getPhone();

        if (!$phone) {
            throw new \InvalidArgumentException('Le client n\'a pas de numéro de téléphone.');
        }

        $phone = $this->normalizePhoneNumber($phone);

        $waMessage = new WhatsAppMessage();
        $waMessage->setClient($client);
        $waMessage->setRecipientPhone($phone);
        $waMessage->setRecipientName($client->getName());
        $waMessage->setMessage($caption);
        $waMessage->setMessageType(WhatsAppMessage::TYPE_DOCUMENT);
        $waMessage->setDocumentPath($documentPath);
        $waMessage->setDocumentName($documentName);

        if ($this->isConfigured) {
            $this->sendDocumentViaApi($waMessage);
        } else {
            $this->simulateSend($waMessage);
        }

        $this->entityManager->persist($waMessage);
        $this->entityManager->flush();

        return $waMessage;
    }

    /**
     * Envoi message texte via l'API Meta Cloud
     */
    private function sendTextViaApi(WhatsAppMessage $waMessage): void
    {
        try {
            $url = sprintf('%s/%s/messages', self::API_BASE_URL, $this->phoneNumberId);

            $response = $this->httpClient->request('POST', $url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiToken,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'messaging_product' => 'whatsapp',
                    'recipient_type' => 'individual',
                    'to' => $this->stripPlus($waMessage->getRecipientPhone()),
                    'type' => 'text',
                    'text' => [
                        'preview_url' => false,
                        'body' => $waMessage->getMessage(),
                    ],
                ],
            ]);

            $data = $response->toArray();
            $wamid = $data['messages'][0]['id'] ?? null;

            $waMessage->markAsSent($wamid);

            $this->logger->info('Message WhatsApp envoyé', [
                'to' => $waMessage->getRecipientPhone(),
                'wamid' => $wamid,
            ]);
        } catch (\Exception $e) {
            $waMessage->markAsFailed($e->getMessage());

            $this->logger->error('Échec envoi WhatsApp', [
                'to' => $waMessage->getRecipientPhone(),
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Envoi document via l'API Meta Cloud
     */
    private function sendDocumentViaApi(WhatsAppMessage $waMessage): void
    {
        try {
            $url = sprintf('%s/%s/messages', self::API_BASE_URL, $this->phoneNumberId);

            // D'abord uploader le document pour obtenir un media_id
            $mediaId = $this->uploadMedia($waMessage->getDocumentPath());

            $payload = [
                'messaging_product' => 'whatsapp',
                'recipient_type' => 'individual',
                'to' => $this->stripPlus($waMessage->getRecipientPhone()),
                'type' => 'document',
                'document' => [
                    'id' => $mediaId,
                    'filename' => $waMessage->getDocumentName(),
                ],
            ];

            if ($waMessage->getMessage()) {
                $payload['document']['caption'] = $waMessage->getMessage();
            }

            $response = $this->httpClient->request('POST', $url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiToken,
                    'Content-Type' => 'application/json',
                ],
                'json' => $payload,
            ]);

            $data = $response->toArray();
            $wamid = $data['messages'][0]['id'] ?? null;

            $waMessage->markAsSent($wamid);

            $this->logger->info('Document WhatsApp envoyé', [
                'to' => $waMessage->getRecipientPhone(),
                'document' => $waMessage->getDocumentName(),
                'wamid' => $wamid,
            ]);
        } catch (\Exception $e) {
            $waMessage->markAsFailed($e->getMessage());

            $this->logger->error('Échec envoi document WhatsApp', [
                'to' => $waMessage->getRecipientPhone(),
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Upload un fichier media vers WhatsApp
     */
    private function uploadMedia(string $filePath): string
    {
        $url = sprintf('%s/%s/media', self::API_BASE_URL, $this->phoneNumberId);

        $mimeType = mime_content_type($filePath) ?: 'application/octet-stream';

        $response = $this->httpClient->request('POST', $url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiToken,
            ],
            'body' => [
                'messaging_product' => 'whatsapp',
                'file' => fopen($filePath, 'r'),
                'type' => $mimeType,
            ],
        ]);

        $data = $response->toArray();

        if (!isset($data['id'])) {
            throw new \RuntimeException('Impossible d\'uploader le media WhatsApp.');
        }

        return $data['id'];
    }

    /**
     * Simulation d'envoi (quand l'API n'est pas configurée)
     */
    private function simulateSend(WhatsAppMessage $waMessage): void
    {
        $waMessage->markAsSimulated();

        $this->logger->info('WhatsApp simulé (API non configurée)', [
            'to' => $waMessage->getRecipientPhone(),
            'type' => $waMessage->getMessageType(),
            'message' => $waMessage->getMessage() ? substr($waMessage->getMessage(), 0, 50) . '...' : '[document]',
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
     * Supprime le + du numéro pour l'API WhatsApp
     */
    private function stripPlus(string $phone): string
    {
        return ltrim($phone, '+');
    }

    /**
     * Obtient les statistiques WhatsApp
     */
    public function getStatistics(): array
    {
        $counts = $this->whatsAppMessageRepository->countByStatus();

        return [
            'total' => array_sum($counts),
            'sent' => ($counts[WhatsAppMessage::STATUS_SENT] ?? 0)
                + ($counts[WhatsAppMessage::STATUS_DELIVERED] ?? 0)
                + ($counts[WhatsAppMessage::STATUS_READ] ?? 0),
            'simulated' => $counts[WhatsAppMessage::STATUS_SIMULATED] ?? 0,
            'failed' => $counts[WhatsAppMessage::STATUS_FAILED] ?? 0,
            'pending' => $counts[WhatsAppMessage::STATUS_PENDING] ?? 0,
            'this_month' => $this->whatsAppMessageRepository->countSentThisMonth(),
            'is_configured' => $this->isConfigured,
        ];
    }
}
