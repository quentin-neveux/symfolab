<?php

namespace App\Repository;

use App\Entity\City;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class CityRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, City::class);
    }

    public function suggest(string $q, int $limit = 30): array
    {
        $q = trim($q);
        if ($q === '' || mb_strlen($q) < 2) return [];

        // priorité aux "commence par", puis tri population
        return $this->createQueryBuilder('c')
            ->select('c.name AS name', 'c.countryCode AS country', 'c.population AS pop')
            ->andWhere('LOWER(c.name) LIKE :q')
            ->setParameter('q', mb_strtolower($q).'%')
            ->orderBy('c.population', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getArrayResult();
    }
}

