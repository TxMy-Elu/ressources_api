<?php

namespace App\Controller\Api;

use App\Entity\Comment;
use App\Entity\LogAction;
use App\Entity\Ressource;
use App\Entity\User;
use App\Repository\CommentRepository;
use App\Repository\RessourceRepository;
use App\Service\LogService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Nelmio\ApiDocBundle\Attribute\Security;
use OpenApi\Attributes as OA;

#[Route('/api/moderation')]
final class ModerationController extends AbstractController
{

    #[Route('/pending', name: 'api_moderation_pending', methods: ['GET'])]
    #[OA\Get(
        path: '/api/moderation/pending',
        description: 'Retourne toutes les ressources soumises et en attente de validation. Réservé aux modérateurs. Nécessite un token JWT.',
        summary: 'Lister les ressources en attente de modération',
    )]
    #[Security(name: 'Bearer')]
    #[OA\Response(
        response: 200,
        description: 'Liste des ressources en attente',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(
                properties: [
                    new OA\Property(property: 'id', type: 'integer', example: 1),
                    new OA\Property(property: 'titre', type: 'string', example: 'Mieux communiquer en famille'),
                    new OA\Property(property: 'createur', type: 'string', example: 'Dupont'),
                    new OA\Property(property: 'created_at', type: 'string', example: '2024-01-15 10:30:00'),
                    ]
            )
        )
    )]
    #[OA\Response(response: 401, description: 'Token JWT manquant ou invalide')]
    #[OA\Response(response: 403, description: 'Accès refusé — Modérateur requis')]
    public function pending(RessourceRepository $repo): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_MODERATOR');

        $resources = $repo->findBy(['statut' => Ressource::STATUS_PENDING]);

        $data = array_map(fn($r) => [
            'id'           => $r->getId(),
            'titre'        => $r->getTitre(),
            'description'  => $r->getDescription(),
            'contenu'      => $r->getContenu(),
            'type'         => $r->getTypeRessource(),
            'category'     => $r->getCategory()?->getNom(),
            'visibilite'   => $r->getVisibilite(),
            'lien'         => $r->getLien(),
            'media'        => $r->getMedia(),
            'createur'     => $r->getCreateur()
                ? $r->getCreateur()->getFirstname() . ' ' . $r->getCreateur()->getLastname()
                : null,
            'dateCreation' => $r->getCreatedAt()?->format('Y-m-d H:i:s'),
        ], $resources);

        return $this->json($data);
    }


    #[Route('/validate/{id}', name: 'api_moderation_validate', methods: ['POST'])]
    #[OA\Post(
        path: '/api/moderation/validate/{id}',
        description: 'Valide une ressource en attente et la publie. Réservé aux modérateurs. Nécessite un token JWT.',
        summary: 'Valider et publier une ressource',
    )]
    #[Security(name: 'Bearer')]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\Response(response: 200, description: 'Ressource publiée avec succès')]
    #[OA\Response(response: 401, description: 'Token JWT manquant ou invalide')]
    #[OA\Response(response: 403, description: 'Accès refusé — Modérateur requis')]
    #[OA\Response(response: 404, description: 'Ressource introuvable')]
    public function validate(int $id, Request $request, RessourceRepository $repo, EntityManagerInterface $em, LogService $logger): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_MODERATOR');

        $resource = $repo->find($id);
        if (!$resource) {
            return $this->json(['error' => 'Ressource introuvable'], Response::HTTP_NOT_FOUND);
        }

        $resource->setStatut(Ressource::STATUS_PUBLISHED);
        $resource->setDatePublication(new \DateTimeImmutable());
        $resource->setEstVerifie(true);
        $em->flush();

        $moderateur = $this->getUser();
        $logger->logAction(
            LogAction::ACTION_MODERATE_VALIDATE,
            $moderateur instanceof User ? $moderateur : null,
            $request,
            $resource,
            "Ressource #{$id} « {$resource->getTitre()} » publiée"
        );

        return $this->json(['message' => 'Ressource publiée avec succès']);
    }

    #[Route('/suspend/{id}', name: 'api_moderation_suspend', methods: ['POST'])]
    #[OA\Post(
        path: '/api/moderation/suspend/{id}',
        description: 'Suspend une ressource (refus ou retrait). Réservé aux modérateurs. Nécessite un token JWT.',
        summary: 'Suspendre une ressource',
    )]
    #[Security(name: 'Bearer')]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\Response(response: 200, description: 'Ressource suspendue')]
    #[OA\Response(response: 401, description: 'Token JWT manquant ou invalide')]
    #[OA\Response(response: 403, description: 'Accès refusé — Modérateur requis')]
    #[OA\Response(response: 404, description: 'Ressource introuvable')]
    public function suspend(int $id, Request $request, RessourceRepository $repo, EntityManagerInterface $em, LogService $logger): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_MODERATOR');

        $resource = $repo->find($id);
        if (!$resource) {
            return $this->json(['error' => 'Ressource introuvable'], Response::HTTP_NOT_FOUND);
        }

        $resource->setStatut(Ressource::STATUS_SUSPENDED);
        $em->flush();

        $moderateur = $this->getUser();
        $logger->logAction(
            LogAction::ACTION_MODERATE_SUSPEND,
            $moderateur instanceof User ? $moderateur : null,
            $request,
            $resource,
            "Ressource #{$id} « {$resource->getTitre()} » suspendue"
        );

        return $this->json(['message' => 'Ressource suspendue avec succès']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  Commentaires — liste des commentaires signalés / tous
    //  GET /api/moderation/comments
    // ─────────────────────────────────────────────────────────────────────────

    #[Route('/comments', name: 'api_moderation_comments', methods: ['GET'])]
    public function comments(Request $request, CommentRepository $repo): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_MODERATOR');

        $page  = max(1, (int) $request->query->get('page', 1));
        $limit = min(50, max(1, (int) $request->query->get('limit', 20)));

        $qb = $repo->createQueryBuilder('c')
            ->leftJoin('c.author', 'u')->addSelect('u')
            ->leftJoin('c.resource', 'r')->addSelect('r')
            ->orderBy('c.createdAt', 'DESC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);

        $comments = $qb->getQuery()->getResult();
        $total    = $repo->count([]);

        $data = array_map(fn(Comment $c) => [
            'id'         => $c->getId(),
            'content'    => $c->getContent(),
            'rating'     => $c->getRating(),
            'createdAt'  => $c->getCreatedAt()?->format('Y-m-d H:i:s'),
            'author'     => $c->getAuthor()
                ? ['id' => $c->getAuthor()->getId(), 'name' => $c->getAuthor()->getFirstname() . ' ' . $c->getAuthor()->getLastname()]
                : null,
            'resource'   => $c->getResource()
                ? ['id' => $c->getResource()->getId(), 'titre' => $c->getResource()->getTitre()]
                : null,
        ], $comments);

        return $this->json([
            'data'       => $data,
            'pagination' => [
                'page'  => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => (int) ceil($total / $limit) ?: 1,
            ],
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  Supprimer un commentaire  DELETE /api/moderation/comments/{id}
    // ─────────────────────────────────────────────────────────────────────────

    #[Route('/comments/{id}', name: 'api_moderation_comment_delete', methods: ['DELETE'])]
    public function deleteComment(int $id, Request $request, CommentRepository $repo, EntityManagerInterface $em, LogService $logger): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_MODERATOR');

        $comment = $repo->find($id);
        if (!$comment) {
            return $this->json(['error' => 'Commentaire introuvable'], Response::HTTP_NOT_FOUND);
        }

        $resourceId = $comment->getResource()?->getId();
        $authorId   = $comment->getAuthor()?->getId();

        $em->remove($comment);
        $em->flush();

        /** @var User|null $moderateur */
        $moderateur = $this->getUser();
        $logger->logAction(
            LogAction::ACTION_MODERATE_SUSPEND,
            $moderateur instanceof User ? $moderateur : null,
            $request,
            null,
            "Commentaire #{$id} supprimé (ressource #{$resourceId}, auteur #{$authorId})"
        );

        return $this->json(['message' => 'Commentaire supprimé']);
    }

}
