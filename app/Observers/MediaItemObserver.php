<?php

namespace App\Observers;

use App\Models\MediaItem;
use App\Services\LandingMediaService;

/**
 * Un MediaItem puede estar referenciado directo desde un slot (hero/nosotros)
 * o indirectamente vía su álbum (galería pública) — cualquier cambio de
 * título/orden/borrado puede alterar lo que se ve en la landing, así que
 * invalida el mismo caché de slots, no solo el de la librería en sí.
 */
class MediaItemObserver
{
    public function __construct(private readonly LandingMediaService $cache) {}

    public function saved(MediaItem $item): void
    {
        $this->cache->invalidar();
    }

    public function deleted(MediaItem $item): void
    {
        $this->cache->invalidar();
    }
}
