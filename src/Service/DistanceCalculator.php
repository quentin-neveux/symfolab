<?php

namespace App\Service;

class DistanceCalculator
{
    public function estimateDistance(string $villeDepart, string $villeArrivee): float
    {
        $vd = trim(mb_strtolower($villeDepart));
        $va = trim(mb_strtolower($villeArrivee));

        // Coordonnées simplifiées (à enrichir si besoin)
        $coords = [
            'annecy' => [45.899247, 6.129384],
            'genève' => [46.204391, 6.143158],
            'lyon' => [45.764043, 4.835659],
            'grenoble' => [45.188529, 5.724524],
            'paris' => [48.856614, 2.352221],
            'marseille' => [43.296482, 5.369780],
        ];

        if (!isset($coords[$vd]) || !isset($coords[$va])) {
            return 50; // distance par défaut si inconnue
        }

        [$lat1, $lon1] = $coords[$vd];
        [$lat2, $lon2] = $coords[$va];

        return $this->haversine($lat1, $lon1, $lat2, $lon2);
    }

    private function haversine(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earth = 6371; // rayon Terre en km

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat/2)**2 +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon/2)**2;

        $c = 2 * atan2(sqrt($a), sqrt(1-$a));

        return $earth * $c;
    }
}
