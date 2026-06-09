<?php

namespace App\Controller\Api;

use App\Entity\LogAction;
use App\Entity\Participation;
use App\Entity\Ressource;
use App\Entity\User;
use App\Repository\ParticipationRepository;
use App\Repository\RessourceRepository;
use App\Service\LogService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Gère la participation d'un utilisateur à une ressource :
 *  - enregistrement de la première consultation
 *  - mise de côté (bookmark)
 */
#[Route('/api/resources/{id}/participation')]
final class ParticipationController extends AbstractController
{
    /**
     * GET /api/resources/{id}/participation
     * Retourne la participation courante de l'utilisateur connecté pour cette ressource.
     */
    #[Route('', name: 'api_participation_get', methods: ['GET'])]
    public function get(
        int                     $id,
        RessourceRepository     $ressourceRepo,
        ParticipationRepository $repo
    ): JsonResponse {
        $this->denyAccessUnlessGranted('ROLE_CONNECTED');

        $ressource = $ressourceRepo->find($id);
        if (!$ressource) {
            return $this->json(['error' => 'Ressource introuvable'], 404);
        }

        /** @var User $user */
        $user          = $this->getUser();
        $participation = $repo->findOneByUserAndRessource($user, $ressource);

        if (!$participation) {
            return $this->json(['participated' => false]);
        }

        return $this->json([
            'participated'      => true,
            'date_participation' => $participation->getDateParticipation()->format('Y-m-d H:i:s'),
            'mise_cote'         => $participation->isMiseCote(),
            'invite'            => $participation->isInvite(),
        ]);
    }

    /**
     * POST /api/resources/{id}/participation
     * Enregistre (ou met à jour) la participation de l'utilisateur.
     * Corps JSON optionnel : { "mise_cote": true|false }
     */
    #[Route('', name: 'api_participation_post', methods: ['POST'])]
    public function participate(
        int                     $id,
        Request                 $request,
        RessourceRepository     $ressourceRepo,
        ParticipationRepository $repo,
        EntityManagerInterface  $em,
        LogService              $logger
    ): JsonResponse {
        $this->denyAccessUnlessGranted('ROLE_CONNECTED');

        $ressource = $ressourceRepo->find($id);
        if (!$ressource) {
            return $this->json(['error' => 'Ressource introuvable'], 404);
        }

        /** @var User $user */
        $user          = $this->getUser();
        $participation = $repo->findOneByUserAndRessource($user, $ressource);
        $data          = json_decode($request->getContent(), true) ?? [];
        $isNew         = false;

        if (!$participation) {
            $participation = new Participation($user, $ressource);
            $em->persist($participation);
            $isNew = true;
        }

        // Mise à jour de la mise_cote si fourni
        if (isset($data['mise_cote'])) {
            $participation->setMiseCote((bool) $data['mise_cote']);
        }

        $em->flush();

        $logger->logAction(
            LogAction::ACTION_RESOURCE_PARTICIPATE,
            $user,
            $request,
            $ressource,
            sprintf(
                '%s ressource #%d « %s »%s',
                $isNew ? 'Première participation' : 'Participation mise à jour',
                $id,
                $ressource->getTitre(),
                isset($data['mise_cote']) ? (' — mise_cote: ' . ($data['mise_cote'] ? 'oui' : 'non')) : ''
            )
        );

        return $this->json([
            'message'           => $isNew ? 'Participation enregistrée' : 'Participation mise à jour',
            'mise_cote'         => $participation->isMiseCote(),
            'date_participation' => $participation->getDateParticipation()->format('Y-m-d H:i:s'),
        ], $isNew ? 201 : 200);
    }

    /**
     * GET /api/users/me/participations
     * Liste toutes les participations de l'utilisateur connecté.
     */
    #[Route('/api/users/me/participations', name: 'api_participation_mine', methods: ['GET'])]
    public function mine(ParticipationRepository $repo): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_CONNECTED');

        /** @var User $user */
        $user           = $this->getUser();
        $participations = $repo->findByUser($user);

        $data = array_map(fn(Participation $p) => [
            'ressource_id'      => $p->getRessource()->getId(),
            'titre'             => $p->getRessource()->getTitre(),
            'date_participation' => $p->getDateParticipation()->format('Y-m-d H:i:s'),
            'mise_cote'         => $p->isMiseCote(),
        ], $participations);

        return $this->json($data);
    }
}
