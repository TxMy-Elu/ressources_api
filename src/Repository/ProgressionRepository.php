<?php

namespace App\Repository;

use App\Entity\Progression;
use App\Entity\Ressource;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ProgressionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Progression::class);
    }

    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('p')
            ->innerJoin('p.ressource', 'r')
            ->addSelect('r')
            ->where('p.user = :user')
            ->setParameter('user', $user)
            ->orderBy('p.updated_at', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findOneByUserAndRessource(User $user, Ressource $ressource): ?Progression
    {
        return $this->findOneBy(['user' => $user, 'ressource' => $ressource]);
    }
}
