<?php

namespace App\Services;

use App\Contracts\DistanceCalculator;

class HaversineDistanceCalculator implements DistanceCalculator
{
    public function kilometers(
        float $fromLatitude,
        float $fromLongitude,
        float $toLatitude,
        float $toLongitude,
    ): float {
        $earthRadiusInKilometers = 6371.0;

        $latitudeDelta = deg2rad($toLatitude - $fromLatitude);
        $longitudeDelta = deg2rad($toLongitude - $fromLongitude);

        $a = sin($latitudeDelta / 2) ** 2
            + cos(deg2rad($fromLatitude))
            * cos(deg2rad($toLatitude))
            * sin($longitudeDelta / 2) ** 2;

        return $earthRadiusInKilometers * (2 * atan2(sqrt($a), sqrt(1 - $a)));
    }
}
