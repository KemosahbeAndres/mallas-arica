<?php

namespace App\Observers;

use App\Models\MediaAlbum;
use App\Services\LandingMediaService;

class MediaAlbumObserver
{
    public function __construct(private readonly LandingMediaService $cache) {}

    public function saved(MediaAlbum $album): void
    {
        $this->cache->invalidar();
    }

    public function deleted(MediaAlbum $album): void
    {
        $this->cache->invalidar();
    }
}
