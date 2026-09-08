<?php

namespace Tests\Feature\Livewire\Admin;

use App\Livewire\Admin\Cotizaciones\CotizacionesIndex;
use App\Models\Cliente;
use App\Models\Cotizacion;
use App\Models\Trabajo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;
use Tests\Traits\ActuaComoAdmin;

class CotizacionesIndexTest extends TestCase
{
    use ActuaComoAdmin, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actuarComoAdmin();
    }

    private function cotizacion(string $estado = 'borrador'): Cotizacion
    {
        $cliente = Cliente::create(['nombre' => 'Cliente '.uniqid()]);
        $cot = Cotizacion::create(['cliente_id' => $cliente->id, 'nombre' => $cliente->nombre, 'estado' => $estado]);
        $cot->items()->create(['descripcion' => 'Línea', 'precio_unitario' => 50000, 'cantidad' => 1, 'subtotal' => 50000]);

        return $cot;
    }

    public function test_filtra_por_estado(): void
    {
        $borrador = $this->cotizacion('borrador');
        $aceptada = $this->cotizacion('aceptada');

        Livewire::test(CotizacionesIndex::class)
            ->set('estadoFiltro', 'aceptada')
            ->assertSee('#'.$aceptada->folio)
            ->assertDontSee('#'.$borrador->folio);
    }

    public function test_aceptar_una_cotizacion_crea_la_ot(): void
    {
        $cot = $this->cotizacion('generada');

        Livewire::test(CotizacionesIndex::class)
            ->call('seleccionar', $cot->id)
            ->call('cambiarEstado', 'aceptada');

        $cot->refresh();
        $this->assertSame('aceptada', $cot->estado);

        $trabajo = Trabajo::where('cotizacion_id', $cot->id)->first();
        $this->assertNotNull($trabajo);
        $this->assertSame($cot->cliente_id, $trabajo->cliente_id);
        $this->assertSame('pendiente', $trabajo->estado);
    }

    public function test_aceptar_dos_veces_no_duplica_la_ot(): void
    {
        $cot = $this->cotizacion('generada');

        $test = Livewire::test(CotizacionesIndex::class)->call('seleccionar', $cot->id);
        $test->call('cambiarEstado', 'aceptada');
        $test->call('cambiarEstado', 'borrador');
        $test->call('cambiarEstado', 'aceptada');

        $this->assertSame(1, Trabajo::where('cotizacion_id', $cot->id)->count());
    }

    public function test_eliminar_hace_soft_delete(): void
    {
        $cot = $this->cotizacion();

        Livewire::test(CotizacionesIndex::class)
            ->call('seleccionar', $cot->id)
            ->call('eliminar');

        $this->assertSoftDeleted('cotizaciones', ['id' => $cot->id]);
    }
}
