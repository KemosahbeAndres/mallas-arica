<?php

namespace Tests\Feature\Livewire\Admin;

use App\Livewire\Admin\Media\AlbumManager;
use App\Models\MediaAlbum;
use App\Models\MediaItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;
use Tests\Traits\ActuaComoAdmin;

class AlbumManagerTest extends TestCase
{
    use ActuaComoAdmin, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actuarComoAdmin();
    }

    public function test_crea_un_album(): void
    {
        Livewire::test(AlbumManager::class)
            ->set('nombreNuevoAlbum', 'Instalaciones 2026')
            ->call('crear')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('media_albums', ['nombre' => 'Instalaciones 2026']);
    }

    public function test_agregar_item_lo_asigna_al_album_con_orden_siguiente(): void
    {
        $album = MediaAlbum::create(['nombre' => 'Balcones']);
        MediaItem::create(['archivo_path' => 'a.jpg', 'titulo' => 'A', 'media_album_id' => $album->id, 'orden' => 1]);
        $suelto = MediaItem::create(['archivo_path' => 'b.jpg', 'titulo' => 'B', 'orden' => 1]);

        Livewire::test(AlbumManager::class)->call('agregarItem', $album->id, $suelto->id);

        $suelto->refresh();
        $this->assertSame($album->id, $suelto->media_album_id);
        $this->assertSame(2, $suelto->orden);
    }

    public function test_quitar_item_lo_desvincula_del_album_sin_borrarlo(): void
    {
        $album = MediaAlbum::create(['nombre' => 'Balcones']);
        $item = MediaItem::create(['archivo_path' => 'a.jpg', 'titulo' => 'A', 'media_album_id' => $album->id, 'orden' => 1]);

        Livewire::test(AlbumManager::class)->call('quitarItem', $item->id);

        $item->refresh();
        $this->assertNull($item->media_album_id);
        $this->assertDatabaseHas('media_items', ['id' => $item->id]);
    }

    public function test_mover_arriba_intercambia_orden_dentro_del_album(): void
    {
        $album = MediaAlbum::create(['nombre' => 'Balcones']);
        $primero = MediaItem::create(['archivo_path' => 'a.jpg', 'titulo' => 'A', 'media_album_id' => $album->id, 'orden' => 1]);
        $segundo = MediaItem::create(['archivo_path' => 'b.jpg', 'titulo' => 'B', 'media_album_id' => $album->id, 'orden' => 2]);

        Livewire::test(AlbumManager::class)->call('moverArriba', $segundo->id);

        $this->assertSame(2, $primero->fresh()->orden);
        $this->assertSame(1, $segundo->fresh()->orden);
    }

    public function test_eliminar_album_no_borra_las_imagenes(): void
    {
        $album = MediaAlbum::create(['nombre' => 'Balcones']);
        $item = MediaItem::create(['archivo_path' => 'a.jpg', 'titulo' => 'A', 'media_album_id' => $album->id, 'orden' => 1]);

        Livewire::test(AlbumManager::class)->call('eliminar', $album->id);

        $this->assertDatabaseMissing('media_albums', ['id' => $album->id]);
        $this->assertDatabaseHas('media_items', ['id' => $item->id]);
        $this->assertNull($item->fresh()->media_album_id);
    }

    public function test_subir_varias_crea_un_item_por_archivo_asignado_al_album(): void
    {
        Storage::fake('public');
        $album = MediaAlbum::create(['nombre' => 'Balcones']);

        Livewire::test(AlbumManager::class)
            ->set('fotosMasivas', [
                UploadedFile::fake()->image('a.jpg'),
                UploadedFile::fake()->image('b.jpg'),
                UploadedFile::fake()->image('c.jpg'),
            ])
            ->call('subirVarias', $album->id)
            ->assertHasNoErrors();

        $this->assertSame(3, MediaItem::where('media_album_id', $album->id)->count());

        MediaItem::where('media_album_id', $album->id)->get()->each(
            fn (MediaItem $item) => Storage::disk('public')->assertExists($item->archivo_path)
        );
    }

    public function test_subir_varias_continua_el_orden_del_album(): void
    {
        Storage::fake('public');
        $album = MediaAlbum::create(['nombre' => 'Balcones']);
        MediaItem::create(['archivo_path' => 'a.jpg', 'titulo' => 'A', 'media_album_id' => $album->id, 'orden' => 3]);

        Livewire::test(AlbumManager::class)
            ->set('fotosMasivas', [UploadedFile::fake()->image('b.jpg')])
            ->call('subirVarias', $album->id);

        $nuevo = MediaItem::where('media_album_id', $album->id)->where('archivo_path', '!=', 'a.jpg')->first();
        $this->assertSame(4, $nuevo->orden);
    }

    public function test_subir_varias_rechaza_archivo_que_no_es_imagen(): void
    {
        Storage::fake('public');
        $album = MediaAlbum::create(['nombre' => 'Balcones']);

        Livewire::test(AlbumManager::class)
            ->set('fotosMasivas', [UploadedFile::fake()->create('documento.pdf', 100)])
            ->call('subirVarias', $album->id)
            ->assertHasErrors('fotosMasivas.0');

        $this->assertDatabaseCount('media_items', 0);
    }
}
