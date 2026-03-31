<?php

namespace App\Controller\Api;

use App\Entity\Payment;
use App\Repository\DocumentRepository;
use App\Repository\PaymentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ApiPaymentController extends AbstractController
{
    private function serializePayment(Payment $payment): array
    {
        return [
            'id' => $payment->getId(),
            'datePaiement' => $payment->getDatePaiement()?->format('c'),
            'montant' => $payment->getMontant(),
            'modePaiement' => $payment->getModePaiement(),
            'modePaiementLabel' => $payment->getModePaiementLabel(),
            'statutPaiement' => $payment->getStatutPaiement(),
            'statutLabel' => $payment->getStatutLabel(),
            'reference' => $payment->getReference(),
            'notes' => $payment->getNotes(),
            'documentId' => $payment->getDocument()?->getId(),
            'documentNumber' => $payment->getDocument()?->getNumber(),
            'documentType' => $payment->getDocument()?->getType(),
            'clientName' => $payment->getDocument()?->getClient()?->getName(),
            'createdAt' => $payment->getCreatedAt()?->format('c'),
        ];
    }

    /**
     * Liste des paiements avec pagination.
     */
    #[Route('/api/v1/payments', name: 'api_payments_list', methods: ['GET'])]
    public function list(Request $request, PaymentRepository $paymentRepository): JsonResponse
    {
        $page = max(1, $request->query->getInt('page', 1));
        $limit = 20;
        $offset = ($page - 1) * $limit;

        $payments = $paymentRepository->findBy([], ['createdAt' => 'DESC'], $limit, $offset);
        $total = $paymentRepository->count([]);

        $data = array_map(fn(Payment $p) => $this->serializePayment($p), $payments);

        return $this->json([
            'data' => $data,
            'total' => $total,
            'page' => $page,
            'totalPages' => (int) ceil($total / $limit),
        ]);
    }

    /**
     * Obtenir un paiement spécifique.
     */
    #[Route('/api/v1/payments/{id}', name: 'api_payments_show', methods: ['GET'])]
    public function show(int $id, PaymentRepository $paymentRepository): JsonResponse
    {
        $payment = $paymentRepository->find($id);

        if (!$payment) {
            return $this->json(['message' => 'Paiement non trouvé.'], 404);
        }

        return $this->json($this->serializePayment($payment));
    }

    /**
     * Créer un paiement.
     */
    #[Route('/api/v1/payments', name: 'api_payments_create', methods: ['POST'])]
    public function create(
        Request $request,
        DocumentRepository $documentRepository,
        EntityManagerInterface $em,
        ValidatorInterface $validator
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['documentId'])) {
            return $this->json(['message' => 'Le document est obligatoire.'], 422);
        }

        $document = $documentRepository->find($data['documentId']);
        if (!$document) {
            return $this->json(['message' => 'Document non trouvé.'], 404);
        }

        $payment = new Payment();
        $payment->setDocument($document);
        $payment->setMontant($data['montant'] ?? '0.00');
        $payment->setModePaiement($data['modePaiement'] ?? Payment::MODE_ESPECES);
        $payment->setStatutPaiement($data['statutPaiement'] ?? Payment::STATUT_EN_ATTENTE);
        $payment->setReference($data['reference'] ?? null);
        $payment->setNotes($data['notes'] ?? null);

        if (isset($data['datePaiement'])) {
            $payment->setDatePaiement(new \DateTime($data['datePaiement']));
        }

        $errors = $validator->validate($payment);
        if (count($errors) > 0) {
            $messages = [];
            foreach ($errors as $error) {
                $messages[$error->getPropertyPath()] = $error->getMessage();
            }
            return $this->json(['errors' => $messages], 422);
        }

        $em->persist($payment);

        // Mettre à jour le statut du document
        $document->updatePaymentStatus();

        $em->flush();

        return $this->json($this->serializePayment($payment), 201);
    }
}
