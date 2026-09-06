<?php

namespace App\Observers;

use App\Models\Tarifa;
use App\Services\TarifaCacheService;

class TarifaObserver
{
    public function __construct(private readonly TarifaCacheService $cache) {}

    public function saved(Tarifa $tarifa): void
    {
        $this->cache->invalidar();
    }

    public function deleted(Tarifa $tarifa): void
    {
        $this->cache->invalidar();
    }
}
