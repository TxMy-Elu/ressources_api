<?php

namespace App\Controller\Api;

use App\Entity\Comment;
use App\Entity\LogAction;
use App\Entity\PasswordResetToken;
use App\Entity\Ressource;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\LogService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/users')]
final class UserController extends AbstractController
{
    #[Route('', name: 'api_users_list', methods: ['GET'])]
    public function list(Request $request, UserRepository $repo): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $page = max(1, (int) $request->query->get('page', 1));
        $limit = min(50, max(1, (int) $request->query->get('limit', 50)));
        $search = $request->query->get('search');
        $role = $request->query->get('role');
        $status = $request->query->get('status');

        $qb = $repo->createQueryBuilder('u');

        if ($search) {
            $qb->andWhere('u.email LIKE :search OR u.firstname LIKE :search OR u.lastname LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        if ($role) {
            $escapedRole = str_replace(['%', '_'], ['\\%', '\\_'], $role);
            $qb->andWhere('u.roles LIKE :role')->setParameter('role', '%"' . $escapedRole . '"%');
        }

        if (!empty($status)) {
            $qb->andWhere('u.is_avaible = :status')->setParameter('status', $status === 'Actif');
        }

        $qb->setFirstResult(($page - 1) * $limit)
           ->setMaxResults($limit);

        $users = $qb->getQuery()->getResult();

        $data = array_map(fn($u) => [
            'id' => $u->getId(),
            'name' => $u->getFirstname() . ' ' . $u->getLastname(),
            'email' => $u->getEmail(),
            'role' => $u->getRoles()[0] ?? 'Citoyen',
            'status' => $u->isAvaible() ? 'Actif' : 'Inactif',
            'joinDate' => $u->getCreatedAt()?->format('Y-m-d'),
            'resourceCount' => 0,
            'lastLogin' => null
        ], $users);

        $total = $repo->count([]);

        return $this->json([
            'data' => $data,
            'pagination' => [
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
                'pages' => (int) ceil($total / $limit)
            ]
        ]);
    }

    #[Route('/{id}', name: 'api_users_show', methods: ['GET'])]
    public function show(int $id, UserRepository $repo): JsonResponse
    {
        $user = $repo->find($id);
        if (!$user) {
            return $this->json(['error' => 'Utilisateur non trouvé'], 404);
        }

        // Allow self or admin
        if ($this->getUser() !== $user && !$this->isGranted('ROLE_ADMIN')) {
            return $this->json(['error' => 'Accès refusé'], 403);
        }

        return $this->json([
            'id' => $user->getId(),
            'name' => $user->getFirstname() . ' ' . $user->getLastname(),
            'email' => $user->getEmail(),
            'role' => $user->getRoles()[0] ?? 'Citoyen',
            'status' => $user->isAvaible() ? 'Actif' : 'Inactif',
            'joinDate' => $user->getCreatedAt()?->format('Y-m-d'),
            'resourceCount' => 0, // TODO
            'bio' => '', // TODO: add field
            'avatar' => '', // TODO
            'lastLogin' => null // TODO
        ]);
    }

    #[Route('/{id}', name: 'api_users_update', methods: ['PUT'])]
    public function update(int $id, Request $request, UserRepository $repo, EntityManagerInterface $em, LogService $logger): JsonResponse
    {
        $user = $repo->find($id);
        if (!$user) {
            return $this->json(['error' => 'Utilisateur non trouvé'], 404);
        }

        if ($this->getUser() !== $user && !$this->isGranted('ROLE_ADMIN')) {
            return $this->json(['error' => 'Accès refusé'], 403);
        }

        $data = json_decode($request->getContent(), true);

        if (!empty($data['name'])) {
            $nameParts = explode(' ', $data['name'], 2);
            $user->setFirstname($nameParts[0]);
            $user->setLastname($nameParts[1] ?? '');
        }

        if (!empty($data['email'])) {
            $existing = $em->getRepository(User::class)->findOneBy(['email' => $data['email']]);
            if ($existing && $existing !== $user) {
                return $this->json(['error' => 'Email déjà utilisé'], 400);
            }
            $user->setEmail($data['email']);
        }

        if (isset($data['status'])) {
            $user->setIsAvaible($data['status'] === 'Actif');
        }

        // TODO: bio, avatar

        $em->flush();

        /** @var User|null $actor */
        $actor = $this->getUser();
        $logger->logAction(
            LogAction::ACTION_USER_UPDATE,
            $actor instanceof User ? $actor : null,
            $request,
            null,
            "Profil utilisateur #{$id} ({$user->getEmail()}) mis à jour"
        );

        return $this->json(['message' => 'Utilisateur mis à jour']);
    }

    #[Route('/{id}', name: 'api_users_delete', methods: ['DELETE'])]
    public function delete(int $id, Request $request, UserRepository $repo, EntityManagerInterface $em, LogService $logger): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_SUPER_ADMIN');

        $user = $repo->find($id);
        if (!$user) {
            return $this->json(['error' => 'Utilisateur non trouvé'], 404);
        }

        // Capturer les infos avant suppression (l'objet sera détaché après remove)
        $deletedEmail = $user->getEmail();
        $deletedId    = $user->getId();

        // 1. Clear ManyToMany favourites (removes rows from user_favorites join table)
        $user->getFavorites()->clear();
        $em->flush();

        // 2. Delete password reset tokens
        $em->createQueryBuilder()
            ->delete(PasswordResetToken::class, 't')
            ->where('t.user = :user')
            ->setParameter('user', $user)
            ->getQuery()->execute();

        // 3. Delete comments authored by this user
        $em->createQueryBuilder()
            ->delete(Comment::class, 'c')
            ->where('c.author = :user')
            ->setParameter('user', $user)
            ->getQuery()->execute();

        // 4. Delete resources created by this user
        //    (cascade will remove their associated comments via Ressource → Comment)
        $em->createQueryBuilder()
            ->delete(Comment::class, 'c')
            ->where('c.resource IN (SELECT r.id FROM ' . Ressource::class . ' r WHERE r.createur = :user)')
            ->setParameter('user', $user)
            ->getQuery()->execute();

        $em->createQueryBuilder()
            ->delete(Ressource::class, 'r')
            ->where('r.createur = :user')
            ->setParameter('user', $user)
            ->getQuery()->execute();

        // 5. Finally remove the user
        $em->remove($user);
        $em->flush();

        /** @var User|null $actor */
        $actor = $this->getUser();
        $logger->logAction(
            LogAction::ACTION_USER_DELETE,
            $actor instanceof User ? $actor : null,
            $request,
            null,
            "Compte utilisateur #{$deletedId} ({$deletedEmail}) supprimé définitivement"
        );

        return $this->json(['message' => 'Utilisateur supprimé']);
    }
}
