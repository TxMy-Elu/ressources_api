<?php

namespace App\Controller;

use App\Entity\LogAction;
use App\Entity\PasswordResetToken;
use App\Entity\User;
use App\Service\LogService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class ResetPasswordController extends AbstractController
{
    // API JSON endpoint used by the Next.js frontend
    #[Route('/api/reset-password', name: 'api_reset_password', methods: ['POST'])]
    public function apiResetPassword(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher,
        LogService $logger
    ): JsonResponse {
        $data = json_decode($request->getContent(), true) ?? [];

        $token           = trim((string) ($data['token'] ?? ''));
        $password        = (string) ($data['password'] ?? '');
        $passwordConfirm = (string) ($data['passwordConfirm'] ?? '');

        if (!$token) {
            return $this->json(['error' => 'Token manquant'], Response::HTTP_BAD_REQUEST);
        }
        if (strlen($password) < 8) {
            return $this->json(['error' => 'Le mot de passe doit contenir au moins 8 caractères'], Response::HTTP_BAD_REQUEST);
        }
        if ($password !== $passwordConfirm) {
            return $this->json(['error' => 'Les mots de passe ne correspondent pas'], Response::HTTP_BAD_REQUEST);
        }

        /** @var PasswordResetToken|null $resetToken */
        $resetToken = $em->getRepository(PasswordResetToken::class)->findValidToken($token);
        if (!$resetToken) {
            return $this->json(['error' => 'Lien invalide ou expiré'], Response::HTTP_NOT_FOUND);
        }

        $user = $resetToken->getUser();
        if (!$user instanceof User) {
            return $this->json(['error' => 'Utilisateur introuvable'], Response::HTTP_NOT_FOUND);
        }

        $user->setPassword($passwordHasher->hashPassword($user, $password));
        $resetToken->setUsed(true);
        $em->flush();

        $logger->logAction(LogAction::ACTION_PASSWORD_RESET, $user, $request, null, "Mot de passe réinitialisé : {$user->getEmail()}");

        return $this->json(['message' => 'Mot de passe réinitialisé avec succès']);
    }

    // Legacy Twig route (kept for fallback)
    #[Route('/reset-password', name: 'app_reset_password', methods: ['GET', 'POST'])]
    public function resetPassword(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        $token = (string) ($request->query->get('token') ?? $request->request->get('token') ?? '');

        if (!$token) {
            return $this->render('reset_password/error.html.twig', ['error' => 'Token manquant']);
        }

        if ($request->isMethod('POST')) {
            $password        = (string) $request->request->get('password', '');
            $passwordConfirm = (string) $request->request->get('password_confirm', '');

            if ('' === trim($password)) {
                return $this->render('reset_password/index.html.twig', ['token' => $token, 'error' => 'Le mot de passe est requis']);
            }
            if (strlen($password) < 8) {
                return $this->render('reset_password/index.html.twig', ['token' => $token, 'error' => 'Le mot de passe doit contenir au moins 8 caractères']);
            }
            if ($password !== $passwordConfirm) {
                return $this->render('reset_password/index.html.twig', ['token' => $token, 'error' => 'Les mots de passe ne correspondent pas']);
            }

            $resetToken = $em->getRepository(PasswordResetToken::class)->findValidToken($token);
            if (!$resetToken) {
                return $this->render('reset_password/error.html.twig', ['error' => 'Token invalide ou expiré']);
            }

            $user = $resetToken->getUser();
            if (!$user instanceof User) {
                return $this->render('reset_password/error.html.twig', ['error' => 'Utilisateur introuvable']);
            }

            $user->setPassword($passwordHasher->hashPassword($user, $password));
            $resetToken->setUsed(true);
            $em->flush();

            return $this->render('reset_password/index.html.twig', ['token' => $token, 'success' => 'Mot de passe réinitialisé avec succès']);
        }

        return $this->render('reset_password/index.html.twig', ['token' => $token]);
    }
}
