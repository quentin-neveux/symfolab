<?php

namespace App\Repository;

use App\Entity\Dispute;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class DisputeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Dispute::class);
    }

    public function findOpenFirst(int $limit = 50): array
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.status IN (:s)')
            ->setParameter('s', [Dispute::STATUS_OPEN, Dispute::STATUS_IN_REVIEW])
            ->orderBy('d.createdAt', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
