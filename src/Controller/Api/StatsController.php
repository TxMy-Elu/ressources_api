<?php

namespace App\Controller\Api;

use App\Entity\Ressource;
use App\Repository\CategoryRepository;
use App\Repository\RessourceRepository;
use App\Repository\UserRepository;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[OA\Tag(name: 'Stats')]
#[Route('/api')]
final class StatsController extends AbstractController
{
    #[Route('/stats', name: 'api_stats', methods: ['GET'])]
    #[OA\Get(
        path: '/api/stats',
        summary: 'Statistiques publiques de la plateforme',
        responses: [
            new OA\Response(
                response: 200,
                description: 'Chiffres clés',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'resources_published', type: 'integer'),
                        new OA\Property(property: 'categories', type: 'integer'),
                        new OA\Property(property: 'users', type: 'integer'),
                    ]
                )
            ),
        ]
    )]
    public function stats(
        RessourceRepository $resources,
        CategoryRepository  $categories,
        UserRepository      $users,
    ): JsonResponse {
        return $this->json([
            'resources_published' => $resources->count(['statut' => Ressource::STATUS_PUBLISHED]),
            'categories'          => $categories->count([]),
            'users'               => $users->count([]),
        ]);
    }
}
