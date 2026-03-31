<?php

namespace App\Controller;

use App\Repository\SavedFilterRepository;
use App\Service\FilterService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/saved-filter')]
#[IsGranted('ROLE_USER')]
class SavedFilterController extends AbstractController
{
    public function __construct(
        private SavedFilterRepository $savedFilterRepository,
        private FilterService $filterService,
    ) {
    }

    /**
     * Sauvegarde un nouveau filtre favori
     */
    #[Route('/save', name: 'app_saved_filter_save', methods: ['POST'])]
    public function save(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['name']) || !isset($data['pageType']) || !isset($data['filters'])) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Données manquantes',
            ], Response::HTTP_BAD_REQUEST);
        }

        $name = trim($data['name']);
        $pageType = $data['pageType'];
        $filters = $data['filters'];

        // Validation
        if (empty($name)) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Le nom du filtre ne peut pas être vide',
            ], Response::HTTP_BAD_REQUEST);
        }

        // Vérifier que le nom n'existe pas déjà
        if ($this->savedFilterRepository->filterNameExists($this->getUser(), $name, $pageType)) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Un filtre avec ce nom existe déjà',
            ], Response::HTTP_CONFLICT);
        }

        try {
            $savedFilter = $this->savedFilterRepository->saveFilter(
                $this->getUser(),
                $name,
                $pageType,
                $filters
            );

            return new JsonResponse([
                'success' => true,
                'message' => 'Filtre sauvegardé avec succès',
                'filter' => [
                    'id' => $savedFilter->getId(),
                    'name' => $savedFilter->getName(),
                    'filters' => $savedFilter->getFiltersArray(),
                ],
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Erreur lors de la sauvegarde: ' . $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Liste tous les filtres sauvegardés pour une page
     */
    #[Route('/list/{pageType}', name: 'app_saved_filter_list', methods: ['GET'])]
    public function list(string $pageType): JsonResponse
    {
        $filters = $this->savedFilterRepository->findByUserAndPage($this->getUser(), $pageType);

        $data = array_map(function($filter) {
            return [
                'id' => $filter->getId(),
                'name' => $filter->getName(),
                'filters' => $filter->getFiltersArray(),
                'createdAt' => $filter->getCreatedAt()->format('Y-m-d H:i:s'),
            ];
        }, $filters);

        return new JsonResponse([
            'success' => true,
            'filters' => $data,
        ]);
    }

    /**
     * Charge un filtre spécifique
     */
    #[Route('/load/{id}', name: 'app_saved_filter_load', methods: ['GET'])]
    public function load(int $id): JsonResponse
    {
        $filter = $this->savedFilterRepository->find($id);

        if (!$filter) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Filtre non trouvé',
            ], Response::HTTP_NOT_FOUND);
        }

        // Vérifier que le filtre appartient à l'utilisateur
        if ($filter->getUser()->getId() !== $this->getUser()->getId()) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Accès non autorisé',
            ], Response::HTTP_FORBIDDEN);
        }

        return new JsonResponse([
            'success' => true,
            'filter' => [
                'id' => $filter->getId(),
                'name' => $filter->getName(),
                'filters' => $filter->getFiltersArray(),
            ],
        ]);
    }

    /**
     * Supprime un filtre sauvegardé
     */
    #[Route('/delete/{id}', name: 'app_saved_filter_delete', methods: ['DELETE', 'POST'])]
    public function delete(int $id): JsonResponse
    {
        $filter = $this->savedFilterRepository->find($id);

        if (!$filter) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Filtre non trouvé',
            ], Response::HTTP_NOT_FOUND);
        }

        // Vérifier que le filtre appartient à l'utilisateur
        if ($filter->getUser()->getId() !== $this->getUser()->getId()) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Accès non autorisé',
            ], Response::HTTP_FORBIDDEN);
        }

        try {
            $this->savedFilterRepository->deleteFilter($filter);

            return new JsonResponse([
                'success' => true,
                'message' => 'Filtre supprimé avec succès',
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Erreur lors de la suppression: ' . $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Met à jour un filtre existant
     */
    #[Route('/update/{id}', name: 'app_saved_filter_update', methods: ['PUT', 'POST'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $filter = $this->savedFilterRepository->find($id);

        if (!$filter) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Filtre non trouvé',
            ], Response::HTTP_NOT_FOUND);
        }

        // Vérifier que le filtre appartient à l'utilisateur
        if ($filter->getUser()->getId() !== $this->getUser()->getId()) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Accès non autorisé',
            ], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true);

        if (!isset($data['name']) || !isset($data['filters'])) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Données manquantes',
            ], Response::HTTP_BAD_REQUEST);
        }

        $name = trim($data['name']);

        if (empty($name)) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Le nom du filtre ne peut pas être vide',
            ], Response::HTTP_BAD_REQUEST);
        }

        // Vérifier que le nom n'existe pas déjà (sauf pour ce filtre)
        if ($this->savedFilterRepository->filterNameExists(
            $this->getUser(),
            $name,
            $filter->getPageType(),
            $filter->getId()
        )) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Un filtre avec ce nom existe déjà',
            ], Response::HTTP_CONFLICT);
        }

        try {
            $this->savedFilterRepository->updateFilter($filter, $name, $data['filters']);

            return new JsonResponse([
                'success' => true,
                'message' => 'Filtre mis à jour avec succès',
                'filter' => [
                    'id' => $filter->getId(),
                    'name' => $filter->getName(),
                    'filters' => $filter->getFiltersArray(),
                ],
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour: ' . $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
