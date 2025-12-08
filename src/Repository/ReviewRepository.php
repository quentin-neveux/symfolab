<?php

namespace App\Repository;

use App\Entity\Review;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Review>
 */
class ReviewRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Review::class);
    }
    public function getAverageRatingForUser(int $userId): ?float
    {
    $qb = $this->createQueryBuilder('r')
        ->select('AVG(r.rating) as avgRating')
        ->where('r.target = :userId')
        ->setParameter('userId', $userId)
        ->getQuery()
        ->getSingleScalarResult();

    return $qb ? round((float) $qb, 1) : null;
    }

    public function getReviewsForUser(int $userId): array
    {
    return $this->createQueryBuilder('r')
        ->where('r.target = :userId')
        ->setParameter('userId', $userId)
        ->orderBy('r.createdAt', 'DESC')
        ->getQuery()
        ->getResult();
    }



    //    /**
    //     * @return Review[] Returns an array of Review objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('r')
    //            ->andWhere('r.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('r.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Review
    //    {
    //        return $this->createQueryBuilder('r')
    //            ->andWhere('r.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
