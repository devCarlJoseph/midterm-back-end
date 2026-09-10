<?php

namespace App\Services;

use App\Contracts\DistanceCalculator;
use App\Models\Address;
use App\Models\DeliveryOption;
use App\Models\Store;
use Illuminate\Validation\ValidationException;

class DeliveryFeeService
{
    public function __construct(
        private DistanceCalculator $distanceCalculator,
    ) {
    }

    public function calculateInCentavos(
        Store $store,
        Address $address,
        DeliveryOption $deliveryOption,
    ): int {
        if (
            $store->latitude === null
            || $store->longitude === null
            || $address->latitude === null
            || $address->longitude === null
        ) {
            throw ValidationException::withMessages([
                'address_id' => ['The store and delivery address must have location coordinates.'],
            ]);
        }

        $distanceInKilometers = $this->distanceCalculator->kilometers(
            (float) $store->latitude,
            (float) $store->longitude,
            (float) $address->latitude,
            (float) $address->longitude,
        );

        if ($distanceInKilometers > config('delivery.maximum_distance_in_kilometers')) {
            throw ValidationException::withMessages([
                'address_id' => ['This address is outside the store delivery area.'],
            ]);
        }

        $distanceFeeInCentavos = (int) config('delivery.base_fee_in_centavos')
            + ((int) ceil($distanceInKilometers)
                * (int) config('delivery.per_kilometer_in_centavos'));

        $optionFeeInCentavos = (int) round(
            ((float) $deliveryOption->additional_fee) * 100,
        );

        return $distanceFeeInCentavos + $optionFeeInCentavos;
    }
}