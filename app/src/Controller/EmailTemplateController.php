<?php

namespace App\Controller;

use App\Entity\EmailTemplate;
use App\Form\EmailTemplateType;
use App\Repository\EmailTemplateHistoryRepository;
use App\Repository\EmailTemplateRepository;
use App\Service\EmailTemplateService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/email-template')]
#[IsGranted('ROLE_ADMIN')]
class EmailTemplateController extends AbstractController
{
    public function __construct(
        private EmailTemplateRepository $templateRepository,
        private EmailTemplateHistoryRepository $historyRepository,
        private EmailTemplateService $templateService,
        private EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * Liste tous les templates
     */
    #[Route('', name: 'app_email_template_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $search = $request->query->get('search', '');
        $category = $request->query->get('category', 'all');

        $qb = $this->templateRepository->createQueryBuilder('t');

        // Filtre de recherche
        if ($search) {
            $qb->andWhere('t.name LIKE :search OR t.code LIKE :search OR t.description LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        // Filtre par catégorie
        if ($category && $category !== 'all') {
            $qb->andWhere('t.category = :category')
                ->setParameter('category', $category);
        }

        $qb->orderBy('t.category', 'ASC')
            ->addOrderBy('t.name', 'ASC');

        $templates = $qb->getQuery()->getResult();

        // Statistiques
        $stats = [
            'total' => $this->templateRepository->count([]),
            'actifs' => $this->templateRepository->count(['isActive' => true]),
            'defaults' => $this->templateRepository->count(['isDefault' => true]),
            'custom' => $this->templateRepository->count(['isDefault' => false]),
        ];

        // Compte par catégorie
        $categoryCount = $this->templateRepository->countByCategory();

        return $this->render('email_template/index.html.twig', [
            'templates' => $templates,
            'search' => $search,
            'category' => $category,
            'stats' => $stats,
            'categoryCount' => $categoryCount,
        ]);
    }

    /**
     * Crée un nouveau template
     */
    #[Route('/new', name: 'app_email_template_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $template = new EmailTemplate();
        $template->setCreatedBy($this->getUser());
        $template->setUpdatedBy($this->getUser());

        $form = $this->createForm(EmailTemplateType::class, $template);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Vérifier que le code n'existe pas déjà
            if ($this->templateRepository->codeExists($template->getCode())) {
                $this->addFlash('error', 'Un template avec ce code existe déjà.');
                return $this->render('email_template/new.html.twig', [
                    'template' => $template,
                    'form' => $form,
                ]);
            }

            // Valider le HTML
            $validation = $this->templateService->validateHtml($template->getBody());
            if (!$validation['valid']) {
                foreach ($validation['errors'] as $error) {
                    $this->addFlash('error', $error);
                }
                return $this->render('email_template/new.html.twig', [
                    'template' => $template,
                    'form' => $form,
                ]);
            }

            // Afficher les avertissements
            foreach ($validation['warnings'] as $warning) {
                $this->addFlash('warning', $warning);
            }

            try {
                $this->entityManager->persist($template);
                $this->entityManager->flush();

                // Sauvegarder dans l'historique
                $this->historyRepository->saveHistory($template, $this->getUser(), 'created');

                $this->addFlash('success', 'Le template a été créé avec succès.');
                return $this->redirectToRoute('app_email_template_show', ['id' => $template->getId()]);
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de la création : ' . $e->getMessage());
            }
        }

        return $this->render('email_template/new.html.twig', [
            'template' => $template,
            'form' => $form,
        ]);
    }

    /**
     * Affiche un template
     */
    #[Route('/{id}', name: 'app_email_template_show', methods: ['GET'])]
    public function show(EmailTemplate $template): Response
    {
        // Historique
        $history = $this->historyRepository->findByTemplate($template);

        // Variables disponibles
        $availableVariables = $this->templateService->getAvailableVariables();

        return $this->render('email_template/show.html.twig', [
            'template' => $template,
            'history' => $history,
            'availableVariables' => $availableVariables,
        ]);
    }

    /**
     * Modifie un template
     */
    #[Route('/{id}/edit', name: 'app_email_template_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, EmailTemplate $template): Response
    {
        $form = $this->createForm(EmailTemplateType::class, $template, [
            'is_default' => $template->isDefault(),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Vérifier que le code n'existe pas déjà
            if ($this->templateRepository->codeExists($template->getCode(), $template->getId())) {
                $this->addFlash('error', 'Un autre template avec ce code existe déjà.');
                return $this->render('email_template/edit.html.twig', [
                    'template' => $template,
                    'form' => $form,
                ]);
            }

            // Valider le HTML
            $validation = $this->templateService->validateHtml($template->getBody());
            if (!$validation['valid']) {
                foreach ($validation['errors'] as $error) {
                    $this->addFlash('error', $error);
                }
                return $this->render('email_template/edit.html.twig', [
                    'template' => $template,
                    'form' => $form,
                ]);
            }

            // Afficher les avertissements
            foreach ($validation['warnings'] as $warning) {
                $this->addFlash('warning', $warning);
            }

            try {
                $template->setUpdatedBy($this->getUser());
                $this->entityManager->flush();

                // Sauvegarder dans l'historique
                $this->historyRepository->saveHistory($template, $this->getUser(), 'updated');

                $this->addFlash('success', 'Le template a été mis à jour avec succès.');
                return $this->redirectToRoute('app_email_template_show', ['id' => $template->getId()]);
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de la mise à jour : ' . $e->getMessage());
            }
        }

        return $this->render('email_template/edit.html.twig', [
            'template' => $template,
            'form' => $form,
        ]);
    }

    /**
     * Supprime un template
     */
    #[Route('/{id}/delete', name: 'app_email_template_delete', methods: ['POST'])]
    public function delete(Request $request, EmailTemplate $template): Response
    {
        // Vérifier que ce n'est pas un template par défaut
        if ($template->isDefault()) {
            $this->addFlash('error', 'Les templates par défaut ne peuvent pas être supprimés.');
            return $this->redirectToRoute('app_email_template_show', ['id' => $template->getId()]);
        }

        if ($this->isCsrfTokenValid('delete' . $template->getId(), $request->request->get('_token'))) {
            try {
                // Supprimer l'historique associé
                $this->historyRepository->deleteByTemplate($template);

                // Supprimer le template
                $this->entityManager->remove($template);
                $this->entityManager->flush();

                $this->addFlash('success', 'Le template a été supprimé avec succès.');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de la suppression : ' . $e->getMessage());
            }
        }

        return $this->redirectToRoute('app_email_template_index');
    }

    /**
     * Duplique un template
     */
    #[Route('/{id}/duplicate', name: 'app_email_template_duplicate', methods: ['POST'])]
    public function duplicate(Request $request, EmailTemplate $template): Response
    {
        if ($this->isCsrfTokenValid('duplicate' . $template->getId(), $request->request->get('_token'))) {
            try {
                $newTemplate = new EmailTemplate();
                $newTemplate->setCode($template->getCode() . '_copy_' . time())
                    ->setName($template->getName() . ' (Copie)')
                    ->setCategory($template->getCategory())
                    ->setDescription($template->getDescription())
                    ->setSubject($template->getSubject())
                    ->setBody($template->getBody())
                    ->setAvailableVariables($template->getAvailableVariables())
                    ->setIsActive(false) // Inactif par défaut
                    ->setIsDefault(false) // Jamais par défaut
                    ->setCreatedBy($this->getUser())
                    ->setUpdatedBy($this->getUser());

                $this->entityManager->persist($newTemplate);
                $this->entityManager->flush();

                // Sauvegarder dans l'historique
                $this->historyRepository->saveHistory($newTemplate, $this->getUser(), 'created', 'Dupliqué depuis "' . $template->getName() . '"');

                $this->addFlash('success', 'Le template a été dupliqué avec succès.');
                return $this->redirectToRoute('app_email_template_edit', ['id' => $newTemplate->getId()]);
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de la duplication : ' . $e->getMessage());
            }
        }

        return $this->redirectToRoute('app_email_template_show', ['id' => $template->getId()]);
    }

    /**
     * Active/Désactive un template
     */
    #[Route('/{id}/toggle-status', name: 'app_email_template_toggle_status', methods: ['POST'])]
    public function toggleStatus(Request $request, EmailTemplate $template): Response
    {
        if ($this->isCsrfTokenValid('toggle' . $template->getId(), $request->request->get('_token'))) {
            try {
                $template->setIsActive(!$template->isActive());
                $template->setUpdatedBy($this->getUser());
                $this->entityManager->flush();

                $status = $template->isActive() ? 'activé' : 'désactivé';
                $this->addFlash('success', "Le template a été $status avec succès.");
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors du changement de statut : ' . $e->getMessage());
            }
        }

        return $this->redirectToRoute('app_email_template_show', ['id' => $template->getId()]);
    }

    /**
     * Prévisualise un template avec des données de test
     */
    #[Route('/{id}/preview', name: 'app_email_template_preview', methods: ['GET'])]
    public function preview(EmailTemplate $template): Response
    {
        // Données de test
        $testData = $this->templateService->getTestData();

        // Rendre le template avec les données de test
        $rendered = $this->templateService->render($template, $testData);

        return $this->render('email_template/preview.html.twig', [
            'template' => $template,
            'subject' => $rendered['subject'],
            'body' => $rendered['body'],
        ]);
    }

    /**
     * Restaure une version depuis l'historique
     */
    #[Route('/{id}/restore/{version}', name: 'app_email_template_restore', methods: ['POST'])]
    public function restore(Request $request, EmailTemplate $template, int $version): Response
    {
        if (!$this->isCsrfTokenValid('restore' . $template->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_email_template_show', ['id' => $template->getId()]);
        }

        $historyEntry = $this->historyRepository->findVersion($template, $version);

        if (!$historyEntry) {
            $this->addFlash('error', 'Version non trouvée.');
            return $this->redirectToRoute('app_email_template_show', ['id' => $template->getId()]);
        }

        try {
            // Restaurer le contenu
            $template->setSubject($historyEntry->getSubject())
                ->setBody($historyEntry->getBody())
                ->setUpdatedBy($this->getUser());

            $this->entityManager->flush();

            // Sauvegarder dans l'historique
            $this->historyRepository->saveHistory(
                $template,
                $this->getUser(),
                'restored',
                "Restauration de la version $version"
            );

            $this->addFlash('success', "La version $version a été restaurée avec succès.");
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de la restauration : ' . $e->getMessage());
        }

        return $this->redirectToRoute('app_email_template_show', ['id' => $template->getId()]);
    }

    /**
     * API : Retourne les variables disponibles
     */
    #[Route('/api/variables', name: 'app_email_template_api_variables', methods: ['GET'])]
    public function apiVariables(): JsonResponse
    {
        return new JsonResponse($this->templateService->getAvailableVariables());
    }
}
