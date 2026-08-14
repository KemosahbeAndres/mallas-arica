<?php

namespace Tests\Unit;

use App\Models\Cotizacion;
use App\Models\TipoEspacio;
use App\Models\TipoMalla;
use App\Models\TramoAltura;
use App\Services\CotizacionPdfDataBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CotizacionPdfDataBuilderTest extends TestCase
{
    use RefreshDatabase;

    private CotizacionPdfDataBuilder $builder;

    private TipoEspacio $ventana;

    private TipoMalla $mallaEstandar;

    private TramoAltura $tramoHasta1_5;

    protected function setUp(): void
    {
        parent::setUp();

        $this->builder = new CotizacionPdfDataBuilder();

        $this->ventana = TipoEspacio::create([
            'slug' => 'ventana',
            'nombre' => 'Ventanas',
            'permite_calculo' => true,
        ]);

        $this->mallaEstandar = TipoMalla::create([
            'slug' => 'estandar',
            'nombre' => 'Estándar',
            'grosor_mm' => 0.8,
            'rombo_cm' => 3.0,
            'multiplicador' => 1.0,
        ]);

        $this->tramoHasta1_5 = TramoAltura::create([
            'etiqueta' => 'Hasta 1,5 m',
            'altura_min' => 0,
            'altura_max' => 1.5,
            'requiere_visita' => false,
        ]);
    }

    public function test_calcula_el_numero_correlativo_a_partir_del_id(): void
    {
        $cotizacion = Cotizacion::create([
            'codigo' => 'MA-0001',
            'nombre' => 'Juan Pérez',
            'telefono' => '+56912345678',
            'canal' => 'web',
            'estado' => 'borrador',
        ]);

        $datos = $this->builder->construir($cotizacion);

        $this->assertSame(str_pad((string) $cotizacion->id, 4, '0', STR_PAD_LEFT), $datos['numero']);
    }

    public function test_precio_unitario_usa_el_maximo_del_rango_con_multiplicador(): void
    {
        $cotizacion = Cotizacion::create([
            'codigo' => 'MA-0002',
            'nombre' => 'Juan Pérez',
            'telefono' => '+56912345678',
            'canal' => 'web',
            'estado' => 'borrador',
        ]);

        $cotizacion->items()->create([
            'tipo_espacio_id' => $this->ventana->id,
            'tipo_malla_id' => $this->mallaEstandar->id,
            'tramo_altura_id' => $this->tramoHasta1_5->id,
            'metros_lineales' => 3.5,
            'precio_ml_min_snapshot' => 8000,
            'precio_ml_max_snapshot' => 9500,
            'multiplicador_snapshot' => 1.0,
            'subtotal_min' => 28000,
            'subtotal_max' => 33250,
        ]);

        $datos = $this->builder->construir($cotizacion);

        $this->assertCount(1, $datos['lineas']);
        $this->assertFalse($datos['lineas'][0]['pendiente']);
        $this->assertSame(9500, $datos['lineas'][0]['precioUnitario']);
        $this->assertSame(3.5, $datos['lineas'][0]['cantidad']);
        $this->assertSame(33250, $datos['lineas'][0]['subtotal']);
        $this->assertSame(33250, $datos['neto']);
        $this->assertSame((int) round(33250 * 0.19), $datos['iva']);
        $this->assertSame(33250 + (int) round(33250 * 0.19), $datos['total']);
    }

    public function test_item_sin_tarifa_snapshot_queda_marcado_como_pendiente_y_no_suma_al_neto(): void
    {
        $cotizacion = Cotizacion::create([
            'codigo' => 'MA-0003',
            'nombre' => 'Juan Pérez',
            'telefono' => '+56912345678',
            'canal' => 'web',
            'estado' => 'borrador',
            'requiere_visita' => true,
        ]);

        $cotizacion->items()->create([
            'tipo_espacio_id' => $this->ventana->id,
            'tipo_malla_id' => null,
            'tramo_altura_id' => null,
            'metros_lineales' => 5,
            'precio_ml_min_snapshot' => null,
            'precio_ml_max_snapshot' => null,
            'multiplicador_snapshot' => null,
            'subtotal_min' => 0,
            'subtotal_max' => 0,
        ]);

        $datos = $this->builder->construir($cotizacion);

        $this->assertTrue($datos['lineas'][0]['pendiente']);
        $this->assertNull($datos['lineas'][0]['subtotal']);
        $this->assertSame(0, $datos['neto']);
        $this->assertSame(0, $datos['iva']);
        $this->assertSame(0, $datos['total']);
    }
}
