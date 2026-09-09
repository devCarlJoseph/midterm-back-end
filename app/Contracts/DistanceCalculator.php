<?php

namespace App\Contracts;

interface DistanceCalculator
{
    public function kilometers(
        float $fromLatitude,
        float $fromLongitude,
        float $toLatitude,
        float $toLongitude,
    ): float;
}