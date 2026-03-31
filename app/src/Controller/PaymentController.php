<?php

namespace App\Controller;

use App\Entity\Document;
use App\Entity\Payment;
use App\Form\PaymentType;
use App\Repository\PaymentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/payment')]
#[IsGranted('ROLE_COMPTABLE')]
class PaymentController extends AbstractController
{
    #[Route('/document/{id}', name: 'app_payment_index', methods: ['GET'])]
    public function index(Document $document, PaymentRepository $paymentRepository): Response
    {
        $payments = $paymentRepository->findByDocument($document->getId());

        // Calcul des totaux
        $totalPaid = $document->getTotalPaid();
        $remainingAmount = $document->getRemainingAmount();

        return $this->render('payment/index.html.twig', [
            'document' => $document,
            'payments' => $payments,
            'totalPaid' => $totalPaid,
            'remainingAmount' => $remainingAmount,
        ]);
    }

    #[Route('/add', name: 'app_payment_add', methods: ['GET', 'POST'])]
    public function add(Request $request, EntityManagerInterface $entityManager): Response
    {
        $payment = new Payment();

        // Si un ID de document est passé en paramètre, le pré-remplir
        $documentId = $request->query->get('document');
        if ($documentId) {
            $document = $entityManager->getRepository(Document::class)->find($documentId);
            if ($document) {
                $payment->setDocument($document);
            }
        }

        $form = $this->createForm(PaymentType::class, $payment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $document = $payment->getDocument();

                // Validation : le montant ne doit pas dépasser le reste à payer
                $remainingAmount = $document->getRemainingAmount();
                $paymentAmount = (float) $payment->getMontant();

                if ($paymentAmount > $remainingAmount + 0.01) {
                    $this->addFlash('error', sprintf(
                        'Le montant du paiement (%.2f MRU) ne peut pas dépasser le reste à payer (%.2f MRU).',
                        $paymentAmount,
                        $remainingAmount
                    ));
                    return $this->render('payment/add.html.twig', [
                        'form' => $form,
                        'document' => $document,
                    ]);
                }

                $entityManager->persist($payment);
                $entityManager->flush();

                // Mettre à jour le statut du document automatiquement
                $document->updatePaymentStatus();
                $entityManager->flush();

                $this->addFlash('success', 'Le paiement a été ajouté avec succès.');
                return $this->redirectToRoute('app_document_show', ['id' => $payment->getDocument()->getId()]);
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de l\'ajout du paiement : ' . $e->getMessage());
            }
        }

        return $this->render('payment/add.html.twig', [
            'form' => $form,
            'document' => $payment->getDocument(),
        ]);
    }

    #[Route('/{id}/delete', name: 'app_payment_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        Payment $payment,
        EntityManagerInterface $entityManager
    ): Response {
        $documentId = $payment->getDocument()->getId();

        if ($this->isCsrfTokenValid('delete' . $payment->getId(), $request->request->get('_token'))) {
            try {
                $document = $payment->getDocument();

                $entityManager->remove($payment);
                $entityManager->flush();

                // Recalculer le statut du document automatiquement
                $document->updatePaymentStatus();
                $entityManager->flush();

                $this->addFlash('success', 'Le paiement a été supprimé avec succès.');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de la suppression : ' . $e->getMessage());
            }
        }

        return $this->redirectToRoute('app_document_show', ['id' => $documentId]);
    }
}
