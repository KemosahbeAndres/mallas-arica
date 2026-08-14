<?php

namespace Tests\Unit;

use App\Models\Tarifa;
use App\Models\TipoEspacio;
use App\Models\TipoMalla;
use App\Models\TramoAltura;
use App\Services\CotizacionCalculatorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CotizacionCalculatorServiceTest extends TestCase
{
    use RefreshDatabase;

    private CotizacionCalculatorService $service;

    private TipoEspacio $ventana;

    private TipoEspacio $piscina;

    private TipoMalla $mallaEstandar;

    private TipoMalla $mallaMascotas;

    private TramoAltura $tramoHasta1_5;

    private TramoAltura $tramo2a3;

    private TramoAltura $tramoMas3;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new CotizacionCalculatorService;

        $this->ventana = TipoEspacio::create([
            'slug' => 'ventana',
            'nombre' => 'Ventana',
            'permite_calculo' => true,
        ]);

        $this->piscina = TipoEspacio::create([
            'slug' => 'piscina',
            'nombre' => 'Piscina',
            'permite_calculo' => false,
        ]);

        $this->mallaEstandar = TipoMalla::create([
            'slug' => 'estandar',
            'nombre' => 'Estándar',
            'grosor_mm' => 0.9,
            'rombo_cm' => 5,
            'multiplicador' => 1.0,
        ]);

        $this->mallaMascotas = TipoMalla::create([
            'slug' => 'reforzada-mascotas',
            'nombre' => 'Reforzada mascotas',
            'grosor_mm' => 1.0,
            'rombo_cm' => 1.5,
            'multiplicador' => 1.35,
        ]);

        $this->tramoHasta1_5 = TramoAltura::create([
            'etiqueta' => 'Hasta 1,5 m',
            'altura_min' => 0,
            'altura_max' => 1.5,
            'requiere_visita' => false,
            'orden' => 1,
        ]);

        $this->tramo2a3 = TramoAltura::create([
            'etiqueta' => '2 - 3 m',
            'altura_min' => 2,
            'altura_max' => 3,
            'requiere_visita' => false,
            'orden' => 3,
        ]);

        $this->tramoMas3 = TramoAltura::create([
            'etiqueta' => '+3 m',
            'altura_min' => 3,
            'altura_max' => null,
            'requiere_visita' => true,
            'orden' => 4,
        ]);

        Tarifa::create([
            'tipo_espacio_id' => $this->ventana->id,
            'tramo_altura_id' => $this->tramoHasta1_5->id,
            'precio_ml_min' => 8000,
            'precio_ml_max' => 9500,
            'vigente_desde' => now()->subDay()->toDateString(),
        ]);
    }

    /** Caso 1: metros_lineales < METRAJE_MINIMO (2 ml) → se cobra el mínimo */
    public function test_metraje_bajo_minimo_cobra_minimo(): void
    {
        $resultado = $this->service->calcularItem($this->ventana->id, null, $this->tramoHasta1_5->id, 1.0);

        $this->assertFalse($resultado->esLead);
        $this->assertSame((int) ceil(2.0 * 8000 / 1000) * 1000, $resultado->subtotalMin);
        $this->assertSame((int) ceil(2.0 * 9500 / 1000) * 1000, $resultado->subtotalMax);
    }

    /** Caso 2: altura +3 m → no se calcula precio, requiere visita */
    public function test_altura_mas_de_3m_requiere_visita(): void
    {
        $resultado = $this->service->calcularItem($this->ventana->id, null, $this->tramoMas3->id, 5.0);

        $this->assertTrue($resultado->esLead);
        $this->assertTrue($resultado->requiereVisita);
        $this->assertSame('altura_requiere_visita', $resultado->motivoLead);
        $this->assertSame(0, $resultado->subtotalMin);
        $this->assertSame(0, $resultado->subtotalMax);
    }

    /** Caso 3: metros_lineales > 40 → derivar a contacto, no calcular */
    public function test_metraje_mayor_a_40_deriva_a_lead(): void
    {
        $resultado = $this->service->calcularItem($this->ventana->id, null, $this->tramoHasta1_5->id, 41.0);

        $this->assertTrue($resultado->esLead);
        $this->assertSame('metraje_excede_maximo', $resultado->motivoLead);
    }

    /** Caso 4: tipo Piscina → sin precio automático, solo lead */
    public function test_piscina_no_calcula_precio(): void
    {
        $resultado = $this->service->calcularItem($this->piscina->id, null, $this->tramoHasta1_5->id, 10.0);

        $this->assertTrue($resultado->esLead);
        $this->assertSame('tipo_sin_calculo_automatico', $resultado->motivoLead);
    }

    /** Caso 5: sin tarifa vigente para la combinación → fallback a lead, nunca $0 ni excepción */
    public function test_sin_tarifa_vigente_cae_a_lead_sin_excepcion(): void
    {
        $resultado = $this->service->calcularItem($this->ventana->id, null, $this->tramo2a3->id, 5.0);

        $this->assertTrue($resultado->esLead);
        $this->assertSame('sin_tarifa_vigente', $resultado->motivoLead);
        $this->assertSame(0, $resultado->subtotalMin);
        $this->assertSame(0, $resultado->subtotalMax);
    }

    /** Caso 6: input no numérico / negativo → tratado como inválido, no calcula */
    public function test_metraje_negativo_o_cero_es_invalido(): void
    {
        $resultado = $this->service->calcularItem($this->ventana->id, null, $this->tramoHasta1_5->id, -3.0);

        $this->assertTrue($resultado->esLead);
        $this->assertSame('metraje_invalido', $resultado->motivoLead);
    }

    /** Caso 7: snapshot de precios — el resultado no debe depender de la tarifa mutando después */
    public function test_snapshot_de_tarifa_queda_fijo_en_el_resultado(): void
    {
        $resultado = $this->service->calcularItem($this->ventana->id, null, $this->tramoHasta1_5->id, 5.0);

        $this->assertSame(8000, $resultado->precioMlMinSnapshot);
        $this->assertSame(9500, $resultado->precioMlMaxSnapshot);

        Tarifa::where('tipo_espacio_id', $this->ventana->id)
            ->where('tramo_altura_id', $this->tramoHasta1_5->id)
            ->update(['precio_ml_min' => 99999, 'precio_ml_max' => 99999]);

        $this->assertSame(8000, $resultado->precioMlMinSnapshot);
    }

    /** Caso 8: redondeo final hacia arriba al millar, y multiplicador de malla mascotas aplicado */
    public function test_redondeo_al_millar_y_multiplicador_de_malla(): void
    {
        $resultado = $this->service->calcularItem(
            $this->ventana->id,
            $this->mallaMascotas->id,
            $this->tramoHasta1_5->id,
            3.0,
        );

        $esperadoMin = (int) ceil(3.0 * 8000 * 1.35 / 1000) * 1000;
        $esperadoMax = (int) ceil(3.0 * 9500 * 1.35 / 1000) * 1000;

        $this->assertFalse($resultado->esLead);
        $this->assertSame($esperadoMin, $resultado->subtotalMin);
        $this->assertSame($esperadoMax, $resultado->subtotalMax);
        $this->assertSame($resultado->subtotalMin % 1000, 0);
        $this->assertSame($resultado->subtotalMax % 1000, 0);
    }

    public function test_calcular_cotizacion_agrega_totales_y_marca_requiere_visita_si_algun_item_es_lead(): void
    {
        $resultado = $this->service->calcularCotizacion([
            ['tipo_espacio_id' => $this->ventana->id, 'tipo_malla_id' => null, 'tramo_altura_id' => $this->tramoHasta1_5->id, 'metros_lineales' => 3.0],
            ['tipo_espacio_id' => $this->piscina->id, 'tipo_malla_id' => null, 'tramo_altura_id' => null, 'metros_lineales' => 10.0],
        ]);

        $this->assertCount(2, $resultado->items);
        $this->assertTrue($resultado->requiereVisita);
        $this->assertGreaterThan(0, $resultado->totalMin);
        $this->assertGreaterThan(0, $resultado->totalMax);
    }
}
