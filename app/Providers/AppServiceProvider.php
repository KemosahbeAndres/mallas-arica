<?php

namespace App\Providers;

use App\Models\Faq;
use App\Models\LandingMediaSlot;
use App\Models\MediaAlbum;
use App\Models\MediaItem;
use App\Models\SiteContent;
use App\Observers\FaqObserver;
use App\Observers\LandingMediaSlotObserver;
use App\Observers\MediaAlbumObserver;
use App\Observers\MediaItemObserver;
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
        LandingMediaSlot::observe(LandingMediaSlotObserver::class);
        MediaItem::observe(MediaItemObserver::class);
        MediaAlbum::observe(MediaAlbumObserver::class);
    }
}
