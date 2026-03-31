<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\UserAuditLog;
use App\Form\UserType;
use App\Repository\UserRepository;
use App\Repository\UserAuditLogRepository;
use App\Service\AvatarUploadService;
use App\Service\UserEmailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/user')]
#[IsGranted('ROLE_ADMIN')]
class UserController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserRepository $userRepository,
        private UserAuditLogRepository $auditLogRepository,
        private UserPasswordHasherInterface $passwordHasher,
        private AvatarUploadService $avatarUploadService,
        private UserEmailService $emailService,
    ) {
    }

    /**
     * Liste tous les utilisateurs avec filtres et recherche
     */
    #[Route('', name: 'app_user_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $search = $request->query->get('search', '');
        $roleFilter = $request->query->get('role', 'all');
        $statusFilter = $request->query->get('status', 'all');

        $qb = $this->userRepository->createQueryBuilder('u')
            ->orderBy('u.createdAt', 'DESC');

        // Filtre de recherche
        if ($search) {
            $qb->andWhere('u.nom LIKE :search OR u.prenom LIKE :search OR u.email LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        // Filtre par rôle
        if ($roleFilter !== 'all') {
            $qb->andWhere('u.roles LIKE :role')
                ->setParameter('role', '%' . $roleFilter . '%');
        }

        // Filtre par statut
        if ($statusFilter === 'active') {
            $qb->andWhere('u.isActive = true');
        } elseif ($statusFilter === 'inactive') {
            $qb->andWhere('u.isActive = false');
        }

        $users = $qb->getQuery()->getResult();

        // Statistiques
        $totalUsers = $this->userRepository->count([]);
        $activeUsers = $this->userRepository->count(['isActive' => true]);
        $adminUsers = $this->userRepository->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->where('u.roles LIKE :role')
            ->setParameter('role', '%ROLE_ADMIN%')
            ->getQuery()
            ->getSingleScalarResult();

        // Journal d'audit récent
        $recentAuditLogs = $this->auditLogRepository->findRecent(10);

        return $this->render('user/index.html.twig', [
            'users' => $users,
            'search' => $search,
            'roleFilter' => $roleFilter,
            'statusFilter' => $statusFilter,
            'totalUsers' => $totalUsers,
            'activeUsers' => $activeUsers,
            'adminUsers' => $adminUsers,
            'recentAuditLogs' => $recentAuditLogs,
        ]);
    }

    /**
     * Affiche le formulaire de création d'un utilisateur
     */
    #[Route('/new', name: 'app_user_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $user = new User();
        $user->setIsActive(true); // Par défaut actif

        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                // Générer un mot de passe temporaire
                $temporaryPassword = $this->emailService->generateTemporaryPassword();
                $hashedPassword = $this->passwordHasher->hashPassword($user, $temporaryPassword);
                $user->setPassword($hashedPassword);

                // Upload de l'avatar si fourni
                $avatarFile = $form->get('avatarFile')->getData();
                if ($avatarFile) {
                    if ($this->avatarUploadService->isValidImage($avatarFile) &&
                        $this->avatarUploadService->isValidSize($avatarFile)) {
                        $avatarFilename = $this->avatarUploadService->upload($avatarFile);
                        $this->avatarUploadService->resize($avatarFilename, 200, 200);
                        $user->setAvatar($avatarFilename);
                    } else {
                        $this->addFlash('warning', 'L\'avatar n\'a pas pu être uploadé (format ou taille invalide).');
                    }
                }

                $this->entityManager->persist($user);
                $this->entityManager->flush();

                // Enregistrer dans le journal d'audit
                $this->auditLogRepository->logAction(
                    performedBy: $this->getUser(),
                    targetUser: $user,
                    targetUserEmail: $user->getEmail(),
                    action: 'created',
                    details: ['role' => $user->getRoleName()],
                    ipAddress: $request->getClientIp()
                );

                // Envoyer l'email de bienvenue
                try {
                    $this->emailService->sendWelcomeEmail($user, $temporaryPassword);
                } catch (\Exception $e) {
                    $this->addFlash('warning', 'Utilisateur créé mais l\'email n\'a pas pu être envoyé.');
                }

                $this->addFlash('success', sprintf(
                    'Utilisateur "%s" créé avec succès. Un email avec le mot de passe a été envoyé.',
                    $user->getFullName()
                ));

                return $this->redirectToRoute('app_user_index');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de la création de l\'utilisateur: ' . $e->getMessage());
            }
        }

        return $this->render('user/new.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    /**
     * Affiche les détails d'un utilisateur
     */
    #[Route('/{id}', name: 'app_user_show', methods: ['GET'])]
    public function show(User $user): Response
    {
        // Récupérer l'historique d'audit pour cet utilisateur
        $auditLogs = $this->auditLogRepository->findByTargetUser($user, 20);

        return $this->render('user/show.html.twig', [
            'user' => $user,
            'auditLogs' => $auditLogs,
        ]);
    }

    /**
     * Affiche le formulaire de modification d'un utilisateur
     */
    #[Route('/{id}/edit', name: 'app_user_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, User $user): Response
    {
        // Empêcher un admin de modifier son propre rôle
        if ($user->getId() === $this->getUser()->getId()) {
            $this->addFlash('warning', 'Vous ne pouvez pas modifier votre propre compte depuis cette interface. Utilisez la page "Mon profil".');
            return $this->redirectToRoute('app_profile');
        }

        $oldRoles = $user->getRoles();
        $oldActive = $user->isActive();

        $form = $this->createForm(UserType::class, $user);
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
                    }
                }

                $this->entityManager->flush();

                // Déterminer le type de modification
                $auditDetails = [];
                $actionType = 'updated';

                // Vérifier si le rôle a changé
                $newRoles = $user->getRoles();
                if ($oldRoles !== $newRoles) {
                    $actionType = 'role_changed';
                    $auditDetails['old_role'] = implode(', ', $oldRoles);
                    $auditDetails['new_role'] = implode(', ', $newRoles);

                    // Envoyer email de notification
                    try {
                        $this->emailService->sendRoleChangedEmail(
                            $user,
                            $auditDetails['old_role'],
                            $auditDetails['new_role']
                        );
                    } catch (\Exception $e) {
                        // Silent fail
                    }
                }

                // Vérifier si le statut a changé
                if ($oldActive !== $user->isActive()) {
                    $actionType = $user->isActive() ? 'activated' : 'deactivated';

                    // Envoyer email de notification
                    try {
                        if ($user->isActive()) {
                            $this->emailService->sendAccountEnabledEmail($user);
                        } else {
                            $this->emailService->sendAccountDisabledEmail($user);
                        }
                    } catch (\Exception $e) {
                        // Silent fail
                    }
                }

                // Enregistrer dans le journal d'audit
                $this->auditLogRepository->logAction(
                    performedBy: $this->getUser(),
                    targetUser: $user,
                    targetUserEmail: $user->getEmail(),
                    action: $actionType,
                    details: $auditDetails,
                    ipAddress: $request->getClientIp()
                );

                $this->addFlash('success', sprintf('Utilisateur "%s" modifié avec succès.', $user->getFullName()));

                return $this->redirectToRoute('app_user_index');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de la modification: ' . $e->getMessage());
            }
        }

        return $this->render('user/edit.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    /**
     * Supprime un utilisateur
     */
    #[Route('/{id}/delete', name: 'app_user_delete', methods: ['POST'])]
    public function delete(Request $request, User $user): Response
    {
        // Empêcher un admin de se supprimer lui-même
        if ($user->getId() === $this->getUser()->getId()) {
            $this->addFlash('error', 'Vous ne pouvez pas supprimer votre propre compte.');
            return $this->redirectToRoute('app_user_index');
        }

        // Vérifier qu'il reste au moins un autre admin
        $adminCount = $this->userRepository->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->where('u.roles LIKE :role')
            ->andWhere('u.isActive = true')
            ->setParameter('role', '%ROLE_ADMIN%')
            ->getQuery()
            ->getSingleScalarResult();

        if ($user->isAdmin() && $adminCount <= 1) {
            $this->addFlash('error', 'Impossible de supprimer le dernier administrateur actif.');
            return $this->redirectToRoute('app_user_index');
        }

        if ($this->isCsrfTokenValid('delete' . $user->getId(), $request->request->get('_token'))) {
            try {
                $userEmail = $user->getEmail();
                $userName = $user->getFullName();

                // Supprimer l'avatar si existe
                if ($user->getAvatar()) {
                    $this->avatarUploadService->delete($user->getAvatar());
                }

                // Enregistrer dans le journal d'audit AVANT la suppression
                $this->auditLogRepository->logAction(
                    performedBy: $this->getUser(),
                    targetUser: null, // Sera null après suppression
                    targetUserEmail: $userEmail,
                    action: 'deleted',
                    details: ['name' => $userName],
                    ipAddress: $request->getClientIp()
                );

                $this->entityManager->remove($user);
                $this->entityManager->flush();

                $this->addFlash('success', sprintf('Utilisateur "%s" supprimé avec succès.', $userName));
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de la suppression: ' . $e->getMessage());
            }
        }

        return $this->redirectToRoute('app_user_index');
    }

    /**
     * Toggle le statut actif/inactif d'un utilisateur
     */
    #[Route('/{id}/toggle-status', name: 'app_user_toggle_status', methods: ['POST'])]
    public function toggleStatus(Request $request, User $user): Response
    {
        // Empêcher un admin de se désactiver lui-même
        if ($user->getId() === $this->getUser()->getId()) {
            $this->addFlash('error', 'Vous ne pouvez pas désactiver votre propre compte.');
            return $this->redirectToRoute('app_user_index');
        }

        // Vérifier qu'il reste au moins un autre admin actif
        if ($user->isAdmin() && $user->isActive()) {
            $adminCount = $this->userRepository->createQueryBuilder('u')
                ->select('COUNT(u.id)')
                ->where('u.roles LIKE :role')
                ->andWhere('u.isActive = true')
                ->setParameter('role', '%ROLE_ADMIN%')
                ->getQuery()
                ->getSingleScalarResult();

            if ($adminCount <= 1) {
                $this->addFlash('error', 'Impossible de désactiver le dernier administrateur actif.');
                return $this->redirectToRoute('app_user_index');
            }
        }

        if ($this->isCsrfTokenValid('toggle' . $user->getId(), $request->request->get('_token'))) {
            try {
                $newStatus = !$user->isActive();
                $user->setIsActive($newStatus);
                $this->entityManager->flush();

                // Enregistrer dans le journal d'audit
                $this->auditLogRepository->logAction(
                    performedBy: $this->getUser(),
                    targetUser: $user,
                    targetUserEmail: $user->getEmail(),
                    action: $newStatus ? 'activated' : 'deactivated',
                    ipAddress: $request->getClientIp()
                );

                // Envoyer email de notification
                try {
                    if ($newStatus) {
                        $this->emailService->sendAccountEnabledEmail($user);
                    } else {
                        $this->emailService->sendAccountDisabledEmail($user);
                    }
                } catch (\Exception $e) {
                    // Silent fail
                }

                $statusText = $newStatus ? 'activé' : 'désactivé';
                $this->addFlash('success', sprintf('Compte de "%s" %s avec succès.', $user->getFullName(), $statusText));
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors du changement de statut: ' . $e->getMessage());
            }
        }

        return $this->redirectToRoute('app_user_index');
    }

    /**
     * Réinitialise le mot de passe d'un utilisateur
     */
    #[Route('/{id}/reset-password', name: 'app_user_reset_password', methods: ['POST'])]
    public function resetPassword(Request $request, User $user): Response
    {
        if ($this->isCsrfTokenValid('reset' . $user->getId(), $request->request->get('_token'))) {
            try {
                // Générer un nouveau mot de passe
                $newPassword = $this->emailService->generateTemporaryPassword();
                $hashedPassword = $this->passwordHasher->hashPassword($user, $newPassword);
                $user->setPassword($hashedPassword);
                $this->entityManager->flush();

                // Enregistrer dans le journal d'audit
                $this->auditLogRepository->logAction(
                    performedBy: $this->getUser(),
                    targetUser: $user,
                    targetUserEmail: $user->getEmail(),
                    action: 'password_reset',
                    ipAddress: $request->getClientIp()
                );

                // Envoyer le nouveau mot de passe par email
                try {
                    $this->emailService->sendWelcomeEmail($user, $newPassword);
                    $this->addFlash('success', sprintf(
                        'Mot de passe réinitialisé pour "%s". Un email a été envoyé avec le nouveau mot de passe.',
                        $user->getFullName()
                    ));
                } catch (\Exception $e) {
                    $this->addFlash('warning', 'Mot de passe réinitialisé mais l\'email n\'a pas pu être envoyé.');
                }
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de la réinitialisation: ' . $e->getMessage());
            }
        }

        return $this->redirectToRoute('app_user_index');
    }
}
