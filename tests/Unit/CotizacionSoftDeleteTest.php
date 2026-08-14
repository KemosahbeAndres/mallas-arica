<?php

namespace Tests\Unit;

use App\Models\Cotizacion;
use App\Models\TipoEspacio;
use App\Models\TramoAltura;
use App\Models\Visita;
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
            'canal' => 'web',
            'estado' => 'borrador',
        ]);

        $cotizacion->delete();

        $this->assertSoftDeleted($cotizacion);
        $this->assertDatabaseHas('cotizaciones', ['id' => $cotizacion->id]);
        $this->assertNull(Cotizacion::find($cotizacion->id));
        $this->assertNotNull(Cotizacion::withTrashed()->find($cotizacion->id));
    }

    public function test_borrar_una_cotizacion_hace_soft_delete_en_cascada_de_items_y_visita(): void
    {
        $tipoEspacio = TipoEspacio::create([
            'slug' => 'ventana',
            'nombre' => 'Ventanas',
            'permite_calculo' => true,
        ]);

        $tramo = TramoAltura::create([
            'etiqueta' => 'Hasta 1,5 m',
            'altura_min' => 0,
            'altura_max' => 1.5,
            'requiere_visita' => false,
        ]);

        $cotizacion = Cotizacion::create([
            'nombre' => 'Juan Pérez',
            'telefono' => '+56912345678',
            'canal' => 'web',
            'estado' => 'borrador',
        ]);

        $item = $cotizacion->items()->create([
            'tipo_espacio_id' => $tipoEspacio->id,
            'tipo_malla_id' => null,
            'tramo_altura_id' => $tramo->id,
            'metros_lineales' => 3.5,
            'precio_ml_min_snapshot' => 8000,
            'precio_ml_max_snapshot' => 9500,
            'multiplicador_snapshot' => 1.0,
            'subtotal_min' => 28000,
            'subtotal_max' => 33250,
        ]);

        $visita = Visita::create([
            'cotizacion_id' => $cotizacion->id,
            'estado' => 'pendiente',
        ]);

        $cotizacion->delete();

        $this->assertSoftDeleted($item);
        $this->assertSoftDeleted($visita);
    }
}
