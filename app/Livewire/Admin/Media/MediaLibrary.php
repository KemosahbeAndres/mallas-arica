<?php

namespace App\Livewire\Admin\Media;

use App\Models\MediaAlbum;
use App\Models\MediaItem;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Librería central de imágenes subidas (biblioteca de medios). Cualquier
 * imagen sube aquí primero; luego se asigna a un álbum (AlbumManager) o
 * directo a un slot de la landing (LandingMediaSlots) — no se resube la
 * misma imagen para cada uso.
 */
class MediaLibrary extends Component
{
    use WithFileUploads;

    public $foto;

    public string $titulo = '';

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

    public function subir(): void
    {
        $this->validate([
            'foto' => ['required', 'image', 'max:4096'],
            'titulo' => ['nullable', 'string', 'max:255'],
        ]);

        MediaItem::create([
            'archivo_path' => $this->foto->store('media', 'public'),
            'titulo' => $this->titulo ?: null,
            'orden' => (MediaItem::max('orden') ?? 0) + 1,
        ]);

        $this->reset('foto', 'titulo');
        $this->dispatch('media-actualizada');
    }

    public function eliminar(MediaItem $item): void
    {
        Storage::disk('public')->delete($item->archivo_path);
        $item->delete();

        $this->dispatch('media-actualizada');
    }

    #[On('media-actualizada')]
    public function refrescar(): void
    {
        unset($this->items, $this->albumes);
    }

    public function render()
    {
        return view('livewire.admin.media.media-library');
    }
}
