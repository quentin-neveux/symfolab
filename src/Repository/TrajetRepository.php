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

    /**
     * Nombre de trajets créés aujourd’hui
     */
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

    /**
     * Recherche des trajets par villes avec trim des espaces
     *
     * @param string $villeDepart
     * @param string $villeArrivee
     * @return Trajet[]
     */
    public function searchByVilles(string $villeDepart, string $villeArrivee): array
    {
        $qb = $this->createQueryBuilder('t');

        $qb->andWhere('TRIM(t.villeDepart) LIKE :depart')
           ->andWhere('TRIM(t.villeArrivee) LIKE :arrivee')
           ->setParameter('depart', trim($villeDepart) . '%')
           ->setParameter('arrivee', trim($villeArrivee) . '%')
           ->orderBy('t.dateDepart', 'ASC');

        return $qb->getQuery()->getResult();
    }
}
