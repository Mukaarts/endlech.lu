<?php

namespace App\Repository;

use App\Entity\Restaurant;
use App\Entity\RestaurantImage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<RestaurantImage>
 */
class RestaurantImageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RestaurantImage::class);
    }

    public function getNextSortOrder(Restaurant $restaurant): int
    {
        $result = $this->createQueryBuilder('i')
            ->select('MAX(i.sortOrder)')
            ->where('i.restaurant = :restaurant')
            ->setParameter('restaurant', $restaurant)
            ->getQuery()
            ->getSingleScalarResult();

        return ($result === null ? 0 : (int) $result) + 1;
    }
}
