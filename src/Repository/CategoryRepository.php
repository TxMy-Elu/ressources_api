<?php

namespace App\Repository;

use App\Entity\Category;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Category>
 */
class CategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Category::class);
    }

    public function findByNom(string $nom): ?Category
    {
        return $this->findOneBy(['nom' => $nom]);
    }

    /** @return Category[] */
    public function findAllOrdered(): array
    {
        return $this->createQueryBuilder('c')
            ->orderBy('c.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /** @return Category[] */
    public function findWithResources(): array
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.resources', 'r')
            ->addSelect('r')
            ->orderBy('c.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function countAll(): int
    {
        return (int) $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }
}
