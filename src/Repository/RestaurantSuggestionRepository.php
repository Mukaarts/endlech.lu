<?php

namespace App\Repository;

use App\Entity\RestaurantSuggestion;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<RestaurantSuggestion>
 */
class RestaurantSuggestionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RestaurantSuggestion::class);
    }

    /** @return RestaurantSuggestion[] */
    public function findByStatus(string $status): array
    {
        return $this->findBy(['status' => $status], ['createdAt' => 'DESC']);
    }

    public function countPending(): int
    {
        return $this->count(['status' => RestaurantSuggestion::STATUS_PENDING]);
    }
}
