<?php

namespace App\Controller;

use App\Entity\Client;
use App\Entity\WhatsAppMessage;
use App\Form\WhatsAppMessageType;
use App\Repository\ClientRepository;
use App\Repository\WhatsAppMessageRepository;
use App\Service\WhatsAppService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/whatsapp')]
#[IsGranted('ROLE_USER')]
class WhatsAppController extends AbstractController
{
    public function __construct(
        private WhatsAppMessageRepository $whatsAppMessageRepository,
        private WhatsAppService $whatsAppService,
        private ClientRepository $clientRepository
    ) {
    }

    /**
     * Liste des messages WhatsApp envoyés
     */
    #[Route('', name: 'app_whatsapp_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $status = $request->query->get('status');
        $messageType = $request->query->get('type');
        $clientId = $request->query->get('client');

        $client = $clientId ? $this->clientRepository->find($clientId) : null;

        $messages = $this->whatsAppMessageRepository->findRecent($status, $messageType, $client);
        $statistics = $this->whatsAppService->getStatistics();
        $clients = $this->clientRepository->findBy([], ['name' => 'ASC']);

        return $this->render('whatsapp/index.html.twig', [
            'messages' => $messages,
            'statistics' => $statistics,
            'clients' => $clients,
            'currentStatus' => $status,
            'currentType' => $messageType,
            'currentClient' => $client,
        ]);
    }

    /**
     * Formulaire d'envoi de message texte WhatsApp
     */
    #[Route('/send', name: 'app_whatsapp_send', methods: ['GET', 'POST'])]
    public function send(Request $request): Response
    {
        $form = $this->createForm(WhatsAppMessageType::class, null, [
            'is_document' => false,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $clientEntity = $form->get('client')->getData();

            try {
                if ($clientEntity instanceof Client) {
                    $waMessage = $this->whatsAppService->sendMessage($clientEntity, $data['message']);
                } else {
                    $waMessage = $this->whatsAppService->sendMessageToNumber(
                        $data['recipientPhone'],
                        $data['recipientName'] ?? 'Inconnu',
                        $data['message']
                    );
                }

                if ($waMessage->getStatus() === WhatsAppMessage::STATUS_SIMULATED) {
                    $this->addFlash('warning', 'Message WhatsApp simulé (API non configurée). Le message a été enregistré.');
                } elseif ($waMessage->getStatus() === WhatsAppMessage::STATUS_SENT) {
                    $this->addFlash('success', 'Message WhatsApp envoyé à ' . $waMessage->getRecipientPhone());
                } else {
                    $this->addFlash('error', 'Erreur : ' . $waMessage->getErrorMessage());
                }
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur : ' . $e->getMessage());
            }

            return $this->redirectToRoute('app_whatsapp_index');
        }

        // Pré-remplir si un client est passé en paramètre
        $clientId = $request->query->get('client');
        if ($clientId && !$form->isSubmitted()) {
            $client = $this->clientRepository->find($clientId);
            if ($client) {
                $form->get('client')->setData($client);
                $form->get('recipientPhone')->setData($client->getPhone());
                $form->get('recipientName')->setData($client->getName());
            }
        }

        return $this->render('whatsapp/send.html.twig', [
            'form' => $form->createView(),
            'isConfigured' => $this->whatsAppService->isConfigured(),
            'isDocument' => false,
        ]);
    }

    /**
     * Formulaire d'envoi de document WhatsApp
     */
    #[Route('/send-document', name: 'app_whatsapp_send_document', methods: ['GET', 'POST'])]
    public function sendDocument(Request $request): Response
    {
        $form = $this->createForm(WhatsAppMessageType::class, null, [
            'is_document' => true,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $clientEntity = $form->get('client')->getData();

            /** @var UploadedFile $documentFile */
            $documentFile = $form->get('document')->getData();

            try {
                // Sauvegarder le fichier temporairement
                $uploadDir = $this->getParameter('kernel.project_dir') . '/var/uploads/whatsapp';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }

                $originalName = $documentFile->getClientOriginalName();
                $newFilename = uniqid() . '.' . $documentFile->guessExtension();
                $documentFile->move($uploadDir, $newFilename);

                $documentPath = $uploadDir . '/' . $newFilename;

                if ($clientEntity instanceof Client) {
                    $waMessage = $this->whatsAppService->sendDocument(
                        $clientEntity,
                        $documentPath,
                        $originalName,
                        $data['message'] ?? null
                    );
                } else {
                    // Pour un envoi sans client, créer un client temporaire ou utiliser directement
                    $tempClient = new Client();
                    $tempClient->setName($data['recipientName'] ?? 'Inconnu');
                    $tempClient->setPhone($data['recipientPhone']);

                    $waMessage = $this->whatsAppService->sendDocument(
                        $tempClient,
                        $documentPath,
                        $originalName,
                        $data['message'] ?? null
                    );
                    // Corriger le client null
                    $waMessage->setClient(null);
                }

                if ($waMessage->getStatus() === WhatsAppMessage::STATUS_SIMULATED) {
                    $this->addFlash('warning', 'Document WhatsApp simulé (API non configurée). Le message a été enregistré.');
                } elseif ($waMessage->getStatus() === WhatsAppMessage::STATUS_SENT) {
                    $this->addFlash('success', 'Document envoyé via WhatsApp à ' . $waMessage->getRecipientPhone());
                } else {
                    $this->addFlash('error', 'Erreur : ' . $waMessage->getErrorMessage());
                }
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur : ' . $e->getMessage());
            }

            return $this->redirectToRoute('app_whatsapp_index');
        }

        // Pré-remplir si un client est passé en paramètre
        $clientId = $request->query->get('client');
        if ($clientId && !$form->isSubmitted()) {
            $client = $this->clientRepository->find($clientId);
            if ($client) {
                $form->get('client')->setData($client);
                $form->get('recipientPhone')->setData($client->getPhone());
                $form->get('recipientName')->setData($client->getName());
            }
        }

        return $this->render('whatsapp/send.html.twig', [
            'form' => $form->createView(),
            'isConfigured' => $this->whatsAppService->isConfigured(),
            'isDocument' => true,
        ]);
    }

    /**
     * Détail d'un message WhatsApp
     */
    #[Route('/{id}', name: 'app_whatsapp_show', methods: ['GET'])]
    public function show(WhatsAppMessage $waMessage): Response
    {
        return $this->render('whatsapp/show.html.twig', [
            'waMessage' => $waMessage,
        ]);
    }
}
