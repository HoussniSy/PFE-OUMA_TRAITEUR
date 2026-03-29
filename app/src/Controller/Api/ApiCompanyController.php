<?php

namespace App\Controller\Api;

use App\Repository\CompanyRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class ApiCompanyController extends AbstractController
{
    /**
     * Liste des entreprises.
     * Admin : toutes, sinon : celle de l'utilisateur uniquement.
     */
    #[Route('/api/companies', name: 'api_companies_list', methods: ['GET'])]
    public function list(CompanyRepository $companyRepository): JsonResponse
    {
        $user = $this->getUser();

        if ($this->isGranted('ROLE_ADMIN')) {
            $companies = $companyRepository->findAll();
        } else {
            $company = $user->getCompany();
            $companies = $company ? [$company] : [];
        }

        $data = array_map(fn($c) => [
            'id' => $c->getId(),
            'name' => $c->getName(),
            'nameArabic' => $c->getNameArabic(),
            'registrationNumber' => $c->getRegistrationNumber(),
            'nif' => $c->getNif(),
            'phone' => $c->getPhone(),
            'address' => $c->getAddress(),
            'bankName' => $c->getBankName(),
            'bankAccount' => $c->getBankAccount(),
            'primaryColor' => $c->getPrimaryColor(),
            'defaultTaxRate' => $c->getDefaultTaxRate(),
            'defaultPaymentTerms' => $c->getDefaultPaymentTerms(),
            'defaultCurrency' => $c->getDefaultCurrency(),
        ], $companies);

        return $this->json($data);
    }

    /**
     * Obtenir une entreprise spécifique.
     */
    #[Route('/api/companies/{id}', name: 'api_companies_show', methods: ['GET'])]
    public function show(int $id, CompanyRepository $companyRepository): JsonResponse
    {
        $company = $companyRepository->find($id);

        if (!$company) {
            return $this->json(['message' => 'Entreprise non trouvée.'], 404);
        }

        // Vérifier accès : admin ou même entreprise
        $user = $this->getUser();
        if (!$this->isGranted('ROLE_ADMIN') && $user->getCompany()?->getId() !== $company->getId()) {
            return $this->json(['message' => 'Accès refusé.'], 403);
        }

        return $this->json([
            'id' => $company->getId(),
            'name' => $company->getName(),
            'nameArabic' => $company->getNameArabic(),
            'registrationNumber' => $company->getRegistrationNumber(),
            'nif' => $company->getNif(),
            'phone' => $company->getPhone(),
            'address' => $company->getAddress(),
            'bankName' => $company->getBankName(),
            'bankAccount' => $company->getBankAccount(),
            'primaryColor' => $company->getPrimaryColor(),
            'defaultTaxRate' => $company->getDefaultTaxRate(),
            'defaultPaymentTerms' => $company->getDefaultPaymentTerms(),
            'defaultCurrency' => $company->getDefaultCurrency(),
        ]);
    }
}
