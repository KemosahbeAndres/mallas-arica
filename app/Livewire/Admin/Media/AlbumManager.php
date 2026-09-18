<?php

namespace App\Livewire\Admin\Media;

use App\Models\MediaAlbum;
use App\Models\MediaItem;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class AlbumManager extends Component
{
    public string $nombreNuevoAlbum = '';

    public ?int $albumAbiertoId = null;

    #[Computed]
    public function albumes()
    {
        return MediaAlbum::query()->with('items')->orderBy('nombre')->get();
    }

    #[Computed]
    public function itemsSinAlbum()
    {
        return MediaItem::query()->whereNull('media_album_id')->orderByDesc('id')->get();
    }

    public function crear(): void
    {
        $this->validate([
            'nombreNuevoAlbum' => ['required', 'string', 'max:255'],
        ]);

        MediaAlbum::create(['nombre' => $this->nombreNuevoAlbum]);

        $this->reset('nombreNuevoAlbum');
        $this->dispatch('media-actualizada');
    }

    public function eliminar(MediaAlbum $album): void
    {
        // No borra las imágenes: quedan sueltas en la librería (media_album_id → null).
        $album->delete();

        if ($this->albumAbiertoId === $album->id) {
            $this->albumAbiertoId = null;
        }

        $this->dispatch('media-actualizada');
    }

    public function abrir(int $albumId): void
    {
        $this->albumAbiertoId = $this->albumAbiertoId === $albumId ? null : $albumId;
    }

    public function agregarItem(MediaAlbum $album, MediaItem $item): void
    {
        $item->update([
            'media_album_id' => $album->id,
            'orden' => ($album->items()->max('orden') ?? 0) + 1,
        ]);

        $this->dispatch('media-actualizada');
    }

    public function quitarItem(MediaItem $item): void
    {
        $item->update(['media_album_id' => null]);

        $this->dispatch('media-actualizada');
    }

    public function moverArriba(MediaItem $item): void
    {
        $anterior = MediaItem::where('media_album_id', $item->media_album_id)
            ->where('orden', '<', $item->orden)
            ->orderByDesc('orden')
            ->first();

        if (! $anterior) {
            return;
        }

        DB::transaction(function () use ($item, $anterior) {
            [$ordenItem, $ordenAnterior] = [$item->orden, $anterior->orden];
            $item->update(['orden' => $ordenAnterior]);
            $anterior->update(['orden' => $ordenItem]);
        });

        $this->dispatch('media-actualizada');
    }

    public function moverAbajo(MediaItem $item): void
    {
        $siguiente = MediaItem::where('media_album_id', $item->media_album_id)
            ->where('orden', '>', $item->orden)
            ->orderBy('orden')
            ->first();

        if (! $siguiente) {
            return;
        }

        DB::transaction(function () use ($item, $siguiente) {
            [$ordenItem, $ordenSiguiente] = [$item->orden, $siguiente->orden];
            $item->update(['orden' => $ordenSiguiente]);
            $siguiente->update(['orden' => $ordenItem]);
        });

        $this->dispatch('media-actualizada');
    }

    #[On('media-actualizada')]
    public function refrescar(): void
    {
        unset($this->albumes, $this->itemsSinAlbum);
    }

    public function render()
    {
        return view('livewire.admin.media.album-manager');
    }
}
