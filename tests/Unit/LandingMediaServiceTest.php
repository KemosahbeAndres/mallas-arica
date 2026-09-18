<?php

namespace Tests\Unit;

use App\Models\LandingMediaSlot;
use App\Models\MediaAlbum;
use App\Models\MediaItem;
use App\Services\LandingMediaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LandingMediaServiceTest extends TestCase
{
    use RefreshDatabase;

    private LandingMediaService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(LandingMediaService::class);
    }

    public function test_slot_devuelve_null_si_no_existe(): void
    {
        $this->assertNull($this->service->slot('slug-inexistente'));
    }

    public function test_slot_devuelve_la_imagen_asignada(): void
    {
        $item = MediaItem::create(['archivo_path' => 'a.jpg', 'titulo' => 'Foto', 'orden' => 1]);
        LandingMediaSlot::updateOrCreate(['slug' => 'hero'], ['media_item_id' => $item->id]);

        $this->assertSame($item->id, $this->service->slot('hero')?->mediaItem?->id);
    }

    public function test_cambiar_la_asignacion_invalida_el_cache(): void
    {
        $primero = MediaItem::create(['archivo_path' => 'a.jpg', 'titulo' => 'A', 'orden' => 1]);
        $segundo = MediaItem::create(['archivo_path' => 'b.jpg', 'titulo' => 'B', 'orden' => 2]);
        $slot = LandingMediaSlot::updateOrCreate(['slug' => 'hero'], ['media_item_id' => $primero->id]);

        $this->assertSame($primero->id, $this->service->slot('hero')?->media_item_id);

        $slot->update(['media_item_id' => $segundo->id]);

        $this->assertSame($segundo->id, $this->service->slot('hero')?->media_item_id);
    }

    public function test_slot_de_album_trae_sus_items_ordenados(): void
    {
        $album = MediaAlbum::create(['nombre' => 'Galería pública']);
        MediaItem::create(['archivo_path' => 'b.jpg', 'titulo' => 'B', 'media_album_id' => $album->id, 'orden' => 2]);
        MediaItem::create(['archivo_path' => 'a.jpg', 'titulo' => 'A', 'media_album_id' => $album->id, 'orden' => 1]);
        LandingMediaSlot::updateOrCreate(['slug' => 'galeria-publica'], ['media_album_id' => $album->id]);

        $items = $this->service->slot('galeria-publica')?->mediaAlbum?->items;

        $this->assertSame(['A', 'B'], $items->pluck('titulo')->all());
    }
}
