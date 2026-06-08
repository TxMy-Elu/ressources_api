<?php

namespace App\Repository;

use App\Entity\LogConnexion;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LogConnexion>
 */
class LogConnexionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LogConnexion::class);
    }

    /**
     * Retourne les logs de connexion paginés avec filtres optionnels.
     *
     * @param int         $page
     * @param int         $limit
     * @param string|null $statut  Filtrer par statut (success/failure/suspended/logout)
     * @param int|null    $userId  Filtrer par utilisateur
     * @return array{data: LogConnexion[], total: int}
     */
    public function findPaginated(int $page = 1, int $limit = 50, ?string $statut = null, ?int $userId = null): array
    {
        $qb = $this->createQueryBuilder('l')
            ->leftJoin('l.user', 'u')
            ->addSelect('u')
            ->orderBy('l.date_connexion', 'DESC');

        if ($statut !== null) {
            $qb->andWhere('l.statut = :statut')->setParameter('statut', $statut);
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
     * Supprime les logs plus anciens que $days jours.
     */
    public function cleanOlderThan(int $days = 90): int
    {
        $limit = new \DateTime("-{$days} days");
        return $this->createQueryBuilder('l')
            ->delete()
            ->where('l.date_connexion < :limit')
            ->setParameter('limit', $limit)
            ->getQuery()
            ->execute();
    }
}
