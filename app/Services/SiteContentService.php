<?php

namespace App\Services;

use App\Models\Faq;
use App\Models\SiteContent;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Contenido editable de la landing (CLAUDE.md §11, Sprint 8).
 *
 * Mismo patrón que TarifaCacheService: la colección completa se cachea bajo
 * una key versionada y la invalidación es un increment de la versión, no un
 * forget (evita la carrera del remember() en vuelo). Los Blade leen de aquí
 * vía el helper global site_content(); nunca tocan la tabla por render.
 */
class SiteContentService
{
    private const VERSION_KEY = 'site_content:version';

    private const FAQS_VERSION_KEY = 'faqs:version';

    private const TTL_SEGUNDOS = 86400;

    /**
     * Todos los valores de contenido indexados por key.
     *
     * @return Collection<string, string|null>
     */
    public function todos(): Collection
    {
        $version = $this->version(self::VERSION_KEY);

        return $this->store()->remember("site_content:v{$version}", self::TTL_SEGUNDOS, function () {
            return SiteContent::query()->pluck('value', 'key');
        });
    }

    public function get(string $key, ?string $default = null): ?string
    {
        $valor = $this->todos()->get($key);

        return $valor === null || $valor === '' ? $default : $valor;
    }

    /**
     * FAQ publicadas, ordenadas. Alimenta el acordeón y el JSON-LD FAQPage.
     *
     * @return Collection<int, Faq>
     */
    public function faqs(): Collection
    {
        $version = $this->version(self::FAQS_VERSION_KEY);

        return $this->store()->remember("faqs:v{$version}", self::TTL_SEGUNDOS, function () {
            return Faq::query()->publicadas()->get();
        });
    }

    public function invalidarContenido(): void
    {
        $this->store()->increment(self::VERSION_KEY);
    }

    public function invalidarFaqs(): void
    {
        $this->store()->increment(self::FAQS_VERSION_KEY);
    }

    private function version(string $key): int
    {
        return (int) $this->store()->rememberForever($key, fn () => 1);
    }

    private function store()
    {
        return Cache::store(config('cache.site_content_store'));
    }
}
