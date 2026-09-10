<?php

namespace App\Providers;

use App\Contracts\DistanceCalculator;
use App\Services\HaversineDistanceCalculator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(
            DistanceCalculator::class,
            HaversineDistanceCalculator::class,
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
