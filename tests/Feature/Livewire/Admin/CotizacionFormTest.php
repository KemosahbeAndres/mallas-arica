<?php

namespace Tests\Feature\Livewire\Admin;

use App\Livewire\Admin\Cotizaciones\CotizacionForm;
use App\Models\Cliente;
use App\Models\Cotizacion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;
use Tests\Traits\ActuaComoAdmin;

class CotizacionFormTest extends TestCase
{
    use ActuaComoAdmin, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actuarComoAdmin();
    }

    public function test_crea_una_cotizacion_para_un_cliente_existente_con_items_libres(): void
    {
        $cliente = Cliente::create(['nombre' => 'María González', 'telefono' => '+56955210032']);
        $direccion = $cliente->direcciones()->create(['direccion' => 'Los Aromos 221, Arica']);

        Livewire::test(CotizacionForm::class)
            ->set('modoCliente', 'existente')
            ->set('clienteId', $cliente->id)
            ->set('clienteDireccionId', $direccion->id)
            ->set('items.0.descripcion', 'Malla en balcón')
            ->set('items.0.precio_unitario', 30000)
            ->set('items.0.cantidad', 3)
            ->set('items.0.descuento_pct', 0)
            ->set('estado', 'generada')
            ->call('guardar');

        $cotizacion = Cotizacion::with('items')->first();
        $this->assertNotNull($cotizacion);
        $this->assertSame($cliente->id, $cotizacion->cliente_id);
        $this->assertSame($direccion->id, $cotizacion->cliente_direccion_id);
        $this->assertSame('generada', $cotizacion->estado);
        $this->assertCount(1, $cotizacion->items);
        $this->assertSame(90000, $cotizacion->items->first()->subtotal);
        $this->assertSame(90000, $cotizacion->neto);
        $this->assertSame((int) round(90000 * 0.19), $cotizacion->iva);
    }

    public function test_cliente_nuevo_se_crea_junto_con_la_cotizacion(): void
    {
        Livewire::test(CotizacionForm::class)
            ->set('modoCliente', 'nuevo')
            ->set('nuevoNombre', 'Pedro Rojas')
            ->set('nuevoTelefono', '+56988301145')
            ->set('direccionLibre', 'Sotomayor 884, Arica')
            ->set('items.0.descripcion', 'Instalación ventana')
            ->set('items.0.precio_unitario', 45000)
            ->set('items.0.cantidad', 1)
            ->call('guardar');

        $cliente = Cliente::with('direcciones')->firstWhere('nombre', 'Pedro Rojas');
        $this->assertNotNull($cliente);
        $this->assertCount(1, $cliente->direcciones);

        $cotizacion = Cotizacion::first();
        $this->assertSame($cliente->id, $cotizacion->cliente_id);
        $this->assertSame('Sotomayor 884, Arica', $cliente->direcciones->first()->direccion);
    }

    public function test_descuento_global_reduce_el_neto(): void
    {
        $cliente = Cliente::create(['nombre' => 'X']);

        Livewire::test(CotizacionForm::class)
            ->set('clienteId', $cliente->id)
            ->set('items.0.descripcion', 'Línea')
            ->set('items.0.precio_unitario', 100000)
            ->set('items.0.cantidad', 1)
            ->set('descuentoPct', 10)
            ->assertSet('neto', 90000)
            ->call('guardar');

        $this->assertSame(90000, Cotizacion::first()->neto);
    }

    public function test_descripcion_de_item_es_obligatoria(): void
    {
        $cliente = Cliente::create(['nombre' => 'X']);

        Livewire::test(CotizacionForm::class)
            ->set('clienteId', $cliente->id)
            ->set('items.0.descripcion', '')
            ->call('guardar')
            ->assertHasErrors('items.0.descripcion');

        $this->assertDatabaseCount('cotizaciones', 0);
    }

    public function test_exige_elegir_cliente_en_modo_existente(): void
    {
        Livewire::test(CotizacionForm::class)
            ->set('modoCliente', 'existente')
            ->set('clienteId', null)
            ->set('items.0.descripcion', 'Línea')
            ->call('guardar')
            ->assertHasErrors('clienteId');
    }

    public function test_editar_reemplaza_los_items(): void
    {
        $cliente = Cliente::create(['nombre' => 'X']);
        $cotizacion = Cotizacion::create(['cliente_id' => $cliente->id, 'nombre' => 'X', 'estado' => 'borrador']);
        $cotizacion->items()->create(['descripcion' => 'Vieja', 'precio_unitario' => 1000, 'cantidad' => 1, 'subtotal' => 1000]);

        Livewire::test(CotizacionForm::class, ['cotizacion' => $cotizacion])
            ->assertSet('items.0.descripcion', 'Vieja')
            ->set('items.0.descripcion', 'Nueva')
            ->set('items.0.precio_unitario', 5000)
            ->call('guardar');

        $cotizacion->refresh()->load('items');
        $this->assertCount(1, $cotizacion->items);
        $this->assertSame('Nueva', $cotizacion->items->first()->descripcion);
        $this->assertSame(5000, $cotizacion->items->first()->subtotal);
    }
}
