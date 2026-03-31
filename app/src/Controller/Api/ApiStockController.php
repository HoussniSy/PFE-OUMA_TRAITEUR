<?php

namespace App\Controller\Api;

use App\Entity\StockItem;
use App\Repository\StockItemRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ApiStockController extends AbstractController
{
    private function serializeStockItem(StockItem $item): array
    {
        return [
            'id' => $item->getId(),
            'name' => $item->getName(),
            'description' => $item->getDescription(),
            'unit' => $item->getUnit(),
            'currentQuantity' => $item->getCurrentQuantity(),
            'minimumQuantity' => $item->getMinimumQuantity(),
            'unitPrice' => $item->getUnitPrice(),
            'isLowStock' => $item->isLowStock(),
            'lastRestockedAt' => $item->getLastRestockedAt()?->format('c'),
            'createdAt' => $item->getCreatedAt()?->format('c'),
        ];
    }

    /**
     * Liste des articles en stock.
     */
    #[Route('/api/stock_items', name: 'api_stock_items_list', methods: ['GET'])]
    public function list(Request $request, StockItemRepository $stockItemRepository): JsonResponse
    {
        $page = max(1, $request->query->getInt('page', 1));
        $limit = 20;
        $offset = ($page - 1) * $limit;

        $items = $stockItemRepository->findBy([], ['name' => 'ASC'], $limit, $offset);
        $total = $stockItemRepository->count([]);

        $data = array_map(fn(StockItem $item) => $this->serializeStockItem($item), $items);

        return $this->json([
            'data' => $data,
            'total' => $total,
            'page' => $page,
            'totalPages' => (int) ceil($total / $limit),
        ]);
    }

    /**
     * Obtenir un article de stock spécifique.
     */
    #[Route('/api/stock_items/{id}', name: 'api_stock_items_show', methods: ['GET'])]
    public function show(int $id, StockItemRepository $stockItemRepository): JsonResponse
    {
        $item = $stockItemRepository->find($id);

        if (!$item) {
            return $this->json(['message' => 'Article non trouvé.'], 404);
        }

        return $this->json($this->serializeStockItem($item));
    }

    /**
     * Créer un nouvel article en stock.
     */
    #[Route('/api/stock_items', name: 'api_stock_items_create', methods: ['POST'])]
    public function create(
        Request $request,
        EntityManagerInterface $em,
        ValidatorInterface $validator
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        $item = new StockItem();
        $item->setName($data['name'] ?? '');
        $item->setDescription($data['description'] ?? null);
        $item->setUnit($data['unit'] ?? StockItem::UNIT_KG);
        $item->setCurrentQuantity($data['currentQuantity'] ?? '0.00');
        $item->setMinimumQuantity($data['minimumQuantity'] ?? '0.00');
        $item->setUnitPrice($data['unitPrice'] ?? null);

        // Associer à l'entreprise de l'utilisateur
        $user = $this->getUser();
        if ($user->getCompany()) {
            $item->setCompany($user->getCompany());
        }

        $errors = $validator->validate($item);
        if (count($errors) > 0) {
            $messages = [];
            foreach ($errors as $error) {
                $messages[$error->getPropertyPath()] = $error->getMessage();
            }
            return $this->json(['errors' => $messages], 422);
        }

        $em->persist($item);
        $em->flush();

        return $this->json($this->serializeStockItem($item), 201);
    }

    /**
     * Mettre à jour ou réapprovisionner un article en stock.
     */
    #[Route('/api/stock_items/{id}', name: 'api_stock_items_update', methods: ['PUT'])]
    public function update(
        int $id,
        Request $request,
        StockItemRepository $stockItemRepository,
        EntityManagerInterface $em,
        ValidatorInterface $validator
    ): JsonResponse {
        $item = $stockItemRepository->find($id);

        if (!$item) {
            return $this->json(['message' => 'Article non trouvé.'], 404);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['name'])) $item->setName($data['name']);
        if (isset($data['description'])) $item->setDescription($data['description']);
        if (isset($data['unit'])) $item->setUnit($data['unit']);
        if (isset($data['minimumQuantity'])) $item->setMinimumQuantity($data['minimumQuantity']);
        if (isset($data['unitPrice'])) $item->setUnitPrice($data['unitPrice']);

        // Réapprovisionnement
        if (isset($data['restockQuantity'])) {
            $item->restock((float) $data['restockQuantity']);
        }

        $errors = $validator->validate($item);
        if (count($errors) > 0) {
            $messages = [];
            foreach ($errors as $error) {
                $messages[$error->getPropertyPath()] = $error->getMessage();
            }
            return $this->json(['errors' => $messages], 422);
        }

        $em->flush();

        return $this->json($this->serializeStockItem($item));
    }

    /**
     * Supprimer un article (ROLE_ADMIN uniquement).
     */
    #[Route('/api/stock_items/{id}', name: 'api_stock_items_delete', methods: ['DELETE'])]
    public function delete(
        int $id,
        StockItemRepository $stockItemRepository,
        EntityManagerInterface $em
    ): JsonResponse {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $item = $stockItemRepository->find($id);

        if (!$item) {
            return $this->json(['message' => 'Article non trouvé.'], 404);
        }

        $em->remove($item);
        $em->flush();

        return $this->json(['message' => 'Article supprimé avec succès.']);
    }
}
