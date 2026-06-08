<?php

namespace App\Controller\Api;

use App\Entity\Ressource;
use App\Repository\RessourceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api')]
final class SearchController extends AbstractController
{
    #[Route('/search', name: 'api_search', methods: ['GET'])]
    public function search(Request $request, RessourceRepository $repo): JsonResponse
    {
        $q = $request->query->get('q');
        $category = $request->query->get('category');
        $type = $request->query->get('type');
        $sort = $request->query->get('sort', 'created_at');

        $qb = $repo->createQueryBuilder('r')
            ->leftJoin('r.category', 'c')
            ->addSelect('c')
            ->where('r.statut = :statut')
            ->setParameter('statut', Ressource::STATUS_PUBLISHED);

        if ($q) {
            $qb->andWhere('r.titre LIKE :q OR r.description LIKE :q OR r.contenu LIKE :q')
               ->setParameter('q', '%' . $q . '%');
        }

        if ($category) {
            $qb->andWhere('c.nom = :category')->setParameter('category', $category);
        }

        if ($type) {
            $qb->andWhere('r.type_ressource = :type')->setParameter('type', $type);
        }

        $sortMap = ['created_at' => 'created_at', 'titre' => 'titre', 'date_publication' => 'date_publication'];
        $qb->orderBy('r.' . ($sortMap[$sort] ?? 'created_at'), 'DESC');

        $results = $qb->getQuery()->getResult();

        $data = array_map(fn($r) => [
            'id' => $r->getId(),
            'titre' => $r->getTitre(),
            'description' => $r->getDescription(),
            'type' => $r->getTypeRessource(),
            'category' => $r->getCategory()?->getNom(),
        ], $results);

        return $this->json([
            'results' => $data,
            'count' => count($data)
        ]);
    }

    #[Route('/resources/trending', name: 'api_resources_trending', methods: ['GET'])]
    public function trending(Request $request, RessourceRepository $repo): JsonResponse
    {
        $limit = min(10, max(1, (int) $request->query->get('limit', 10)));
        $period = $request->query->get('period', 'week');

        // For simplicity, just get recent published resources
        $qb = $repo->createQueryBuilder('r')
            ->where('r.statut = :statut')
            ->setParameter('statut', Ressource::STATUS_PUBLISHED)
            ->orderBy('r.created_at', 'DESC')
            ->setMaxResults($limit);

        $resources = $qb->getQuery()->getResult();

        $data = array_map(fn($r) => [
            'id' => $r->getId(),
            'titre' => $r->getTitre(),
            'description' => $r->getDescription(),
        ], $resources);

        return $this->json(['data' => $data]);
    }
}
