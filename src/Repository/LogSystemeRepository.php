<?php

namespace App\Repository;

use App\Entity\LogSysteme;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LogSysteme>
 */
class LogSystemeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LogSysteme::class);
    }

    /**
     * Retourne les logs système paginés avec filtres optionnels.
     *
     * @return array{data: LogSysteme[], total: int}
     */
    public function findPaginated(int $page = 1, int $limit = 50, ?string $niveau = null): array
    {
        $qb = $this->createQueryBuilder('l')
            ->orderBy('l.date_log', 'DESC');

        if ($niveau !== null) {
            $qb->andWhere('l.niveau = :niveau')->setParameter('niveau', $niveau);
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
    public function cleanOlderThan(int $days = 30): int
    {
        $limit = new \DateTime("-{$days} days");
        return $this->createQueryBuilder('l')
            ->delete()
            ->where('l.date_log < :limit')
            ->setParameter('limit', $limit)
            ->getQuery()
            ->execute();
    }
}
