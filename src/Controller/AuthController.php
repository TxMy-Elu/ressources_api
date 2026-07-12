<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\LogAction;
use App\Entity\LogConnexion;
use App\Entity\Participation;
use App\Entity\Ressource;
use App\Entity\User;
use App\Entity\PasswordResetToken;
use App\Service\LogService;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;
use App\Service\PasswordResetMailer;

class AuthController extends AbstractController
{
    public function __construct(
        private readonly RateLimiterFactory $loginLimiter,
        private readonly RateLimiterFactory $registerLimiter,
        private readonly RateLimiterFactory $forgotPasswordLimiter,
    ) {}

    #[Route('/api/auth/register', name: 'api_auth_register', methods: ['POST'])]
    public function register(
        Request                     $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface      $em,
        JWTTokenManagerInterface    $jwtManager,
        LogService                  $logger
    ): JsonResponse
    {
        $limiter = $this->registerLimiter->create($request->getClientIp());
        if (!$limiter->consume(1)->isAccepted()) {
            return $this->json(['error' => 'Trop de tentatives. Réessayez dans 1 heure.'], 429);
        }
        $data = $this->decodeJsonPayload($request);

        if ($data === null) {
            return $this->json(['error' => 'Corps JSON invalide ou vide'], 400);
        }

        $email = strtolower(trim((string)($data['email'] ?? '')));
        $displayName = trim((string)($data['name'] ?? ''));
        $password = (string)($data['password'] ?? '');

        if ($email === '' || $password === '' || $displayName === '') {
            return $this->json(['error' => 'Champs manquants'], 400);
        }

        $existingUser = $em->getRepository(User::class)->findOneBy(['email' => $email]);
        if ($existingUser) {
            return $this->json(['error' => 'Email déjà utilisé'], 400);
        }

        $user = new User();
        $user->setEmail($email);


        $nameParts = preg_split('/\s+/', $displayName, 2) ?: [];
        $user->setFirstname($nameParts[0]);
        $user->setLastname($nameParts[1] ?? '');


        $user->setRoles(['ROLE_CONNECTED']);

        $hashedPassword = $passwordHasher->hashPassword($user, $password);
        $user->setPassword($hashedPassword);

        $em->persist($user);
        $em->flush();

        $logger->logAction(LogAction::ACTION_REGISTER, $user, $request, null, "Inscription : {$email}");

        try {
            $token = $jwtManager->create($user);
        } catch (\Throwable) {
            $logger->error('Erreur JWT à l\'inscription', 'AuthController::register');
            return $this->json(['error' => 'Erreur de configuration JWT'], 500);
        }

        return $this->json([
            'message' => 'Utilisateur créé avec succès',
            'id' => $user->getId(),
            'token' => $token
        ], 201);
    }

    #[Route('/api/logout', name: 'api_logout', methods: ['POST'])]
    public function logout(
        Request             $request,
        LogService          $logger,
        JWTEncoderInterface $jwtEncoder,
        EntityManagerInterface $em
    ): JsonResponse
    {
        /** @var \App\Entity\User|null $user */
        $user = $this->getUser();

        // Si le firewall JWT n'a pas résolu l'utilisateur (token expiré, absent, etc.),
        // on tente de le retrouver en décodant manuellement le token depuis le header.
        if (!$user instanceof User) {
            $authHeader = $request->headers->get('Authorization', '');
            if (str_starts_with($authHeader, 'Bearer ')) {
                try {
                    $payload = $jwtEncoder->decode(substr($authHeader, 7));
                    // LexikJWT stocke l'identifiant sous la clé "username" ou "email"
                    $email = $payload['username'] ?? $payload['email'] ?? null;
                    if ($email) {
                        $found = $em->getRepository(User::class)->findOneBy(['email' => $email]);
                        if ($found instanceof User) {
                            $user = $found;
                        }
                    }
                } catch (\Throwable) {
                    // Token illisible → user reste null, log sans user_id
                }
            }
        }

        $logger->logConnexion(LogConnexion::STATUT_LOGOUT, $request, $user instanceof User ? $user : null);

        return $this->json(['message' => 'Déconnexion réussie'], 200);
    }

    #[Route('/api/me', name: 'api_me', methods: ['GET'])]
    public function me(EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->json(['error' => 'Utilisateur non authentifié'], 401);
        }

        // ── Ressources créées ─────────────────────────────────────────────────
        $resourcesCreated = $em->getRepository(Ressource::class)->count(['createur' => $user]);

        // ── Ressources enregistrées (mise de côté) ────────────────────────────
        $resourcesSaved = (int) $em->createQueryBuilder()
            ->select('COUNT(p.id)')
            ->from(Participation::class, 'p')
            ->where('p.user = :user')
            ->andWhere('p.mise_cote = true')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();

        // ── Note moyenne sur les ressources de l'utilisateur ──────────────────
        $avgRatingRaw = $em->createQueryBuilder()
            ->select('AVG(c.rating)')
            ->from(Comment::class, 'c')
            ->join('c.resource', 'r')
            ->where('r.createur = :user')
            ->andWhere('c.rating > 0')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();

        $avgRating = $avgRatingRaw !== null ? round((float) $avgRatingRaw, 1) : null;

        return $this->json([
            'id'               => $user->getId(),
            'email'            => $user->getUserIdentifier(),
            'firstname'        => $user->getFirstname(),
            'lastname'         => $user->getLastname(),
            'roles'            => $user->getRoles(),
            'joinDate'         => $user->getCreatedAt()?->format('Y-m-d'),
            'resourcesCreated' => $resourcesCreated,
            'resourcesSaved'   => $resourcesSaved,
            'avgRating'        => $avgRating,
        ]);
    }

    #[Route('/api/auth/login', name: 'api_auth_login', methods: ['POST'])]
    public function login(Request $request, UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $em, JWTTokenManagerInterface $jwtManager, LogService $logger): JsonResponse
    {
        $limiter = $this->loginLimiter->create($request->getClientIp());
        if (!$limiter->consume(1)->isAccepted()) {
            return $this->json(['error' => 'Trop de tentatives. Réessayez dans 15 minutes.'], 429);
        }
        $data = $this->decodeJsonPayload($request);

        if ($data === null) {
            return $this->json(['error' => 'Corps JSON invalide ou vide'], 400);
        }

        $email = strtolower(trim((string)($data['email'] ?? '')));
        $password = (string)($data['password'] ?? '');

        if ($email === '' || $password === '') {
            return $this->json(['error' => 'Email et mot de passe requis'], 400);
        }

        $user = $em->getRepository(User::class)->findOneBy(['email' => $email]);

        // Cas 1 : email inconnu → user_id null (on ne peut pas faire mieux)
        if (!$user) {
            $logger->logConnexion(LogConnexion::STATUT_FAILURE, $request, null);
            return $this->json(['error' => 'Email ou mot de passe incorrect'], 401);
        }

        // Cas 2 : email connu mais mot de passe erroné → user_id renseigné
        if (!$passwordHasher->isPasswordValid($user, $password)) {
            $logger->logConnexion(LogConnexion::STATUT_FAILURE, $request, $user);
            return $this->json(['error' => 'Email ou mot de passe incorrect'], 401);
        }

        if (!$user->isAvaible()) {
            $logger->logConnexion(LogConnexion::STATUT_SUSPENDED, $request, $user);
            return $this->json(['error' => 'Votre compte est suspendu. Impossible de vous connecter.'], 403);
        }

        try {
            $token = $jwtManager->create($user);
        } catch (\Throwable) {
            $logger->error('Erreur JWT à la connexion', 'AuthController::login');
            return $this->json(['error' => 'Erreur de configuration JWT'], 500);
        }

        $logger->logConnexion(LogConnexion::STATUT_SUCCESS, $request, $user);

        return $this->json([
            'token' => $token,
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'name' => $user->getFirstname() . ' ' . $user->getLastname(),
                'role' => $user->getRoles()[0] ?? 'Citoyen'
            ]
        ]);
    }

    #[Route('/api/auth/refresh', name: 'api_auth_refresh', methods: ['POST'])]
    public function refresh(Request $request, JWTTokenManagerInterface $jwtManager): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['error' => 'Token invalide ou expiré'], 401);
        }

        try {
            $token = $jwtManager->create($user);
        } catch (\Throwable) {
            return $this->json(['error' => 'Erreur de configuration JWT'], 500);
        }

        return $this->json([
            'token' => $token,
            'expiresIn' => 3600
        ]);
    }

    #[Route('/api/auth/forgot-password', name: 'api_auth_forgot_password', methods: ['POST'])]
    public function forgotPassword(Request $request, EntityManagerInterface $em, PasswordResetMailer $mailer, LogService $logger): JsonResponse
    {
        $limiter = $this->forgotPasswordLimiter->create($request->getClientIp());
        if (!$limiter->consume(1)->isAccepted()) {
            return $this->json(['error' => 'Trop de tentatives. Réessayez dans 1 heure.'], 429);
        }
        $data = $this->decodeJsonPayload($request);

        if ($data === null) {
            return $this->json(['error' => 'Corps JSON invalide ou vide'], 400);
        }

        $email = strtolower(trim((string)($data['email'] ?? '')));

        if ($email === '') {
            return $this->json(['error' => 'Email requis'], 400);
        }

        // Normaliser l'email en minuscules
        $user = $em->getRepository(User::class)->findOneBy(['email' => $email]);
        if (!$user) {
            // Pour sécurité, on ne révèle pas si l'email existe ou pas
            // On trace quand même la tentative (email inconnu) pour la surveillance sécurité
            $logger->logAction(LogAction::ACTION_PASSWORD_RESET_REQUEST, null, $request, null, "Demande reset mdp — email inconnu : {$email}");
            return $this->json(['message' => 'Si cet email existe, un lien de réinitialisation a été envoyé']);
        }

        // Nettoyer les anciens tokens
        $em->getRepository(PasswordResetToken::class)->cleanupExpiredTokens();

        // Générer un token sécurisé
        $token = bin2hex(random_bytes(32));

        // Créer le token de réinitialisation
        $resetToken = new PasswordResetToken();
        $resetToken->setToken($token);
        $resetToken->setUser($user);

        $em->persist($resetToken);
        $em->flush();

        $logger->logAction(LogAction::ACTION_PASSWORD_RESET_REQUEST, $user, $request, null, "Demande reset mdp : {$email}");

        // Envoyer l'email de réinitialisation
        try {
            $mailer->sendPasswordResetEmail($user->getEmail(), $token);
        } catch (\Throwable) {
            // Echec silencieux : on ne révèle pas d'infos sur l'infrastructure
        }

        return $this->json(['message' => 'Si cet email existe, un lien de réinitialisation a été envoyé']);
    }

    #[Route('/api/auth/reset-password', name: 'api_auth_reset_password', methods: ['POST'])]
    public function resetPassword(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher, LogService $logger): JsonResponse
    {
        $data = $this->decodeJsonPayload($request);

        if ($data === null) {
            return $this->json(['error' => 'Corps JSON invalide ou vide'], 400);
        }

        if (empty($data['token']) || empty($data['password'])) {
            return $this->json(['error' => 'Token et nouveau mot de passe requis'], 400);
        }

        if (!empty($data['password_confirm']) && $data['password_confirm'] !== $data['password']) {
            return $this->json(['error' => 'Les mots de passe ne correspondent pas'], 400);
        }

        $resetToken = $em->getRepository(PasswordResetToken::class)->findValidToken($data['token']);
        if (!$resetToken) {
            return $this->json(['error' => 'Token invalide ou expiré'], 400);
        }

        $user = $resetToken->getUser();
        if (!$user instanceof User) {
            return $this->json(['error' => 'Utilisateur introuvable'], 404);
        }

        $hashedPassword = $passwordHasher->hashPassword($user, $data['password']);
        $user->setPassword($hashedPassword);

        $resetToken->setUsed(true);

        $em->flush();

        $logger->logAction(LogAction::ACTION_PASSWORD_RESET, $user, $request, null, "Mot de passe réinitialisé : {$user->getEmail()}");

        return $this->json(['message' => 'Mot de passe réinitialisé avec succès']);
    }

    private function decodeJsonPayload(Request $request): ?array
    {
        try {
            $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return null;
        }

        return is_array($data) ? $data : null;
    }
}
