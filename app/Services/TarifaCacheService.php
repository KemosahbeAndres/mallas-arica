<?php

namespace App\Services;

use App\Models\Tarifa;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class TarifaCacheService
{
    private const VERSION_KEY = 'tarifas:version';

    // Red de seguridad, no el mecanismo primario de invalidación (ver invalidar()).
    private const TTL_SEGUNDOS = 86400;

    public function tarifasVigentes(): Collection
    {
        $version = $this->version();

        return $this->store()->remember("tarifas:v{$version}", self::TTL_SEGUNDOS, function () {
            return Tarifa::query()
                ->whereDate('vigente_desde', '<=', now()->toDateString())
                ->where(fn ($q) => $q->whereNull('vigente_hasta')->orWhereDate('vigente_hasta', '>=', now()->toDateString()))
                ->orderByDesc('vigente_desde')
                ->get()
                ->groupBy(fn (Tarifa $t) => "{$t->tipo_espacio_id}:{$t->tramo_altura_id}")
                ->map(fn (Collection $g) => $g->first());
        });
    }

    public function buscar(int $tipoEspacioId, int $tramoAlturaId): ?Tarifa
    {
        return $this->tarifasVigentes()->get("{$tipoEspacioId}:{$tramoAlturaId}");
    }

    /**
     * Versión incremental en vez de forget: un remember() en vuelo que ya
     * leyó de BD antes de este bump escribiría, si usáramos forget, bajo la
     * misma key que un lector posterior podría releer con datos viejos. Con
     * la versión incrementada, ese request tardío escribe bajo la key vieja,
     * que ya nadie vuelve a leer.
     */
    public function invalidar(): void
    {
        $this->store()->increment(self::VERSION_KEY);
    }

    private function version(): int
    {
        return (int) $this->store()->rememberForever(self::VERSION_KEY, fn () => 1);
    }

    private function store()
    {
        return Cache::store(config('cache.tarifas_store'));
    }
}
