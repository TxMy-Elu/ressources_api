<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Nombre d'utilisateurs inscrits depuis $since.
     */
    public function countSince(\DateTimeInterface $since): int
    {
        return (int) $this->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->where('u.created_at >= :since')
            ->setParameter('since', $since)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findByEmail(string $email): ?User
    {
        return $this->findOneBy(['email' => $email]);
    }

    public function findActive(): array
    {
        return $this->createQueryBuilder('u')
            ->where('u.is_avaible = :active')
            ->setParameter('active', true)
            ->orderBy('u.lastname', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /** @return User[] */
    public function findByRole(string $role): array
    {
        return $this->createQueryBuilder('u')
            ->where('JSON_CONTAINS(u.roles, :role) = 1')
            ->setParameter('role', json_encode($role))
            ->getQuery()
            ->getResult();
    }

    public function countAll(): int
    {
        return (int) $this->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function activateUser(int $id, \DateTime $activationDate): void
    {
        $this->createQueryBuilder('u')
            ->update()
            ->set('u.is_avaible', ':active')
            ->set('u.date_activation', ':date')
            ->where('u.id = :id')
            ->setParameter('active', true)
            ->setParameter('date', $activationDate)
            ->setParameter('id', $id)
            ->getQuery()
            ->execute();
    }
}
