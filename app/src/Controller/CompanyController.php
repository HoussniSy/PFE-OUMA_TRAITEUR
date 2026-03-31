<?php

namespace App\Controller;

use App\Entity\Company;
use App\Form\CompanyType;
use App\Repository\CompanyRepository;
use App\Service\FileUploadService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/company')]
#[IsGranted('ROLE_USER')]
class CompanyController extends AbstractController
{
    #[Route('', name: 'app_company_show', methods: ['GET'])]
    public function show(CompanyRepository $companyRepository): Response
    {
        $company = $companyRepository->findFirst();

        if (!$company) {
            $this->addFlash('warning', 'Aucune entreprise configurée. Veuillez créer les informations de votre entreprise.');

            // Seul un admin peut créer l'entreprise
            if ($this->isGranted('ROLE_ADMIN')) {
                return $this->redirectToRoute('app_company_edit');
            }

            return $this->redirectToRoute('app_dashboard');
        }

        return $this->render('company/show.html.twig', [
            'company' => $company,
        ]);
    }

    #[Route('/edit', name: 'app_company_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function edit(
        Request $request,
        CompanyRepository $companyRepository,
        EntityManagerInterface $entityManager,
        FileUploadService $fileUploadService
    ): Response {
        $company = $companyRepository->findFirst();

        if (!$company) {
            $company = new Company();
        }

        $oldLogo = $company->getLogo();
        $oldLogoQuote = $company->getLogoQuote();
        $oldLogoInvoice = $company->getLogoInvoice();

        $form = $this->createForm(CompanyType::class, $company);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                // Appliquer la couleur du thème prédéfini si sélectionné
                $themeColors = [
                    'green' => '#00a651',
                    'ocean' => '#0077b6',
                    'sunset' => '#e07b39',
                    'purple' => '#7c3aed',
                    'red' => '#dc2626',
                    'royal' => '#1d4ed8',
                ];
                if ($company->getColorTheme() && isset($themeColors[$company->getColorTheme()])) {
                    $company->setPrimaryColor($themeColors[$company->getColorTheme()]);
                }

                // Gestion de l'upload du logo principal
                $logoFile = $form->get('logoFile')->getData();
                if ($logoFile) {
                    if ($oldLogo) {
                        try {
                            $fileUploadService->delete($oldLogo);
                        } catch (\Exception $e) {
                        }
                    }
                    $filename = $fileUploadService->upload($logoFile);
                    $company->setLogo($filename);
                }

                // Gestion de l'upload du logo pour les devis
                $logoQuoteFile = $form->get('logoQuoteFile')->getData();
                if ($logoQuoteFile) {
                    if ($oldLogoQuote) {
                        try {
                            $fileUploadService->delete($oldLogoQuote);
                        } catch (\Exception $e) {
                        }
                    }
                    $filename = $fileUploadService->upload($logoQuoteFile);
                    $company->setLogoQuote($filename);
                }

                // Gestion de l'upload du logo pour les factures
                $logoInvoiceFile = $form->get('logoInvoiceFile')->getData();
                if ($logoInvoiceFile) {
                    if ($oldLogoInvoice) {
                        try {
                            $fileUploadService->delete($oldLogoInvoice);
                        } catch (\Exception $e) {
                        }
                    }
                    $filename = $fileUploadService->upload($logoInvoiceFile);
                    $company->setLogoInvoice($filename);
                }

                $entityManager->persist($company);
                $entityManager->flush();

                $this->addFlash('success', 'Les informations de l\'entreprise ont été mises à jour avec succès.');
                return $this->redirectToRoute('app_company_show');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de la mise à jour : ' . $e->getMessage());
            }
        }

        return $this->render('company/edit.html.twig', [
            'company' => $company,
            'form' => $form,
        ]);
    }
}
