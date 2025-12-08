<?php

namespace App\Repository;

use App\Entity\AimlabScore;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class AimlabScoreRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AimlabScore::class);
    }

    public function findTop3(): array
    {
        return $this->createQueryBuilder('s')
            ->join('s.user', 'u')
            ->addSelect('u')
            ->orderBy('s.avgMs', 'ASC')
            ->setMaxResults(3)
            ->getQuery()
            ->getResult();
    }
}
