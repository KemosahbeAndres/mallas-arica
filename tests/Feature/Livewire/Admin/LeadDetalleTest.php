<?php

namespace Tests\Feature\Livewire\Admin;

use App\Livewire\Admin\Leads\LeadDetalle;
use App\Models\Cotizacion;
use App\Models\TipoEspacio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;
use Tests\Traits\ActuaComoAdmin;

class LeadDetalleTest extends TestCase
{
    use ActuaComoAdmin, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actuarComoAdmin();
    }

    public function test_muestra_los_items_de_la_cotizacion(): void
    {
        $ventana = TipoEspacio::create(['slug' => 'ventana', 'nombre' => 'Ventana', 'permite_calculo' => true]);
        $cotizacion = Cotizacion::create(['nombre' => 'Juan', 'telefono' => '111', 'canal' => 'web', 'estado' => 'borrador']);
        $cotizacion->items()->create([
            'tipo_espacio_id' => $ventana->id,
            'metros_lineales' => 3.5,
            'subtotal_min' => 0,
            'subtotal_max' => 0,
        ]);

        Livewire::test(LeadDetalle::class, ['cotizacion' => $cotizacion])
            ->assertSee('Ventana');
    }

    public function test_cambio_de_estado_valido_persiste(): void
    {
        $cotizacion = Cotizacion::create(['nombre' => 'Juan', 'telefono' => '111', 'canal' => 'web', 'estado' => 'borrador']);

        Livewire::test(LeadDetalle::class, ['cotizacion' => $cotizacion])
            ->call('cambiarEstado', 'contactado');

        $this->assertSame('contactado', $cotizacion->fresh()->estado);
    }

    public function test_estado_invalido_se_ignora(): void
    {
        $cotizacion = Cotizacion::create(['nombre' => 'Juan', 'telefono' => '111', 'canal' => 'web', 'estado' => 'borrador']);

        Livewire::test(LeadDetalle::class, ['cotizacion' => $cotizacion])
            ->call('cambiarEstado', 'no-existe');

        $this->assertSame('borrador', $cotizacion->fresh()->estado);
    }
}
