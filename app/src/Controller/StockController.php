<?php

namespace App\Controller;

use App\Entity\StockItem;
use App\Form\StockItemType;
use App\Repository\StockItemRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/stock')]
#[IsGranted('ROLE_USER')]
class StockController extends AbstractController
{
    #[Route('', name: 'app_stock_index', methods: ['GET'])]
    public function index(Request $request, StockItemRepository $repository): Response
    {
        $user = $this->getUser();
        $company = $user->getCompany();

        $filter = $request->query->get('filter', 'all');

        if ($filter === 'low') {
            $items = $repository->findLowStock($company);
        } else {
            $items = $repository->findByCompany($company);
        }

        $lowStockCount = $repository->countLowStock($company);

        return $this->render('stock/index.html.twig', [
            'items' => $items,
            'lowStockCount' => $lowStockCount,
            'currentFilter' => $filter,
        ]);
    }

    #[Route('/new', name: 'app_stock_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $item = new StockItem();

        $form = $this->createForm(StockItemType::class, $item);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $user = $this->getUser();
                if ($user->getCompany()) {
                    $item->setCompany($user->getCompany());
                }

                $entityManager->persist($item);
                $entityManager->flush();

                $this->addFlash('success', 'L\'article a été ajouté au stock avec succès.');
                return $this->redirectToRoute('app_stock_index');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de la création : ' . $e->getMessage());
            }
        }

        return $this->render('stock/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_stock_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, StockItem $item, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(StockItemType::class, $item);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $entityManager->flush();
                $this->addFlash('success', 'L\'article a été mis à jour avec succès.');
                return $this->redirectToRoute('app_stock_index');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de la mise à jour : ' . $e->getMessage());
            }
        }

        return $this->render('stock/edit.html.twig', [
            'item' => $item,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/restock', name: 'app_stock_restock', methods: ['POST'])]
    public function restock(Request $request, StockItem $item, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('restock' . $item->getId(), $request->request->get('_token'))) {
            $quantity = (float) $request->request->get('quantity', 0);

            if ($quantity <= 0) {
                $this->addFlash('error', 'La quantité doit être positive.');
                return $this->redirectToRoute('app_stock_index');
            }

            try {
                $item->restock($quantity);
                $entityManager->flush();

                $this->addFlash('success', sprintf(
                    'Stock de "%s" réapprovisionné de %s %s. Nouveau stock : %s',
                    $item->getName(),
                    number_format($quantity, 2, ',', ' '),
                    $item->getUnit(),
                    $item->getFormattedQuantity()
                ));
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors du réapprovisionnement : ' . $e->getMessage());
            }
        }

        return $this->redirectToRoute('app_stock_index');
    }

    #[Route('/{id}/delete', name: 'app_stock_delete', methods: ['POST'])]
    #[IsGranted('ROLE_COMPTABLE')]
    public function delete(Request $request, StockItem $item, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $item->getId(), $request->request->get('_token'))) {
            try {
                $entityManager->remove($item);
                $entityManager->flush();
                $this->addFlash('success', 'L\'article a été supprimé du stock.');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de la suppression : ' . $e->getMessage());
            }
        }

        return $this->redirectToRoute('app_stock_index');
    }
}
