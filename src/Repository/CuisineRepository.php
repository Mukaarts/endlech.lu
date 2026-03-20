<?php

namespace App\Repository;

use App\Entity\Cuisine;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\String\Slugger\AsciiSlugger;

/**
 * @extends ServiceEntityRepository<Cuisine>
 */
class CuisineRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Cuisine::class);
    }

    /**
     * @return Cuisine[]
     */
    public function findAllSorted(): array
    {
        return $this->createQueryBuilder('c')
            ->orderBy('c.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Cuisine[]
     */
    public function search(string $query, int $limit = 20): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.name LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->orderBy('c.name', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findOrCreateByName(string $name): Cuisine
    {
        $name = trim($name);
        $slugger = new AsciiSlugger();
        $slug = strtolower((string) $slugger->slug($name));

        $existing = $this->findOneBy(['slug' => $slug]);
        if ($existing !== null) {
            return $existing;
        }

        $cuisine = new Cuisine();
        $cuisine->setName($name);
        $cuisine->setSlug($slug);

        $this->getEntityManager()->persist($cuisine);

        return $cuisine;
    }
}
