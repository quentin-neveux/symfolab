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
     * Recherche des trajets par villes (trim + startsWith)
     *
     * @return Trajet[]
     */
    public function searchByVilles(string $villeDepart, string $villeArrivee): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('TRIM(t.villeDepart) LIKE :depart')
            ->andWhere('TRIM(t.villeArrivee) LIKE :arrivee')
            ->setParameter('depart', trim($villeDepart) . '%')
            ->setParameter('arrivee', trim($villeArrivee) . '%')
            ->orderBy('t.dateDepart', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Suggestions de villes (autocomplete) via villeDepart + villeArrivee
     *
     * @return string[]
     */
    public function findCitySuggestions(string $q, int $limit = 10): array
    {
        $q = trim($q);
        if ($q === '') {
            return [];
        }

        $qLower = mb_strtolower($q);
        $qLike = $qLower . '%';

        // On récupère un peu plus, puis on déduplique côté PHP.
        $rows = $this->createQueryBuilder('t')
            ->select('DISTINCT TRIM(LOWER(t.villeDepart)) AS vd, TRIM(LOWER(t.villeArrivee)) AS va')
            ->andWhere('LOWER(TRIM(t.villeDepart)) LIKE :q OR LOWER(TRIM(t.villeArrivee)) LIKE :q')
            ->setParameter('q', $qLike)
            ->setMaxResults($limit * 3)
            ->getQuery()
            ->getArrayResult();

        $set = [];
        foreach ($rows as $r) {
            $vd = $r['vd'] ?? null;
            $va = $r['va'] ?? null;

            if ($vd && str_starts_with($vd, $qLower)) {
                $set[$vd] = true;
            }
            if ($va && str_starts_with($va, $qLower)) {
                $set[$va] = true;
            }
        }

        $cities = array_slice(array_keys($set), 0, $limit);

        // Casse "propre" (Paris, Saint-Étienne, etc.)
        return array_map(
            fn (string $c) => mb_convert_case($c, MB_CASE_TITLE, 'UTF-8'),
            $cities
        );
    }
}
