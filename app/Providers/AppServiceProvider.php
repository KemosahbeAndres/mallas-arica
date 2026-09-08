<?php

namespace App\Providers;

use App\Models\Faq;
use App\Models\SiteContent;
use App\Observers\FaqObserver;
use App\Observers\SiteContentObserver;
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
        SiteContent::observe(SiteContentObserver::class);
        Faq::observe(FaqObserver::class);
    }
}
