<?php

namespace Tests\Unit;

use App\Models\Cotizacion;
use App\Models\SiteContent;
use App\Services\CotizacionPdfDataBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CotizacionPdfDataBuilderTest extends TestCase
{
    use RefreshDatabase;

    private CotizacionPdfDataBuilder $builder;

    protected function setUp(): void
    {
        parent::setUp();
        $this->builder = app(CotizacionPdfDataBuilder::class);
    }

    private function cotizacionConItems(): Cotizacion
    {
        $cotizacion = Cotizacion::create([
            'nombre' => 'Juan Pérez',
            'telefono' => '+56912345678',
            'email' => 'juan@correo.cl',
            'direccion' => 'Los Aromos 221, Arica',
            'estado' => 'generada',
            'descuento_pct' => 0,
        ]);

        $cotizacion->items()->create([
            'descripcion' => 'Malla de protección — ventana',
            'precio_unitario' => 25000,
            'cantidad' => 4,
            'descuento_pct' => 0,
            'subtotal' => 100000,
        ]);

        return $cotizacion;
    }

    public function test_folio_se_deriva_del_id(): void
    {
        $cotizacion = $this->cotizacionConItems();

        $datos = $this->builder->construir($cotizacion);

        $this->assertSame(str_pad((string) $cotizacion->id, 4, '0', STR_PAD_LEFT), $datos['folio']);
    }

    public function test_lineas_libres_con_neto_iva_y_total(): void
    {
        $datos = $this->builder->construir($this->cotizacionConItems());

        $this->assertCount(1, $datos['lineas']);
        $this->assertSame(25000, $datos['lineas'][0]['precioUnitario']);
        $this->assertSame(4.0, $datos['lineas'][0]['cantidad']);
        $this->assertSame(100000, $datos['lineas'][0]['subtotal']);
        $this->assertSame(100000, $datos['neto']);
        $this->assertSame((int) round(100000 * 0.19), $datos['iva']);
        $this->assertSame(100000 + (int) round(100000 * 0.19), $datos['total']);
    }

    public function test_descuento_global_reduce_el_neto(): void
    {
        $cotizacion = $this->cotizacionConItems();
        $cotizacion->update(['descuento_pct' => 10]);

        $datos = $this->builder->construir($cotizacion->fresh('items'));

        $this->assertSame(90000, $datos['neto']);
        $this->assertSame(10.0, $datos['descuentoPct']);
    }

    public function test_datos_del_cliente_en_el_bloque_del_pdf(): void
    {
        $datos = $this->builder->construir($this->cotizacionConItems());

        $this->assertSame('Juan Pérez', $datos['cliente']['nombre']);
        $this->assertSame('Los Aromos 221, Arica', $datos['cliente']['direccion']);
        $this->assertStringContainsString('juan@correo.cl', $datos['cliente']['contacto']);
    }

    public function test_mensaje_de_vigencia_usa_el_default_sin_contenido_configurado(): void
    {
        $datos = $this->builder->construir($this->cotizacionConItems());

        $this->assertSame(CotizacionPdfDataBuilder::MENSAJE_VIGENCIA_DEFAULT, $datos['mensajeVigencia']);
    }

    public function test_mensaje_de_vigencia_toma_el_valor_editado_en_site_contents(): void
    {
        SiteContent::create([
            'key' => 'cotizaciones.mensaje_vigencia',
            'value' => 'Vigencia de 30 días.',
            'grupo' => 'cotizaciones',
            'label' => 'Mensaje de vigencia',
            'tipo' => 'textarea',
        ]);

        $datos = $this->builder->construir($this->cotizacionConItems());

        $this->assertSame('Vigencia de 30 días.', $datos['mensajeVigencia']);
    }
}
