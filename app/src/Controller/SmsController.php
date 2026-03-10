<?php

namespace App\Controller;

use App\Entity\Client;
use App\Entity\SmsMessage;
use App\Form\SmsType;
use App\Repository\ClientRepository;
use App\Repository\SmsMessageRepository;
use App\Service\SmsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/sms')]
#[IsGranted('ROLE_USER')]
class SmsController extends AbstractController
{
    public function __construct(
        private SmsMessageRepository $smsMessageRepository,
        private SmsService $smsService,
        private ClientRepository $clientRepository
    ) {
    }

    /**
     * Liste des SMS envoyés
     */
    #[Route('', name: 'app_sms_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $status = $request->query->get('status');
        $clientId = $request->query->get('client');

        $client = $clientId ? $this->clientRepository->find($clientId) : null;

        $messages = $this->smsMessageRepository->findRecent($status, $client);
        $statistics = $this->smsService->getStatistics();
        $clients = $this->clientRepository->findBy([], ['name' => 'ASC']);

        return $this->render('sms/index.html.twig', [
            'messages' => $messages,
            'statistics' => $statistics,
            'clients' => $clients,
            'currentStatus' => $status,
            'currentClient' => $client,
        ]);
    }

    /**
     * Formulaire d'envoi de SMS
     */
    #[Route('/send', name: 'app_sms_send', methods: ['GET', 'POST'])]
    public function send(Request $request): Response
    {
        $form = $this->createForm(SmsType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $clientEntity = $form->get('client')->getData();

            try {
                if ($clientEntity instanceof Client) {
                    // Envoyer au client sélectionné
                    $smsMessage = $this->smsService->sendSms($clientEntity, $data['message']);
                } else {
                    // Envoyer directement au numéro
                    $smsMessage = $this->smsService->sendSmsToNumber(
                        $data['recipientPhone'],
                        $data['recipientName'] ?? 'Inconnu',
                        $data['message']
                    );
                }

                if ($smsMessage->getStatus() === SmsMessage::STATUS_SIMULATED) {
                    $this->addFlash('warning', 'SMS simulé (Twilio non configuré). Le message a été enregistré en base de données.');
                } elseif ($smsMessage->getStatus() === SmsMessage::STATUS_SENT) {
                    $this->addFlash('success', 'SMS envoyé avec succès à ' . $smsMessage->getRecipientPhone());
                } else {
                    $this->addFlash('error', 'Erreur lors de l\'envoi : ' . $smsMessage->getErrorMessage());
                }
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur : ' . $e->getMessage());
            }

            return $this->redirectToRoute('app_sms_index');
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

        return $this->render('sms/send.html.twig', [
            'form' => $form->createView(),
            'isConfigured' => $this->smsService->isConfigured(),
        ]);
    }

    /**
     * Détail d'un SMS
     */
    #[Route('/{id}', name: 'app_sms_show', methods: ['GET'])]
    public function show(SmsMessage $smsMessage): Response
    {
        return $this->render('sms/show.html.twig', [
            'smsMessage' => $smsMessage,
        ]);
    }
}
