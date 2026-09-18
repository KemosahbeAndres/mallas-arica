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
 * Hero y Nosotros (una imagen individual), Galería pública (un álbum).
 * Las 3 filas de landing_media_slots ya existen (sembradas por migración/
 * seeder) — este componente solo actualiza, nunca crea ni borra slots.
 */
class LandingMediaSlots extends Component
{
    public ?int $heroMediaItemId = null;

    public ?int $nosotrosMediaItemId = null;

    public ?int $galeriaMediaAlbumId = null;

    public function mount(): void
    {
        $this->heroMediaItemId = $this->slot('hero')?->media_item_id;
        $this->nosotrosMediaItemId = $this->slot('nosotros')?->media_item_id;
        $this->galeriaMediaAlbumId = $this->slot('galeria-publica')?->media_album_id;
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

    public function guardar(): void
    {
        $this->slot('hero')?->update(['media_item_id' => $this->heroMediaItemId]);
        $this->slot('nosotros')?->update(['media_item_id' => $this->nosotrosMediaItemId]);
        $this->slot('galeria-publica')?->update(['media_album_id' => $this->galeriaMediaAlbumId]);

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
