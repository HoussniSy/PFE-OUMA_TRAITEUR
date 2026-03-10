<?php

namespace App\Controller;

use App\Entity\ServiceCategory;
use App\Form\ServiceCategoryType;
use App\Repository\ServiceCategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/service-category')]
#[IsGranted('ROLE_USER')]
class ServiceCategoryController extends AbstractController
{
    #[Route('', name: 'app_service_category_index', methods: ['GET'])]
    public function index(ServiceCategoryRepository $repository): Response
    {
        $user = $this->getUser();
        $company = $user->getCompany();

        $categoriesWithCount = $repository->findWithItemCount($company);

        return $this->render('service_category/index.html.twig', [
            'categoriesWithCount' => $categoriesWithCount,
        ]);
    }

    #[Route('/new', name: 'app_service_category_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $category = new ServiceCategory();

        $form = $this->createForm(ServiceCategoryType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                // Associer à l'entreprise de l'utilisateur
                $user = $this->getUser();
                if ($user->getCompany()) {
                    $category->setCompany($user->getCompany());
                }

                $entityManager->persist($category);
                $entityManager->flush();

                $this->addFlash('success', 'La catégorie a été créée avec succès.');
                return $this->redirectToRoute('app_service_category_index');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de la création : ' . $e->getMessage());
            }
        }

        return $this->render('service_category/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_service_category_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, ServiceCategory $category, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ServiceCategoryType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $entityManager->flush();
                $this->addFlash('success', 'La catégorie a été mise à jour avec succès.');
                return $this->redirectToRoute('app_service_category_index');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de la mise à jour : ' . $e->getMessage());
            }
        }

        return $this->render('service_category/edit.html.twig', [
            'category' => $category,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_service_category_delete', methods: ['POST'])]
    #[IsGranted('ROLE_COMPTABLE')]
    public function delete(Request $request, ServiceCategory $category, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $category->getId(), $request->request->get('_token'))) {
            try {
                $entityManager->remove($category);
                $entityManager->flush();
                $this->addFlash('success', 'La catégorie a été supprimée avec succès.');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de la suppression : ' . $e->getMessage());
            }
        }

        return $this->redirectToRoute('app_service_category_index');
    }
}
