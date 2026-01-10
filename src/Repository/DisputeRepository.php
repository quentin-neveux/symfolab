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

    public function findAllOrdered(int $limit = 200): array
    {
        // OPEN puis IN_REVIEW puis le reste
        return $this->createQueryBuilder('d')
            ->addSelect("
                CASE
                    WHEN d.status = :open THEN 1
                    WHEN d.status = :inreview THEN 2
                    ELSE 3
                END AS HIDDEN statusOrder
            ")
            ->setParameter('open', Dispute::STATUS_OPEN)
            ->setParameter('inreview', Dispute::STATUS_IN_REVIEW)
            ->orderBy('statusOrder', 'ASC')
            ->addOrderBy('d.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
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

    public function findActiveForReporterAndTrajet(int $reporterId, int $trajetId): ?Dispute
    {
        return $this->createQueryBuilder('d')
            ->andWhere('IDENTITY(d.reporter) = :r')
            ->andWhere('IDENTITY(d.trajet) = :t')
            ->andWhere('d.status IN (:s)')
            ->setParameter('r', $reporterId)
            ->setParameter('t', $trajetId)
            ->setParameter('s', [Dispute::STATUS_OPEN, Dispute::STATUS_IN_REVIEW])
            ->orderBy('d.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function hasRecentForReporterAndTrajet(int $reporterId, int $trajetId, \DateTimeImmutable $since): bool
    {
        $count = $this->createQueryBuilder('d')
            ->select('COUNT(d.id)')
            ->andWhere('IDENTITY(d.reporter) = :r')
            ->andWhere('IDENTITY(d.trajet) = :t')
            ->andWhere('d.createdAt >= :since')
            ->setParameter('r', $reporterId)
            ->setParameter('t', $trajetId)
            ->setParameter('since', $since)
            ->getQuery()
            ->getSingleScalarResult();

        return (int) $count > 0;
    }

    // =========================================================
    // Payout helpers (blocage/libération gains conducteur)
    // =========================================================

    public function countActiveForTrajet(int $trajetId): int
    {
        return (int) $this->createQueryBuilder('d')
            ->select('COUNT(d.id)')
            ->andWhere('IDENTITY(d.trajet) = :t')
            ->andWhere('d.status IN (:s)')
            ->setParameter('t', $trajetId)
            ->setParameter('s', [Dispute::STATUS_OPEN, Dispute::STATUS_IN_REVIEW])
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countResolvedForTrajet(int $trajetId): int
    {
        // Interprétation actuelle : RESOLVED = signalement fondé => payout bloqué
        return (int) $this->createQueryBuilder('d')
            ->select('COUNT(d.id)')
            ->andWhere('IDENTITY(d.trajet) = :t')
            ->andWhere('d.status = :s')
            ->setParameter('t', $trajetId)
            ->setParameter('s', Dispute::STATUS_RESOLVED)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
