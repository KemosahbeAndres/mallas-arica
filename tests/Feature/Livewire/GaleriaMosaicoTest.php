<?php

namespace Tests\Feature\Livewire;

use App\Livewire\GaleriaMosaico;
use App\Models\LandingMediaSlot;
use App\Models\MediaAlbum;
use App\Models\MediaItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class GaleriaMosaicoTest extends TestCase
{
    use RefreshDatabase;

    public function test_muestra_los_items_del_album_asignado_ordenados(): void
    {
        $album = MediaAlbum::create(['nombre' => 'Galería pública']);

        MediaItem::create(['archivo_path' => 'segunda.svg', 'titulo' => 'Segunda foto', 'media_album_id' => $album->id, 'orden' => 2]);
        MediaItem::create(['archivo_path' => 'primera.svg', 'titulo' => 'Primera foto', 'media_album_id' => $album->id, 'orden' => 1]);
        MediaItem::create(['archivo_path' => 'suelta.svg', 'titulo' => 'Foto suelta']);

        LandingMediaSlot::updateOrCreate(['slug' => 'galeria-publica'], ['media_album_id' => $album->id]);

        Livewire::test(GaleriaMosaico::class)
            ->assertSee('Primera foto')
            ->assertSee('Segunda foto')
            ->assertDontSee('Foto suelta');
    }

    public function test_muestra_mensaje_cuando_no_hay_album_asignado(): void
    {
        Livewire::test(GaleriaMosaico::class)
            ->assertSee('Muy pronto vamos a publicar fotos');
    }

    public function test_muestra_mensaje_cuando_el_album_asignado_esta_vacio(): void
    {
        $album = MediaAlbum::create(['nombre' => 'Galería pública']);
        LandingMediaSlot::updateOrCreate(['slug' => 'galeria-publica'], ['media_album_id' => $album->id]);

        Livewire::test(GaleriaMosaico::class)
            ->assertSee('Muy pronto vamos a publicar fotos');
    }
}
