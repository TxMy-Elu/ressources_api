<?php

namespace App\Repository;

use App\Entity\LogAction;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LogAction>
 */
class LogActionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LogAction::class);
    }

    /**
     * Retourne les logs d'action paginés avec filtres optionnels.
     *
     * @return array{data: LogAction[], total: int}
     */
    public function findPaginated(int $page = 1, int $limit = 50, ?string $action = null, ?int $userId = null): array
    {
        $qb = $this->createQueryBuilder('l')
            ->leftJoin('l.user', 'u')->addSelect('u')
            ->leftJoin('l.ressource', 'r')->addSelect('r')
            ->orderBy('l.date_action', 'DESC');

        if ($action !== null) {
            $qb->andWhere('l.action = :action')->setParameter('action', $action);
        }
        if ($userId !== null) {
            $qb->andWhere('u.id = :userId')->setParameter('userId', $userId);
        }

        $total = (clone $qb)->select('COUNT(l.id)')->getQuery()->getSingleScalarResult();

        $data = $qb->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return ['data' => $data, 'total' => (int) $total];
    }

    /**
     * Compte les vues (action = resource_view) par ressource.
     *
     * @param int[] $ressourceIds
     * @return array<int, int>  [ressource_id => nb_vues]
     */
    public function countViewsByRessource(array $ressourceIds): array
    {
        if (empty($ressourceIds)) {
            return [];
        }

        $rows = $this->createQueryBuilder('l')
            ->select('IDENTITY(l.ressource) AS rid, COUNT(l.id) AS cnt')
            ->where('l.action = :action')
            ->andWhere('l.ressource IN (:ids)')
            ->setParameter('action', LogAction::ACTION_RESOURCE_VIEW)
            ->setParameter('ids', $ressourceIds)
            ->groupBy('l.ressource')
            ->getQuery()
            ->getScalarResult();

        $map = [];
        foreach ($rows as $row) {
            $map[(int) $row['rid']] = (int) $row['cnt'];
        }
        return $map;
    }

    /**
     * Nombre d'actions enregistrées depuis $since.
     */
    public function countSince(\DateTimeInterface $since): int
    {
        return (int) $this->createQueryBuilder('l')
            ->select('COUNT(l.id)')
            ->where('l.date_action >= :since')
            ->setParameter('since', $since)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Supprime les logs plus anciens que $days jours.
     */
    public function cleanOlderThan(int $days = 180): int
    {
        $limit = new \DateTime("-{$days} days");
        return $this->createQueryBuilder('l')
            ->delete()
            ->where('l.date_action < :limit')
            ->setParameter('limit', $limit)
            ->getQuery()
            ->execute();
    }
}
