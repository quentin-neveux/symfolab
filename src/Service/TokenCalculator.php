<?php

namespace App\Service;

class TokenCalculator
{
    public function calculate(float $distance): int
    {
        // 1 token / 10 km
        $tokens = ceil($distance / 10);

        // plancher
        if ($tokens < 5) $tokens = 5;

        // plafond
        if ($tokens > 40) $tokens = 40;

        return $tokens;
    }
}
