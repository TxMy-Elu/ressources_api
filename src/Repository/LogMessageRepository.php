<?php

namespace App\Repository;

use App\Entity\LogMessage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class LogMessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LogMessage::class);
    }

    public function findPaginated(int $page = 1, int $limit = 50, ?string $action = null): array
    {
        $qb = $this->createQueryBuilder('l')
            ->leftJoin('l.user', 'u')->addSelect('u')
            ->leftJoin('l.comment', 'c')->addSelect('c')
            ->orderBy('l.date_action', 'DESC');

        if ($action) {
            $qb->andWhere('l.action = :action')->setParameter('action', $action);
        }

        $total = (clone $qb)->select('COUNT(l.id)')->getQuery()->getSingleScalarResult();

        $data = $qb->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return ['data' => $data, 'total' => (int) $total];
    }
}
