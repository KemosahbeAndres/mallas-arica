<?php

namespace Tests\Unit;

use App\Models\LandingMediaSlot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LandingMediaSlotTest extends TestCase
{
    use RefreshDatabase;

    public function test_estilo_imagen_usa_encuadre_y_posicion_del_slot(): void
    {
        $slot = LandingMediaSlot::create([
            'slug' => 'slot-de-prueba',
            'encuadre' => 'contain',
            'posicion_x' => 20,
            'posicion_y' => 80,
        ]);

        $this->assertSame('object-fit: contain; object-position: 20% 80%;', $slot->estiloImagen);
    }

    public function test_estilo_imagen_por_defecto_es_cover_centrado(): void
    {
        $slot = LandingMediaSlot::create(['slug' => 'slot-de-prueba']);

        $this->assertSame('object-fit: cover; object-position: 50% 50%;', $slot->estiloImagen);
    }

    public function test_estilo_imagen_cae_a_cover_si_el_encuadre_guardado_es_invalido(): void
    {
        $slot = LandingMediaSlot::create(['slug' => 'slot-de-prueba']);
        $slot->encuadre = 'zoom';

        $this->assertSame('object-fit: cover; object-position: 50% 50%;', $slot->estiloImagen);
    }
}
