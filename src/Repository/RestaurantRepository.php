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

    public function findPaginated(string $sort = 'rating', int $page = 1, int $limit = 6, array $filters = []): Paginator
    {
        $qb = $this->createQueryBuilder('r');

        if (!empty($filters['verified'])) {
            $qb->andWhere('r.isVerified = true');
        }
        if (!empty($filters['wheelchair'])) {
            $qb->andWhere('r.isWheelchairAccessible = true');
        }
        if (!empty($filters['toilet'])) {
            $qb->andWhere('r.hasAccessibleToilet = true');
        }
        if (!empty($filters['dogs'])) {
            $qb->andWhere('r.allowsAssistanceDogs = true');
        }
        if (!empty($filters['lighting'])) {
            $qb->andWhere('r.hasBrightLighting = true');
        }
        if (!empty($filters['changing_table'])) {
            $qb->andWhere('r.hasChangingTable = true');
        }
        if (!empty($filters['open'])) {
            $qb->andWhere('r.isOpen = true');
        }
        if (!empty($filters['vegan'])) {
            $qb->andWhere('r.isVegan = true');
        }
        if (!empty($filters['vegetarian'])) {
            $qb->andWhere('r.isVegetarian = true');
        }
        if (!empty($filters['halal'])) {
            $qb->andWhere('r.isHalal = true');
        }
        if (!empty($filters['city'])) {
            $qb->andWhere('r.city LIKE :city')->setParameter('city', '%'.$filters['city'].'%');
        }
        if (!empty($filters['cuisine'])) {
            $qb->andWhere('r.cuisine LIKE :cuisine')->setParameter('cuisine', '%'.$filters['cuisine'].'%');
        }
        if (!empty($filters['lang'])) {
            foreach ($filters['lang'] as $i => $langValue) {
                $param = 'lang'.$i;
                $qb->andWhere("JSON_CONTAINS(r.spokenLanguages, :$param) = 1")
                    ->setParameter($param, json_encode($langValue));
            }
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
