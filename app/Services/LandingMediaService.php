<?php

namespace App\Services;

use App\Models\LandingMediaSlot;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Asignación de imagen/álbum a cada sección de la landing (Hero, Nosotros,
 * Galería pública). Mismo patrón que SiteContentService: la colección
 * completa de slots se cachea bajo una key versionada y la invalidación es
 * un increment de la versión, no un forget.
 */
class LandingMediaService
{
    private const VERSION_KEY = 'landing_media:version';

    private const TTL_SEGUNDOS = 86400;

    /**
     * Todos los slots indexados por slug, con sus relaciones cargadas.
     *
     * @return Collection<string, LandingMediaSlot>
     */
    public function slots(): Collection
    {
        $version = $this->version();

        return $this->store()->remember("landing_media:v{$version}", self::TTL_SEGUNDOS, function () {
            return LandingMediaSlot::query()
                ->with(['mediaItem', 'mediaAlbum.items'])
                ->get()
                ->keyBy('slug');
        });
    }

    public function slot(string $slug): ?LandingMediaSlot
    {
        return $this->slots()->get($slug);
    }

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
        return Cache::store(config('cache.site_content_store'));
    }
}
