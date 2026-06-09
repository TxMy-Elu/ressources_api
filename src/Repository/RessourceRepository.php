<?php

namespace App\Repository;

use App\Entity\Category;
use App\Entity\Ressource;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Ressource>
 */
class RessourceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Ressource::class);
    }

    /** @return Ressource[] */
    public function findPublished(): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.statut = :statut')
            ->andWhere('r.visibilite = :visibilite')
            ->setParameter('statut', Ressource::STATUS_PUBLISHED)
            ->setParameter('visibilite', Ressource::VISIBILITE_PUBLIC)
            ->orderBy('r.date_publication', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /** @return Ressource[] */
    public function findPending(): array
    {
        return $this->findBy(
            ['statut' => Ressource::STATUS_PENDING],
            ['created_at' => 'ASC']
        );
    }

    /** @return Ressource[] */
    public function findByCreateur(User $user): array
    {
        return $this->findBy(['createur' => $user], ['created_at' => 'DESC']);
    }

    /** @return Ressource[] */
    public function findByStatut(string $statut): array
    {
        return $this->findBy(['statut' => $statut]);
    }

    /** @return Ressource[] */
    public function findByCategory(Category $category): array
    {
        return $this->findBy(['category' => $category]);
    }

    public function countByStatut(string $statut): int
    {
        return (int) $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->where('r.statut = :statut')
            ->setParameter('statut', $statut)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /** @return Ressource[] */
    public function findPublishedWithFilters(
        ?string $category,
        ?string $type,
        string  $sort = 'created_at',
        string  $order = 'DESC'
    ): array {
        $allowedSorts = ['created_at', 'titre', 'date_publication'];
        $sort = in_array($sort, $allowedSorts, true) ? $sort : 'created_at';
        $order = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';

        $qb = $this->createQueryBuilder('r')
            ->leftJoin('r.category', 'c')
            ->addSelect('c')
            ->where('r.statut = :statut')
            ->andWhere('r.visibilite = :visibilite')
            ->setParameter('statut', Ressource::STATUS_PUBLISHED)
            ->setParameter('visibilite', Ressource::VISIBILITE_PUBLIC)
            ->orderBy('r.' . $sort, $order);

        if ($category !== null) {
            $qb->andWhere('c.nom = :category')->setParameter('category', $category);
        }

        if ($type !== null) {
            $qb->andWhere('r.type_ressource = :type')->setParameter('type', $type);
        }

        return $qb->getQuery()->getResult();
    }

    public function suspendById(int $id, \Doctrine\ORM\EntityManagerInterface $em): bool
    {
        $resource = $this->find($id);
        if (!$resource) {
            return false;
        }
        $resource->setStatut(Ressource::STATUS_SUSPENDED);
        $em->flush();
        return true;
    }
}
