<?php

namespace App\Repository;

use App\Entity\Participation;
use App\Entity\Ressource;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ParticipationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Participation::class);
    }

    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('p')
            ->innerJoin('p.ressource', 'r')
            ->addSelect('r')
            ->where('p.user = :user')
            ->setParameter('user', $user)
            ->orderBy('p.date_participation', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findOneByUserAndRessource(User $user, Ressource $ressource): ?Participation
    {
        return $this->findOneBy(['user' => $user, 'ressource' => $ressource]);
    }

    /** Nombre de participations (consultations) d'une ressource */
    public function countByRessource(Ressource $ressource): int
    {
        return (int) $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->where('p.ressource = :r')
            ->setParameter('r', $ressource)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
