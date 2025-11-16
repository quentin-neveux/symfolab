<?php

namespace App\Repository;

use App\Entity\Trajet;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class TrajetRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Trajet::class);
    }

    public function countToday(): int
{
    $today = new \DateTimeImmutable('today');
    $tomorrow = $today->modify('+1 day');

    return (int) $this->createQueryBuilder('t')
        ->select('COUNT(t.id)')
        ->where('t.dateDepart >= :today')
        ->andWhere('t.dateDepart < :tomorrow')
        ->setParameter('today', $today)
        ->setParameter('tomorrow', $tomorrow)
        ->getQuery()
        ->getSingleScalarResult();
}
    
}

