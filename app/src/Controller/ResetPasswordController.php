<?php

namespace App\Controller;

use App\Form\ResetPasswordFormType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[Route('/reset-password')]
class ResetPasswordController extends AbstractController
{
    #[Route('', name: 'app_forgot_password_request')]
    public function request(): Response
    {
        // Si déjà connecté, rediriger
        if ($this->getUser()) {
            return $this->redirectToRoute('app_dashboard');
        }

        return $this->render('reset_password/request.html.twig');
    }

    #[Route('/send', name: 'app_forgot_password_send', methods: ['POST'])]
    public function sendResetEmail(
        Request $request,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
        MailerInterface $mailer
    ): Response {
        $email = $request->request->get('email');

        if (!$email) {
            $this->addFlash('error', 'Veuillez fournir une adresse email.');
            return $this->redirectToRoute('app_forgot_password_request');
        }

        $user = $userRepository->findOneBy(['email' => $email]);

        // Ne pas révéler si l'email existe (sécurité)
        if (!$user) {
            $this->addFlash('info', 'Si un compte existe avec cet email, un lien de réinitialisation a été envoyé.');
            return $this->redirectToRoute('app_login');
        }

        try {
            // Générer un token unique
            $token = bin2hex(random_bytes(32));
            $user->setResetToken($token);
            $user->setResetTokenExpiresAt(new \DateTime('+1 hour'));

            $entityManager->flush();

            // Générer le lien de réinitialisation
            $resetUrl = $this->generateUrl(
                'app_reset_password',
                ['token' => $token],
                UrlGeneratorInterface::ABSOLUTE_URL
            );

            // Envoyer l'email
            $email = (new TemplatedEmail())
                ->from(new Address('noreply@oumatraiteur.mr', 'Ouma Traiteur'))
                ->to($user->getEmail())
                ->subject('Réinitialisation de votre mot de passe')
                ->htmlTemplate('reset_password/email.html.twig')
                ->context([
                    'user' => $user,
                    'resetUrl' => $resetUrl,
                    'expiresAt' => $user->getResetTokenExpiresAt(),
                ]);

            $mailer->send($email);

            $this->addFlash('success', 'Un email de réinitialisation a été envoyé. Vérifiez votre boîte mail.');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de l\'envoi : ' . $e->getMessage());
        }

        return $this->redirectToRoute('app_login');
    }

    #[Route('/reset/{token}', name: 'app_reset_password')]
    public function reset(
        Request $request,
        string $token,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        // Si déjà connecté, rediriger
        if ($this->getUser()) {
            return $this->redirectToRoute('app_dashboard');
        }

        // Trouver l'utilisateur avec ce token
        $user = $userRepository->findOneBy(['resetToken' => $token]);

        if (!$user) {
            $this->addFlash('error', 'Le lien de réinitialisation est invalide.');
            return $this->redirectToRoute('app_forgot_password_request');
        }

        // Vérifier si le token n'a pas expiré
        if ($user->getResetTokenExpiresAt() < new \DateTime()) {
            $this->addFlash('error', 'Le lien de réinitialisation a expiré. Veuillez en demander un nouveau.');

            // Nettoyer le token expiré
            $user->setResetToken(null);
            $user->setResetTokenExpiresAt(null);
            $entityManager->flush();

            return $this->redirectToRoute('app_forgot_password_request');
        }

        $form = $this->createForm(ResetPasswordFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                // Hasher le nouveau mot de passe
                $encodedPassword = $passwordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
                );

                $user->setPassword($encodedPassword);

                // Supprimer le token
                $user->setResetToken(null);
                $user->setResetTokenExpiresAt(null);

                $entityManager->flush();

                $this->addFlash('success', 'Votre mot de passe a été réinitialisé avec succès ! Vous pouvez maintenant vous connecter.');
                return $this->redirectToRoute('app_login');

            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de la réinitialisation : ' . $e->getMessage());
            }
        }

        return $this->render('reset_password/reset.html.twig', [
            'resetForm' => $form,
            'token' => $token,
        ]);
    }
}
