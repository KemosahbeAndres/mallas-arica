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
            ->set('slots.hero.media_item_id', $item->id)
            ->call('guardar');

        $this->assertSame($item->id, LandingMediaSlot::where('slug', 'hero')->first()->media_item_id);
    }

    public function test_asigna_un_album_al_slot_galeria_publica(): void
    {
        $album = MediaAlbum::create(['nombre' => 'Galería pública']);

        Livewire::test(LandingMediaSlots::class)
            ->set('slots.galeria-publica.media_album_id', $album->id)
            ->call('guardar');

        $this->assertSame($album->id, LandingMediaSlot::where('slug', 'galeria-publica')->first()->media_album_id);
    }

    public function test_quitar_asignacion_vuelve_a_null(): void
    {
        $item = MediaItem::create(['archivo_path' => 'a.jpg', 'titulo' => 'Hero', 'orden' => 1]);
        LandingMediaSlot::where('slug', 'hero')->update(['media_item_id' => $item->id]);

        Livewire::test(LandingMediaSlots::class)
            ->set('slots.hero.media_item_id', null)
            ->call('guardar');

        $this->assertNull(LandingMediaSlot::where('slug', 'hero')->first()->media_item_id);
    }

    public function test_guardar_invalida_el_cache_de_slots(): void
    {
        $item = MediaItem::create(['archivo_path' => 'a.jpg', 'titulo' => 'Hero', 'orden' => 1]);

        app(LandingMediaService::class)->slots();

        Livewire::test(LandingMediaSlots::class)
            ->set('slots.hero.media_item_id', $item->id)
            ->call('guardar');

        $this->assertSame($item->id, app(LandingMediaService::class)->slot('hero')?->media_item_id);
    }

    public function test_guarda_encuadre_y_posicion(): void
    {
        Livewire::test(LandingMediaSlots::class)
            ->set('slots.hero.encuadre', 'contain')
            ->set('slots.hero.posicion_x', 20)
            ->set('slots.hero.posicion_y', 80)
            ->call('guardar');

        $slot = LandingMediaSlot::where('slug', 'hero')->first();
        $this->assertSame('contain', $slot->encuadre);
        $this->assertSame(20, $slot->posicion_x);
        $this->assertSame(80, $slot->posicion_y);
    }

    public function test_fijar_posicion_aplica_el_preset(): void
    {
        Livewire::test(LandingMediaSlots::class)
            ->call('fijarPosicion', 'hero', 'arriba')
            ->assertSet('slots.hero.posicion_x', 50)
            ->assertSet('slots.hero.posicion_y', 0);
    }

    public function test_encuadre_invalido_es_rechazado(): void
    {
        Livewire::test(LandingMediaSlots::class)
            ->set('slots.hero.encuadre', 'zoom')
            ->call('guardar')
            ->assertHasErrors('slots.hero.encuadre');
    }
}
