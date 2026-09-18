<?php

namespace App\Observers;

use App\Models\LandingMediaSlot;
use App\Services\LandingMediaService;

class LandingMediaSlotObserver
{
    public function __construct(private readonly LandingMediaService $cache) {}

    public function saved(LandingMediaSlot $slot): void
    {
        $this->cache->invalidar();
    }

    public function deleted(LandingMediaSlot $slot): void
    {
        $this->cache->invalidar();
    }
}
