<?php

namespace App\Livewire\Admin\Media;

use App\Models\LandingMediaSlot;
use App\Models\MediaAlbum;
use App\Models\MediaItem;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Asigna qué imagen o álbum de la librería usa cada sección de la landing:
 * Hero y Nosotros (una imagen individual), Galería pública (un álbum). Cada
 * slot también guarda su propio encuadre (object-fit) y posición
 * (object-position, 0-100%) — la misma imagen puede recortarse distinto
 * según la sección donde se usa. Las 3 filas de landing_media_slots ya
 * existen (sembradas por migración/seeder) — este componente solo
 * actualiza, nunca crea ni borra slots.
 */
class LandingMediaSlots extends Component
{
    public const SLUGS = ['hero', 'nosotros', 'galeria-publica'];

    /** @var array<string, array{media_item_id: ?int, media_album_id: ?int, encuadre: string, posicion_x: int, posicion_y: int}> */
    public array $slots = [];

    public function mount(): void
    {
        foreach (self::SLUGS as $slug) {
            $slot = $this->slot($slug);

            $this->slots[$slug] = [
                'media_item_id' => $slot?->media_item_id,
                'media_album_id' => $slot?->media_album_id,
                'encuadre' => $slot?->encuadre ?? 'cover',
                'posicion_x' => $slot?->posicion_x ?? 50,
                'posicion_y' => $slot?->posicion_y ?? 50,
            ];
        }
    }

    #[Computed]
    public function items()
    {
        return MediaItem::query()->orderByDesc('id')->get();
    }

    #[Computed]
    public function albumes()
    {
        return MediaAlbum::query()->orderBy('nombre')->get();
    }

    public function fijarPosicion(string $slug, string $preset): void
    {
        $presets = [
            'centro' => [50, 50],
            'arriba' => [50, 0],
            'abajo' => [50, 100],
            'izquierda' => [0, 50],
            'derecha' => [100, 50],
        ];

        if (! isset($presets[$preset], $this->slots[$slug])) {
            return;
        }

        [$x, $y] = $presets[$preset];
        $this->slots[$slug]['posicion_x'] = $x;
        $this->slots[$slug]['posicion_y'] = $y;
    }

    public function guardar(): void
    {
        $this->validate([
            'slots.*.encuadre' => ['required', 'in:'.implode(',', LandingMediaSlot::ENCUADRES)],
            'slots.*.posicion_x' => ['required', 'integer', 'min:0', 'max:100'],
            'slots.*.posicion_y' => ['required', 'integer', 'min:0', 'max:100'],
        ]);

        foreach (['hero', 'nosotros'] as $slug) {
            $this->slot($slug)?->update([
                'media_item_id' => $this->slots[$slug]['media_item_id'],
                'encuadre' => $this->slots[$slug]['encuadre'],
                'posicion_x' => $this->slots[$slug]['posicion_x'],
                'posicion_y' => $this->slots[$slug]['posicion_y'],
            ]);
        }

        $this->slot('galeria-publica')?->update([
            'media_album_id' => $this->slots['galeria-publica']['media_album_id'],
            'encuadre' => $this->slots['galeria-publica']['encuadre'],
            'posicion_x' => $this->slots['galeria-publica']['posicion_x'],
            'posicion_y' => $this->slots['galeria-publica']['posicion_y'],
        ]);

        $this->dispatch('slots-actualizados');
    }

    #[On('media-actualizada')]
    public function refrescar(): void
    {
        unset($this->items, $this->albumes);
    }

    private function slot(string $slug): ?LandingMediaSlot
    {
        return LandingMediaSlot::where('slug', $slug)->first();
    }

    public function render()
    {
        return view('livewire.admin.media.landing-media-slots');
    }
}
