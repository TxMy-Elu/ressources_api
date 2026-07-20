<?php

namespace App\Controller\Api;

use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[OA\Tag(name: 'Health')]
#[Route('/api')]
final class HealthController extends AbstractController
{
    #[Route('/health', name: 'api_health', methods: ['GET'])]
    #[OA\Get(
        path: '/api/health',
        summary: 'Vérifie l\'état de l\'API',
        responses: [
            new OA\Response(
                response: 200,
                description: 'API opérationnelle',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'ok'),
                        new OA\Property(property: 'version', type: 'string', example: '1.0.0'),
                        new OA\Property(property: 'timestamp', type: 'string', format: 'date-time'),
                    ]
                )
            ),
        ]
    )]
    public function health(): JsonResponse
    {
        return $this->json([
            'status'    => 'ok',
            'version'   => '1.0.0',
            'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
        ]);
    }
}
