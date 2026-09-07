<?php

namespace App\Observers;

use App\Models\SiteContent;
use App\Services\SiteContentService;

class SiteContentObserver
{
    public function __construct(private readonly SiteContentService $cache) {}

    public function saved(SiteContent $content): void
    {
        $this->cache->invalidarContenido();
    }

    public function deleted(SiteContent $content): void
    {
        $this->cache->invalidarContenido();
    }
}
