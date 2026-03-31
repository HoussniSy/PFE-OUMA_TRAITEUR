<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
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
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;
use SymfonyCasts\Bundle\VerifyEmail\VerifyEmailHelperInterface;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function register(
        Request $request,
        UserPasswordHasherInterface $userPasswordHasher,
        EntityManagerInterface $entityManager,
        VerifyEmailHelperInterface $verifyEmailHelper,
        MailerInterface $mailer
    ): Response {
        // Si déjà connecté, rediriger
        if ($this->getUser()) {
            return $this->redirectToRoute('app_dashboard');
        }

        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                // Hash du mot de passe
                $user->setPassword(
                    $userPasswordHasher->hashPassword($user, $form->get('plainPassword')->getData())
                );

                // Génération du token de vérification
                $user->setEmailVerificationToken(bin2hex(random_bytes(32)));
                $user->setEmailVerificationTokenExpiresAt(new \DateTime('+24 hours'));

                $entityManager->persist($user);
                $entityManager->flush();

                // Génération du lien de vérification
                $signatureComponents = $verifyEmailHelper->generateSignature(
                    'app_verify_email',
                    (string) $user->getId(),
                    $user->getEmail(),
                    ['token' => $user->getEmailVerificationToken()]
                );

                // Envoi de l'email de vérification
                $email = (new TemplatedEmail())
                    ->from(new Address('noreply@oumatraiteur.mr', 'Ouma Traiteur'))
                    ->to($user->getEmail())
                    ->subject('Vérifiez votre adresse email')
                    ->htmlTemplate('registration/email_verification.html.twig')
                    ->context([
                        'user' => $user,
                        'signedUrl' => $signatureComponents->getSignedUrl(),
                        'expiresAt' => $user->getEmailVerificationTokenExpiresAt(),
                    ]);

                $mailer->send($email);

                $this->addFlash('success', 'Votre compte a été créé ! Un email de vérification vous a été envoyé.');
                return $this->redirectToRoute('app_login');

            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de l\'inscription : ' . $e->getMessage());
            }
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form,
        ]);
    }

    #[Route('/verify/email', name: 'app_verify_email')]
    public function verifyUserEmail(
        Request $request,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
        VerifyEmailHelperInterface $verifyEmailHelper
    ): Response {
        $userId = $request->query->get('id');

        if (!$userId) {
            $this->addFlash('error', 'Lien de vérification invalide.');
            return $this->redirectToRoute('app_register');
        }

        $user = $userRepository->find($userId);

        if (!$user) {
            $this->addFlash('error', 'Utilisateur non trouvé.');
            return $this->redirectToRoute('app_register');
        }

        // Vérifier si déjà vérifié
        if ($user->isVerified()) {
            $this->addFlash('info', 'Votre email est déjà vérifié. Vous pouvez vous connecter.');
            return $this->redirectToRoute('app_login');
        }

        try {
            $verifyEmailHelper->validateEmailConfirmation(
                $request->getUri(),
                (string) $user->getId(),
                $user->getEmail()
            );

            // Marquer l'email comme vérifié
            $user->setIsVerified(true);
            $user->setEmailVerificationToken(null);
            $user->setEmailVerificationTokenExpiresAt(null);

            $entityManager->flush();

            $this->addFlash('success', 'Votre adresse email a été vérifiée avec succès ! Vous pouvez maintenant vous connecter.');
            return $this->redirectToRoute('app_login');

        } catch (VerifyEmailExceptionInterface $e) {
            $this->addFlash('error', 'Le lien de vérification est invalide ou a expiré. Veuillez vous inscrire à nouveau.');
            return $this->redirectToRoute('app_register');
        }
    }

    #[Route('/verify/resend', name: 'app_resend_verification')]
    public function resendVerification(
        Request $request,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
        VerifyEmailHelperInterface $verifyEmailHelper,
        MailerInterface $mailer
    ): Response {
        $email = $request->request->get('email');

        if (!$email) {
            $this->addFlash('error', 'Veuillez fournir une adresse email.');
            return $this->redirectToRoute('app_login');
        }

        $user = $userRepository->findOneBy(['email' => $email]);

        if (!$user) {
            // Ne pas révéler si l'email existe ou non (sécurité)
            $this->addFlash('info', 'Si un compte existe avec cet email, un nouveau lien de vérification a été envoyé.');
            return $this->redirectToRoute('app_login');
        }

        if ($user->isVerified()) {
            $this->addFlash('info', 'Votre email est déjà vérifié.');
            return $this->redirectToRoute('app_login');
        }

        try {
            // Générer un nouveau token
            $user->setEmailVerificationToken(bin2hex(random_bytes(32)));
            $user->setEmailVerificationTokenExpiresAt(new \DateTime('+24 hours'));

            $entityManager->flush();

            // Générer le lien
            $signatureComponents = $verifyEmailHelper->generateSignature(
                'app_verify_email',
                (string) $user->getId(),
                $user->getEmail(),
                ['token' => $user->getEmailVerificationToken()]
            );

            // Envoyer l'email
            $email = (new TemplatedEmail())
                ->from(new Address('noreply@oumatraiteur.mr', 'Ouma Traiteur'))
                ->to($user->getEmail())
                ->subject('Vérifiez votre adresse email')
                ->htmlTemplate('registration/email_verification.html.twig')
                ->context([
                    'user' => $user,
                    'signedUrl' => $signatureComponents->getSignedUrl(),
                    'expiresAt' => $user->getEmailVerificationTokenExpiresAt(),
                ]);

            $mailer->send($email);

            $this->addFlash('success', 'Un nouveau lien de vérification a été envoyé à votre adresse email.');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de l\'envoi : ' . $e->getMessage());
        }

        return $this->redirectToRoute('app_login');
    }
}
