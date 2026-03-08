<?php

namespace App\Repository;

use App\Entity\Restaurant;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Restaurant>
 */
class RestaurantRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Restaurant::class);
    }

    /**
     * @return Restaurant[]
     */
    public function findTopRated(int $limit = 6): array
    {
        return $this->createQueryBuilder('r')
            ->orderBy('r.rating', 'DESC')
            ->addOrderBy('r.name', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findPaginated(string $sort = 'rating', int $page = 1, int $limit = 6, bool $verifiedOnly = false): Paginator
    {
        $qb = $this->createQueryBuilder('r');

        if ($verifiedOnly) {
            $qb->andWhere('r.isVerified = :verified')->setParameter('verified', true);
        }

        match ($sort) {
            'name' => $qb->orderBy('r.name', 'ASC'),
            'newest' => $qb->orderBy('r.createdAt', 'DESC'),
            default => $qb->orderBy('r.rating', 'DESC')->addOrderBy('r.name', 'ASC'),
        };

        $qb->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);

        return new Paginator($qb);
    }

    public function countVerified(): int
    {
        return (int) $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->where('r.isVerified = :verified')
            ->setParameter('verified', true)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
