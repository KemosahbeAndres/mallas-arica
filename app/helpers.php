<?php

use App\Models\LandingMediaSlot;
use App\Services\LandingMediaService;
use App\Services\SiteContentService;

if (! function_exists('site_content')) {
    /**
     * Valor de contenido editable de la landing (CLAUDE.md §11, Sprint 8).
     * Cacheado en Redis por SiteContentService; el $default se usa mientras
     * la key no exista o esté vacía.
     */
    function site_content(string $key, ?string $default = null): ?string
    {
        return app(SiteContentService::class)->get($key, $default);
    }
}

if (! function_exists('landing_media_slot')) {
    /**
     * Slot de imagen/álbum asignado a una sección de la landing (hero,
     * nosotros, galeria-publica). Cacheado por LandingMediaService.
     */
    function landing_media_slot(string $slug): ?LandingMediaSlot
    {
        return app(LandingMediaService::class)->slot($slug);
    }
}
