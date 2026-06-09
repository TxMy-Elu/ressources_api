<?php

namespace App\Controller\Api;

use App\Entity\LogAction;
use App\Entity\Ressource;
use App\Entity\User;
use App\Repository\LogActionRepository;
use App\Repository\LogConnexionRepository;
use App\Repository\LogMessageRepository;
use App\Repository\LogSystemeRepository;
use App\Repository\RessourceRepository;
use App\Repository\UserRepository;
use App\Service\LogService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/admin')]
final class AdminController extends AbstractController
{
    // ─────────────────────────────────────────────────────────────────────────
    //  Statistiques globales
    // ─────────────────────────────────────────────────────────────────────────

    #[Route('/stats', name: 'api_admin_stats', methods: ['GET'])]
    public function stats(
        UserRepository      $userRepo,
        RessourceRepository $resourceRepo,
        LogActionRepository $logActionRepo
    ): JsonResponse {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $totalUsers         = $userRepo->count([]);
        $activeUsers        = $userRepo->count(['is_avaible' => true]);
        $totalResources     = $resourceRepo->count([]);
        $publishedResources = $resourceRepo->count(['statut' => Ressource::STATUS_PUBLISHED]);
        $pendingResources   = $resourceRepo->count(['statut' => Ressource::STATUS_PENDING]);
        $suspendedResources = $resourceRepo->count(['statut' => Ressource::STATUS_SUSPENDED]);

        // ── Nouveaux utilisateurs ce mois ─────────────────────────────────────
        $startOfMonth = new \DateTime('first day of this month midnight');
        $newUsers     = $userRepo->countSince($startOfMonth);

        // ── Activités ce mois / cette semaine ─────────────────────────────────
        $startOfWeek      = new \DateTime('monday this week midnight');
        $activitiesMonth  = $logActionRepo->countSince($startOfMonth);
        $activitiesWeek   = $logActionRepo->countSince($startOfWeek);

        return $this->json([
            'users' => [
                'total'  => $totalUsers,
                'active' => $activeUsers,
                'new'    => $newUsers,
            ],
            'resources' => [
                'total'     => $totalResources,
                'published' => $publishedResources,
                'pending'   => $pendingResources,
                'suspended' => $suspendedResources,
            ],
            'activities' => [
                'thisMonth' => $activitiesMonth,
                'thisWeek'  => $activitiesWeek,
            ],
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  Logs de connexion  GET /api/admin/logs/connexion
    // ─────────────────────────────────────────────────────────────────────────

    #[Route('/logs/connexion', name: 'api_admin_logs_connexion', methods: ['GET'])]
    public function logsConnexion(Request $request, LogConnexionRepository $repo): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $page   = max(1, (int) $request->query->get('page', 1));
        $limit  = min(100, max(1, (int) $request->query->get('limit', 50)));
        $statut = $request->query->get('statut');
        $userId = $request->query->get('userId') !== null ? (int) $request->query->get('userId') : null;

        $result = $repo->findPaginated($page, $limit, $statut ?: null, $userId);

        $data = array_map(fn($l) => [
            'id'             => $l->getId(),
            'statut'         => $l->getStatut(),
            'ip_address'     => $l->getIpAddress(),
            'date_connexion' => $l->getDateConnexion()->format('Y-m-d H:i:s'),
            'user'           => $l->getUser()
                ? [
                    'id'    => $l->getUser()->getId(),
                    'email' => $l->getUser()->getEmail(),
                    'name'  => $l->getUser()->getFirstname() . ' ' . $l->getUser()->getLastname(),
                ]
                : null,
        ], $result['data']);

        return $this->json([
            'data'       => $data,
            'pagination' => [
                'page'  => $page,
                'limit' => $limit,
                'total' => $result['total'],
                'pages' => (int) ceil($result['total'] / $limit) ?: 1,
            ],
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  Logs d'action  GET /api/admin/logs/actions
    // ─────────────────────────────────────────────────────────────────────────

    #[Route('/logs/actions', name: 'api_admin_logs_actions', methods: ['GET'])]
    public function logsActions(Request $request, LogActionRepository $repo): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $page   = max(1, (int) $request->query->get('page', 1));
        $limit  = min(100, max(1, (int) $request->query->get('limit', 50)));
        $action = $request->query->get('action');
        $userId = $request->query->get('userId') !== null ? (int) $request->query->get('userId') : null;

        $result = $repo->findPaginated($page, $limit, $action ?: null, $userId);

        $data = array_map(fn($l) => [
            'id'          => $l->getId(),
            'action'      => $l->getAction(),
            'details'     => $l->getDetails(),
            'ip_address'  => $l->getIpAddress(),
            'date_action' => $l->getDateAction()->format('Y-m-d H:i:s'),
            'user'        => $l->getUser()
                ? [
                    'id'    => $l->getUser()->getId(),
                    'email' => $l->getUser()->getEmail(),
                    'name'  => $l->getUser()->getFirstname() . ' ' . $l->getUser()->getLastname(),
                ]
                : null,
            'ressource'   => $l->getRessource()
                ? [
                    'id'    => $l->getRessource()->getId(),
                    'titre' => $l->getRessource()->getTitre(),
                ]
                : null,
        ], $result['data']);

        return $this->json([
            'data'       => $data,
            'pagination' => [
                'page'  => $page,
                'limit' => $limit,
                'total' => $result['total'],
                'pages' => (int) ceil($result['total'] / $limit) ?: 1,
            ],
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  Logs système  GET /api/admin/logs/systeme
    // ─────────────────────────────────────────────────────────────────────────

    #[Route('/logs/systeme', name: 'api_admin_logs_systeme', methods: ['GET'])]
    public function logsSysteme(Request $request, LogSystemeRepository $repo): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_SUPER_ADMIN');

        $page   = max(1, (int) $request->query->get('page', 1));
        $limit  = min(100, max(1, (int) $request->query->get('limit', 50)));
        $niveau = $request->query->get('niveau');

        $result = $repo->findPaginated($page, $limit, $niveau ?: null);

        $data = array_map(fn($l) => [
            'id'       => $l->getId(),
            'niveau'   => $l->getNiveau(),
            'message'  => $l->getMessage(),
            'contexte' => $l->getContexte(),
            'date_log' => $l->getDateLog()->format('Y-m-d H:i:s'),
        ], $result['data']);

        return $this->json([
            'data'       => $data,
            'pagination' => [
                'page'  => $page,
                'limit' => $limit,
                'total' => $result['total'],
                'pages' => (int) ceil($result['total'] / $limit) ?: 1,
            ],
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  Logs de message  GET /api/admin/logs/messages
    // ─────────────────────────────────────────────────────────────────────────

    #[Route('/logs/messages', name: 'api_admin_logs_messages', methods: ['GET'])]
    public function logsMessages(Request $request, LogMessageRepository $repo): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $page   = max(1, (int) $request->query->get('page', 1));
        $limit  = min(100, max(1, (int) $request->query->get('limit', 50)));
        $action = $request->query->get('action');

        $result = $repo->findPaginated($page, $limit, $action ?: null);

        $data = array_map(fn($l) => [
            'id'          => $l->getId(),
            'action'      => $l->getAction(),
            'details'     => $l->getDetails(),
            'ip_address'  => $l->getIpAddress(),
            'date_action' => $l->getDateAction()->format('Y-m-d H:i:s'),
            'user'        => $l->getUser()
                ? [
                    'id'    => $l->getUser()->getId(),
                    'email' => $l->getUser()->getEmail(),
                    'name'  => $l->getUser()->getFirstname() . ' ' . $l->getUser()->getLastname(),
                ]
                : null,
            'comment_id' => $l->getComment()?->getId(),
        ], $result['data']);

        return $this->json([
            'data'       => $data,
            'pagination' => [
                'page'  => $page,
                'limit' => $limit,
                'total' => $result['total'],
                'pages' => (int) ceil($result['total'] / $limit) ?: 1,
            ],
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  Promotion d'un utilisateur  PUT /api/admin/users/{id}/promote
    // ─────────────────────────────────────────────────────────────────────────

    #[Route('/users/{id}/promote', name: 'api_promote_user', methods: ['PUT'])]
    public function promoteUser(
        int                    $id,
        Request                $request,
        EntityManagerInterface $em,
        LogService             $logger
    ): JsonResponse {
        $this->denyAccessUnlessGranted('ROLE_SUPER_ADMIN');

        $user = $em->getRepository(User::class)->find($id);
        if (!$user) {
            return $this->json(['message' => 'Utilisateur non trouvé'], 404);
        }

        $data = json_decode($request->getContent(), true);

        if (!is_array($data)) {
            return $this->json(['message' => 'Corps JSON invalide ou vide'], 400);
        }

        $allowedRoles = ['ROLE_CONNECTED', 'ROLE_MODERATOR', 'ROLE_ADMIN', 'ROLE_SUPER_ADMIN'];

        if (empty($data['role']) || !in_array($data['role'], $allowedRoles)) {
            return $this->json(['message' => 'Rôle invalide'], 400);
        }

        $oldRole = $user->getRoles()[0] ?? 'ROLE_CONNECTED';
        $user->setRoles([$data['role']]);
        $em->flush();

        /** @var User|null $admin */
        $admin = $this->getUser();
        $logger->logAction(
            LogAction::ACTION_USER_PROMOTE,
            $admin instanceof User ? $admin : null,
            $request,
            null,
            "Utilisateur #{$id} ({$user->getEmail()}) : {$oldRole} → {$data['role']}"
        );

        return $this->json(['message' => 'Rôle mis à jour avec succès'], 200);
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  Suspension / activation d'un compte  PUT /api/admin/users/{id}/status
    // ─────────────────────────────────────────────────────────────────────────

    #[Route('/users/{id}/status', name: 'api_admin_user_status', methods: ['PUT'])]
    public function userStatus(
        int                    $id,
        Request                $request,
        EntityManagerInterface $em,
        LogService             $logger
    ): JsonResponse {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $user = $em->getRepository(User::class)->find($id);
        if (!$user) {
            return $this->json(['message' => 'Utilisateur non trouvé'], 404);
        }

        $data = json_decode($request->getContent(), true);

        if (!is_array($data) || !isset($data['avaible'])) {
            return $this->json(['message' => 'Champ "avaible" requis (boolean)'], 400);
        }

        $avaible = (bool) $data['avaible'];
        $user->setIsAvaible($avaible);
        $em->flush();

        /** @var User|null $admin */
        $admin = $this->getUser();
        $action = $avaible ? LogAction::ACTION_USER_ACTIVATE : LogAction::ACTION_USER_SUSPEND;
        $label  = $avaible ? 'activé' : 'suspendu';
        $logger->logAction(
            $action,
            $admin instanceof User ? $admin : null,
            $request,
            null,
            "Compte #{$id} ({$user->getEmail()}) {$label}"
        );

        return $this->json([
            'message' => "Compte {$label} avec succès",
            'avaible' => $avaible,
        ]);
    }
}
