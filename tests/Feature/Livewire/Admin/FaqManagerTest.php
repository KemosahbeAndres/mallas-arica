<?php

namespace Tests\Feature\Livewire\Admin;

use App\Livewire\Admin\SitioWeb\FaqManager;
use App\Models\Faq;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;
use Tests\Traits\ActuaComoAdmin;

class FaqManagerTest extends TestCase
{
    use ActuaComoAdmin, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actuarComoAdmin();
    }

    public function test_agregar_y_guardar_crea_una_faq(): void
    {
        Livewire::test(FaqManager::class)
            ->call('agregar')
            ->set('filas.0.pregunta', '¿Hacen envíos?')
            ->set('filas.0.respuesta', 'Solo instalamos en Arica.')
            ->set('filas.0.publicada', true)
            ->call('guardar')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('faqs', [
            'pregunta' => '¿Hacen envíos?',
            'respuesta' => 'Solo instalamos en Arica.',
            'publicada' => true,
            'orden' => 0,
        ]);
    }

    public function test_guardar_actualiza_una_faq_existente_y_su_orden(): void
    {
        $a = Faq::create(['pregunta' => 'A', 'respuesta' => 'ra', 'orden' => 0, 'publicada' => true]);
        $b = Faq::create(['pregunta' => 'B', 'respuesta' => 'rb', 'orden' => 1, 'publicada' => true]);

        Livewire::test(FaqManager::class)
            ->set('filas.0.pregunta', 'A editada')
            ->call('guardar')
            ->assertHasNoErrors();

        $this->assertSame('A editada', $a->fresh()->pregunta);
        $this->assertSame(1, $b->fresh()->orden);
    }

    public function test_eliminar_borra_la_faq_persistida(): void
    {
        $faq = Faq::create(['pregunta' => 'Temporal', 'respuesta' => 'r', 'orden' => 0, 'publicada' => true]);

        Livewire::test(FaqManager::class)
            ->call('eliminar', 0);

        $this->assertDatabaseMissing('faqs', ['id' => $faq->id]);
    }

    public function test_pregunta_vacia_no_guarda(): void
    {
        Livewire::test(FaqManager::class)
            ->call('agregar')
            ->set('filas.0.pregunta', '')
            ->set('filas.0.respuesta', 'algo')
            ->call('guardar')
            ->assertHasErrors('filas.0.pregunta');

        $this->assertDatabaseCount('faqs', 0);
    }
}
