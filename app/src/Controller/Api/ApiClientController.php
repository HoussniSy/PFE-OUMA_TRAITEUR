<?php

namespace App\Controller\Api;

use App\Entity\Client;
use App\Repository\ClientRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ApiClientController extends AbstractController
{
    /**
     * Liste des clients avec pagination et recherche.
     */
    #[Route('/api/clients', name: 'api_clients_list', methods: ['GET'])]
    public function list(Request $request, ClientRepository $clientRepository): JsonResponse
    {
        $page = max(1, $request->query->getInt('page', 1));
        $search = $request->query->get('search', '');
        $limit = 20;
        $offset = ($page - 1) * $limit;

        if ($search) {
            $allResults = $clientRepository->search($search);
            $total = count($allResults);
            $clients = array_slice($allResults, $offset, $limit);
        } else {
            $clients = $clientRepository->findBy([], ['createdAt' => 'DESC'], $limit, $offset);
            $total = $clientRepository->count([]);
        }

        $data = array_map(fn(Client $c) => [
            'id' => $c->getId(),
            'name' => $c->getName(),
            'email' => $c->getEmail(),
            'phone' => $c->getPhone(),
            'address' => $c->getAddress(),
            'createdAt' => $c->getCreatedAt()?->format('c'),
            'documentsCount' => $c->getDocuments()->count(),
        ], $clients);

        return $this->json([
            'data' => $data,
            'total' => $total,
            'page' => $page,
            'totalPages' => (int) ceil($total / $limit),
        ]);
    }

    /**
     * Obtenir un client spécifique.
     */
    #[Route('/api/clients/{id}', name: 'api_clients_show', methods: ['GET'])]
    public function show(int $id, ClientRepository $clientRepository): JsonResponse
    {
        $client = $clientRepository->find($id);

        if (!$client) {
            return $this->json(['message' => 'Client non trouvé.'], 404);
        }

        return $this->json([
            'id' => $client->getId(),
            'name' => $client->getName(),
            'email' => $client->getEmail(),
            'phone' => $client->getPhone(),
            'address' => $client->getAddress(),
            'createdAt' => $client->getCreatedAt()?->format('c'),
            'documentsCount' => $client->getDocuments()->count(),
        ]);
    }

    /**
     * Créer un client.
     */
    #[Route('/api/clients', name: 'api_clients_create', methods: ['POST'])]
    public function create(
        Request $request,
        EntityManagerInterface $em,
        ValidatorInterface $validator
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        $client = new Client();
        $client->setName($data['name'] ?? '');
        $client->setEmail($data['email'] ?? null);
        $client->setPhone($data['phone'] ?? null);
        $client->setAddress($data['address'] ?? null);

        // Associer à l'entreprise de l'utilisateur connecté
        $user = $this->getUser();
        if ($user->getCompany()) {
            $client->setCompany($user->getCompany());
        }

        $errors = $validator->validate($client);
        if (count($errors) > 0) {
            $messages = [];
            foreach ($errors as $error) {
                $messages[$error->getPropertyPath()] = $error->getMessage();
            }
            return $this->json(['errors' => $messages], 422);
        }

        $em->persist($client);
        $em->flush();

        return $this->json([
            'id' => $client->getId(),
            'name' => $client->getName(),
            'email' => $client->getEmail(),
            'phone' => $client->getPhone(),
            'address' => $client->getAddress(),
            'createdAt' => $client->getCreatedAt()?->format('c'),
            'documentsCount' => $client->getDocuments()->count(),
        ], 201);
    }

    /**
     * Mettre à jour un client.
     */
    #[Route('/api/clients/{id}', name: 'api_clients_update', methods: ['PUT'])]
    public function update(
        int $id,
        Request $request,
        ClientRepository $clientRepository,
        EntityManagerInterface $em,
        ValidatorInterface $validator
    ): JsonResponse {
        $client = $clientRepository->find($id);

        if (!$client) {
            return $this->json(['message' => 'Client non trouvé.'], 404);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['name'])) $client->setName($data['name']);
        if (array_key_exists('email', $data)) $client->setEmail($data['email']);
        if (array_key_exists('phone', $data)) $client->setPhone($data['phone']);
        if (array_key_exists('address', $data)) $client->setAddress($data['address']);

        $errors = $validator->validate($client);
        if (count($errors) > 0) {
            $messages = [];
            foreach ($errors as $error) {
                $messages[$error->getPropertyPath()] = $error->getMessage();
            }
            return $this->json(['errors' => $messages], 422);
        }

        $em->flush();

        return $this->json([
            'id' => $client->getId(),
            'name' => $client->getName(),
            'email' => $client->getEmail(),
            'phone' => $client->getPhone(),
            'address' => $client->getAddress(),
            'createdAt' => $client->getCreatedAt()?->format('c'),
            'documentsCount' => $client->getDocuments()->count(),
        ]);
    }

    /**
     * Supprimer un client (ROLE_ADMIN uniquement).
     */
    #[Route('/api/clients/{id}', name: 'api_clients_delete', methods: ['DELETE'])]
    public function delete(
        int $id,
        ClientRepository $clientRepository,
        EntityManagerInterface $em
    ): JsonResponse {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $client = $clientRepository->find($id);

        if (!$client) {
            return $this->json(['message' => 'Client non trouvé.'], 404);
        }

        $em->remove($client);
        $em->flush();

        return $this->json(['message' => 'Client supprimé avec succès.']);
    }
}
