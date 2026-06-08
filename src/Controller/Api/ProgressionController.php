<?php

namespace App\Controller\Api;

use App\Entity\LogAction;
use App\Entity\Progression;
use App\Entity\User;
use App\Repository\ProgressionRepository;
use App\Repository\RessourceRepository;
use App\Service\LogService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Gère la progression d'un utilisateur sur une ressource.
 * statut : false = en cours, true = terminé
 */
#[Route('/api/resources/{id}/progression')]
final class ProgressionController extends AbstractController
{
    /**
     * GET /api/resources/{id}/progression
     * Retourne la progression de l'utilisateur connecté pour cette ressource.
     */
    #[Route('', name: 'api_progression_get', methods: ['GET'])]
    public function get(
        int                  $id,
        RessourceRepository  $ressourceRepo,
        ProgressionRepository $repo
    ): JsonResponse {
        $this->denyAccessUnlessGranted('ROLE_CONNECTED');

        $ressource = $ressourceRepo->find($id);
        if (!$ressource) {
            return $this->json(['error' => 'Ressource introuvable'], 404);
        }

        /** @var User $user */
        $user       = $this->getUser();
        $progression = $repo->findOneByUserAndRessource($user, $ressource);

        if (!$progression) {
            return $this->json(['started' => false, 'completed' => false]);
        }

        return $this->json([
            'started'    => true,
            'completed'  => $progression->isStatut(),
            'updated_at' => $progression->getUpdatedAt()->format('Y-m-d H:i:s'),
        ]);
    }

    /**
     * POST /api/resources/{id}/progression
     * Crée ou met à jour la progression.
     * Corps JSON : { "statut": true|false }
     */
    #[Route('', name: 'api_progression_post', methods: ['POST'])]
    public function update(
        int                   $id,
        Request               $request,
        RessourceRepository   $ressourceRepo,
        ProgressionRepository $repo,
        EntityManagerInterface $em,
        LogService            $logger
    ): JsonResponse {
        $this->denyAccessUnlessGranted('ROLE_CONNECTED');

        $ressource = $ressourceRepo->find($id);
        if (!$ressource) {
            return $this->json(['error' => 'Ressource introuvable'], 404);
        }

        $data = json_decode($request->getContent(), true) ?? [];
        if (!isset($data['statut'])) {
            return $this->json(['error' => 'Champ "statut" requis (boolean)'], 400);
        }

        /** @var User $user */
        $user        = $this->getUser();
        $progression = $repo->findOneByUserAndRessource($user, $ressource);
        $isNew       = false;

        if (!$progression) {
            $progression = new Progression($user, $ressource);
            $em->persist($progression);
            $isNew = true;
        }

        $statut = (bool) $data['statut'];
        $progression->setStatut($statut);
        $em->flush();

        $logger->logAction(
            LogAction::ACTION_RESOURCE_PROGRESS,
            $user,
            $request,
            $ressource,
            sprintf(
                'Progression ressource #%d « %s » → %s',
                $id,
                $ressource->getTitre(),
                $statut ? 'terminée' : 'en cours'
            )
        );

        return $this->json([
            'message'   => $isNew ? 'Progression créée' : 'Progression mise à jour',
            'completed' => $statut,
        ], $isNew ? 201 : 200);
    }

    /**
     * GET /api/users/me/progressions
     * Liste toutes les progressions de l'utilisateur connecté.
     */
    #[Route('/api/users/me/progressions', name: 'api_progression_mine', methods: ['GET'])]
    public function mine(ProgressionRepository $repo): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_CONNECTED');

        /** @var User $user */
        $user        = $this->getUser();
        $progressions = $repo->findByUser($user);

        $data = array_map(fn(Progression $p) => [
            'ressource_id' => $p->getRessource()->getId(),
            'titre'        => $p->getRessource()->getTitre(),
            'completed'    => $p->isStatut(),
            'updated_at'   => $p->getUpdatedAt()->format('Y-m-d H:i:s'),
        ], $progressions);

        return $this->json($data);
    }
}
