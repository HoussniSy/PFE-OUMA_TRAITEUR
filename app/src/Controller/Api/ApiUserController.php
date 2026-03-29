<?php

namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class ApiUserController extends AbstractController
{
    /**
     * Retourne les informations de l'utilisateur connecté.
     */
    #[Route('/api/me', name: 'api_me', methods: ['GET'])]
    public function me(): JsonResponse
    {
        $user = $this->getUser();

        return $this->json([
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'nom' => $user->getNom(),
            'prenom' => $user->getPrenom(),
            'roles' => $user->getRoles(),
            'phone' => $user->getPhone(),
            'poste' => $user->getPoste(),
            'avatar' => $user->getAvatarPath(),
            'company' => $user->getCompany() ? [
                'id' => $user->getCompany()->getId(),
                'name' => $user->getCompany()->getName(),
            ] : null,
            'lastLoginAt' => $user->getLastLoginAt()?->format('c'),
            'createdAt' => $user->getCreatedAt()?->format('c'),
        ]);
    }
}
