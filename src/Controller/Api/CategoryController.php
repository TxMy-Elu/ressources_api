<?php

namespace App\Controller\Api;

use App\Entity\Category;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Nelmio\ApiDocBundle\Attribute\Security;
use OpenApi\Attributes as OA;

#[Route('api/categories')]
final class CategoryController extends AbstractController
{
    //Fonction qui liste tous les categories (visible par tous)
    #[Route('', name: 'api_categories_list', methods: ['GET'])]
    #[OA\Get(
        path: '/api/categories',
        description: 'Retourne la liste complète des catégories disponibles. Accessible sans authentification.',
        summary: 'Lister toutes les catégories',
    )]
    #[OA\Response(
        response: 200,
        description: 'Liste des catégories',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(
                properties: [
                    new OA\Property(property: 'id', type: 'integer', example: 1),
                    new OA\Property(property: 'nom', type: 'string', example: 'Famille'),
                    new OA\Property(property: 'description', type: 'string', example: 'Ressources liées à la famille'),
                    ]
            )
        )
    )]
    public function list(CategoryRepository $repo): JsonResponse
    {
        $categories = $repo->findAll();
        $data = array_map(fn($c) => [
            'id'          => $c->getId(),
            'name'        => $c->getNom(),
            'description' => $c->getDescription(),
            'count'       => $c->getResources()->count(),
        ], $categories);

        return $this->json($data);
    }

    //Fonction pour créer une catégorie (admin)
    #[Route('', name: 'api_category_create', methods: ['POST'])]
    #[OA\Post(
        path: '/api/categories',
        description: 'Crée une nouvelle catégorie dans le référentiel. Réservé aux administrateurs. Nécessite un token JWT.',
        summary: 'Créer une catégorie',
    )]
    #[Security(name: 'Bearer')]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['nom'],
            properties: [
                new OA\Property(property: 'nom', type: 'string', example: 'Famille'),
                new OA\Property(property: 'description', type: 'string', example: 'Ressources liées aux relations familiales'),
                ]
        )
    )]
    #[OA\Response(
        response: 201,
        description: 'Catégorie créée',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'message', type: 'string', example: 'Catégorie créée'),
                new OA\Property(property: 'id', type: 'integer', example: 1),
            ]
        )
    )]
    #[OA\Response(response: 400, description: 'Nom manquant')]
    #[OA\Response(response: 401, description: 'Token JWT manquant ou invalide')]
    #[OA\Response(response: 403, description: 'Accès refusé — Admin requis')]
    public function create(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $data = json_decode($request->getContent(), true);
        if (empty($data['name'])) {
            return $this->json(['error' => 'Nom requis.'], Response::HTTP_BAD_REQUEST);
        }

        $category = new Category();
        $category->setNom($data['name']);
        $category->setDescription($data['description'] ?? null);

        $em->persist($category);
        $em->flush();

        return $this->json([
            'message' => 'Catégorie crée avec succès',
            'id' => $category->getId(),
        ], Response::HTTP_CREATED);
    }

    //Fonction pour créer une catégorie (admin)
    #[Route('/{id}', name: 'api_category_update', methods: ['PUT'])]
    #[OA\Put(
        path: '/api/categories/{id}',
        description: 'Modifie une catégorie existante. Réservé aux administrateurs. Nécessite un token JWT.',
        summary: 'Modifier une catégorie',
    )]
    #[Security(name: 'Bearer')]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\RequestBody(
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'nom', type: 'string', example: 'Nouveau nom'),
                new OA\Property(property: 'description', type: 'string', example: 'Nouvelle description'),
                ]
        )
    )]
    #[OA\Response(response: 200, description: 'Catégorie mise à jour')]
    #[OA\Response(response: 401, description: 'Token JWT manquant ou invalide')]
    #[OA\Response(response: 403, description: 'Accès refusé — Admin requis')]
    #[OA\Response(response: 404, description: 'Catégorie introuvable')]
    public function update(int $id, Request $request, EntityManagerInterface $em, CategoryRepository $repo): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $category = $repo->find($id);
        if(!$category) {
            return $this->json(['error' => 'Catégorie introuvable'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);
        if (!empty($data['name'])) {$category->setNom($data['name']);}
        if(isset($data['description'])) {$category->setDescription($data['description']);}


        $em->flush();

        return $this->json(['message' => 'Catégorie mise à jour']);
    }

    #[Route('/{id}', name: 'api_category_delete', methods: ['DELETE'])]
    #[OA\Delete(
        path: '/api/categories/{id}',
        description: 'Supprime définitivement une catégorie. Réservé aux administrateurs. Nécessite un token JWT.',
        summary: 'Supprimer une catégorie',
    )]
    #[Security(name: 'Bearer')]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\Response(response: 200, description: 'Catégorie supprimée')]
    #[OA\Response(response: 401, description: 'Token JWT manquant ou invalide')]
    #[OA\Response(response: 403, description: 'Accès refusé — Admin requis')]
    #[OA\Response(response: 404, description: 'Catégorie introuvable')]
    public function delete(int $id, EntityManagerInterface $em, CategoryRepository $repo): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $category = $repo->find($id);
        if (!$category) {
            return $this->json(['error' => 'Catégorie introuvable'], Response::HTTP_NOT_FOUND);
        }

        // Détacher toutes les ressources liées avant suppression
        foreach ($category->getResources() as $resource) {
            $resource->setCategory(null);
        }

        $em->remove($category);
        $em->flush();

        return $this->json([
            'message' => 'Catégorie supprimée',
            'affected' => $category->getResources()->count(),
        ]);
    }
}
