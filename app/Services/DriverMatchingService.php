<?php

namespace App\Services;

use App\Contracts\DistanceCalculator;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class DriverMatchingService
{
    public function __construct(
        private DistanceCalculator $distanceCalculator,
    ) {
    }

    /**
     * @return Collection<int, Order>
     */
    public function availableOrdersFor(User $driver): Collection
    {
        if (
            ! $driver->is_available_for_delivery
            || $driver->latitude === null
            || $driver->longitude === null
        ) {
            return new Collection();
        }

        $radiusInKilometers = (float) config(
            'delivery.driver_matching_radius_in_kilometers',
        );

        $orders = Order::query()
            ->where('status', OrderStatus::Ready)
            ->doesntHave('delivery')
            ->with(['store', 'items'])
            ->whereHas('store', function ($query): void {
                $query->whereNotNull('latitude')
                    ->whereNotNull('longitude')
                    ->active();
            })
            ->orderBy('id')
            ->get();

        return $orders
            ->map(function (Order $order) use ($driver): Order {
                $distanceInKilometers = $this->distanceCalculator->kilometers(
                    (float) $driver->latitude,
                    (float) $driver->longitude,
                    (float) $order->store->latitude,
                    (float) $order->store->longitude,
                );

                $order->setAttribute(
                    'pickup_distance_kilometers',
                    round($distanceInKilometers, 2),
                );

                return $order;
            })
            ->filter(
                fn (Order $order): bool => $order->pickup_distance_kilometers <= $radiusInKilometers,
            )
            ->sortBy('pickup_distance_kilometers')
            ->values();
    }
}