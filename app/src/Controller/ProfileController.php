<?php

namespace App\Controller;

use App\Form\ChangePasswordType;
use App\Form\ProfileType;
use App\Service\AvatarUploadService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/profile')]
#[IsGranted('ROLE_USER')]
class ProfileController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
        private AvatarUploadService $avatarUploadService,
    ) {
    }

    /**
     * Affiche et permet de modifier le profil de l'utilisateur connecté
     */
    #[Route('', name: 'app_profile', methods: ['GET', 'POST'])]
    public function index(Request $request): Response
    {
        $user = $this->getUser();

        $form = $this->createForm(ProfileType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                // Upload de l'avatar si fourni
                $avatarFile = $form->get('avatarFile')->getData();
                if ($avatarFile) {
                    // Supprimer l'ancien avatar
                    if ($user->getAvatar()) {
                        $this->avatarUploadService->delete($user->getAvatar());
                    }

                    if ($this->avatarUploadService->isValidImage($avatarFile) &&
                        $this->avatarUploadService->isValidSize($avatarFile)) {
                        $avatarFilename = $this->avatarUploadService->upload($avatarFile);
                        $this->avatarUploadService->resize($avatarFilename, 200, 200);
                        $user->setAvatar($avatarFilename);
                    } else {
                        $this->addFlash('warning', 'L\'avatar n\'a pas pu être uploadé (format ou taille invalide).');
                    }
                }

                $this->entityManager->flush();

                $this->addFlash('success', 'Votre profil a été mis à jour avec succès.');

                return $this->redirectToRoute('app_profile');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de la mise à jour du profil: ' . $e->getMessage());
            }
        }

        return $this->render('profile/index.html.twig', [
            'form' => $form,
            'user' => $user,
        ]);
    }

    /**
     * Permet de changer le mot de passe
     */
    #[Route('/change-password', name: 'app_profile_change_password', methods: ['GET', 'POST'])]
    public function changePassword(Request $request): Response
    {
        $user = $this->getUser();

        $form = $this->createForm(ChangePasswordType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $currentPassword = $form->get('currentPassword')->getData();
                $newPassword = $form->get('newPassword')->getData();

                // Vérifier que le mot de passe actuel est correct
                if (!$this->passwordHasher->isPasswordValid($user, $currentPassword)) {
                    $this->addFlash('error', 'Le mot de passe actuel est incorrect.');
                    return $this->redirectToRoute('app_profile_change_password');
                }

                // Hasher et sauvegarder le nouveau mot de passe
                $hashedPassword = $this->passwordHasher->hashPassword($user, $newPassword);
                $user->setPassword($hashedPassword);
                $this->entityManager->flush();

                $this->addFlash('success', 'Votre mot de passe a été changé avec succès.');

                return $this->redirectToRoute('app_profile');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors du changement de mot de passe: ' . $e->getMessage());
            }
        }

        return $this->render('profile/change_password.html.twig', [
            'form' => $form,
        ]);
    }

    /**
     * Supprime l'avatar de l'utilisateur
     */
    #[Route('/delete-avatar', name: 'app_profile_delete_avatar', methods: ['POST'])]
    public function deleteAvatar(Request $request): Response
    {
        $user = $this->getUser();

        if ($this->isCsrfTokenValid('delete_avatar', $request->request->get('_token'))) {
            try {
                if ($user->getAvatar()) {
                    $this->avatarUploadService->delete($user->getAvatar());
                    $user->setAvatar(null);
                    $this->entityManager->flush();

                    $this->addFlash('success', 'Votre photo de profil a été supprimée.');
                } else {
                    $this->addFlash('warning', 'Vous n\'avez pas de photo de profil.');
                }
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de la suppression de la photo: ' . $e->getMessage());
            }
        }

        return $this->redirectToRoute('app_profile');
    }
}
