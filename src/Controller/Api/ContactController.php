<?php

namespace App\Controller\Api;

use App\Entity\LogAction;
use App\Entity\User;
use App\Service\LogService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api')]
final class ContactController extends AbstractController
{
    #[Route('/contact', name: 'api_contact', methods: ['POST'])]
    public function contact(Request $request, LogService $logger): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!is_array($data) || empty($data['name']) || empty($data['email']) || empty($data['message'])) {
            return $this->json(['error' => 'Veuillez remplir tous les champs'], 400);
        }

        // TODO: Send email or save to database
        // For now, just return success

        /** @var User|null $currentUser */
        $currentUser = $this->getUser();
        $logger->logAction(
            LogAction::ACTION_CONTACT,
            $currentUser instanceof User ? $currentUser : null,
            $request,
            null,
            "Message de contact de {$data['name']} <{$data['email']}>"
        );

        return $this->json([
            'message' => 'Message reçu avec succès',
            'ticketId' => 'TICKET-' . uniqid()
        ], 201);
    }
}
