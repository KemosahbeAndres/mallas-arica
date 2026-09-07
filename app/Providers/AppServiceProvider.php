<?php

namespace App\Providers;

use App\Models\Tarifa;
use App\Observers\TarifaObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Tarifa::observe(TarifaObserver::class);
    }
}
