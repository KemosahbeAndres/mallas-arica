<?php

namespace Tests\Unit;

use App\Models\Cotizacion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CotizacionSoftDeleteTest extends TestCase
{
    use RefreshDatabase;

    public function test_borrar_una_cotizacion_no_la_elimina_fisicamente(): void
    {
        $cotizacion = Cotizacion::create([
            'nombre' => 'Juan Pérez',
            'telefono' => '+56912345678',
            'estado' => 'borrador',
        ]);

        $cotizacion->delete();

        $this->assertSoftDeleted($cotizacion);
        $this->assertDatabaseHas('cotizaciones', ['id' => $cotizacion->id]);
        $this->assertNull(Cotizacion::find($cotizacion->id));
        $this->assertNotNull(Cotizacion::withTrashed()->find($cotizacion->id));
    }

    public function test_borrar_una_cotizacion_hace_soft_delete_en_cascada_de_los_items(): void
    {
        $cotizacion = Cotizacion::create([
            'nombre' => 'Juan Pérez',
            'telefono' => '+56912345678',
            'estado' => 'borrador',
        ]);

        $item = $cotizacion->items()->create([
            'descripcion' => 'Malla en ventana',
            'precio_unitario' => 25000,
            'cantidad' => 4,
            'descuento_pct' => 0,
            'subtotal' => 100000,
        ]);

        $cotizacion->delete();

        $this->assertSoftDeleted($item);
    }
}
