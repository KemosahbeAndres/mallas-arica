<?php

namespace Tests\Feature\Livewire\Admin;

use App\Livewire\Admin\Media\LandingMediaSlots;
use App\Models\LandingMediaSlot;
use App\Models\MediaAlbum;
use App\Models\MediaItem;
use App\Services\LandingMediaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;
use Tests\Traits\ActuaComoAdmin;

class LandingMediaSlotsTest extends TestCase
{
    use ActuaComoAdmin, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actuarComoAdmin();

        foreach (['hero', 'nosotros', 'galeria-publica'] as $slug) {
            LandingMediaSlot::firstOrCreate(['slug' => $slug]);
        }
    }

    public function test_asigna_una_imagen_al_slot_hero(): void
    {
        $item = MediaItem::create(['archivo_path' => 'a.jpg', 'titulo' => 'Hero', 'orden' => 1]);

        Livewire::test(LandingMediaSlots::class)
            ->set('heroMediaItemId', $item->id)
            ->call('guardar');

        $this->assertSame($item->id, LandingMediaSlot::where('slug', 'hero')->first()->media_item_id);
    }

    public function test_asigna_un_album_al_slot_galeria_publica(): void
    {
        $album = MediaAlbum::create(['nombre' => 'Galería pública']);

        Livewire::test(LandingMediaSlots::class)
            ->set('galeriaMediaAlbumId', $album->id)
            ->call('guardar');

        $this->assertSame($album->id, LandingMediaSlot::where('slug', 'galeria-publica')->first()->media_album_id);
    }

    public function test_quitar_asignacion_vuelve_a_null(): void
    {
        $item = MediaItem::create(['archivo_path' => 'a.jpg', 'titulo' => 'Hero', 'orden' => 1]);
        LandingMediaSlot::where('slug', 'hero')->update(['media_item_id' => $item->id]);

        Livewire::test(LandingMediaSlots::class)
            ->set('heroMediaItemId', null)
            ->call('guardar');

        $this->assertNull(LandingMediaSlot::where('slug', 'hero')->first()->media_item_id);
    }

    public function test_guardar_invalida_el_cache_de_slots(): void
    {
        $item = MediaItem::create(['archivo_path' => 'a.jpg', 'titulo' => 'Hero', 'orden' => 1]);

        app(LandingMediaService::class)->slots();

        Livewire::test(LandingMediaSlots::class)
            ->set('heroMediaItemId', $item->id)
            ->call('guardar');

        $this->assertSame($item->id, app(LandingMediaService::class)->slot('hero')?->media_item_id);
    }
}
