<?php

namespace Tests\Unit;

use App\Models\Tarifa;
use App\Models\TipoEspacio;
use App\Models\TramoAltura;
use App\Services\TarifaCacheService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TarifaCacheServiceTest extends TestCase
{
    use RefreshDatabase;

    private TarifaCacheService $service;

    private TipoEspacio $ventana;

    private TramoAltura $tramoHasta1_5;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(TarifaCacheService::class);

        $this->ventana = TipoEspacio::create([
            'slug' => 'ventana',
            'nombre' => 'Ventana',
            'permite_calculo' => true,
        ]);

        $this->tramoHasta1_5 = TramoAltura::create([
            'etiqueta' => 'Hasta 1,5 m',
            'altura_min' => 0,
            'altura_max' => 1.5,
            'requiere_visita' => false,
        ]);

        Tarifa::create([
            'tipo_espacio_id' => $this->ventana->id,
            'tramo_altura_id' => $this->tramoHasta1_5->id,
            'precio_ml_min' => 8000,
            'precio_ml_max' => 9500,
            'vigente_desde' => now()->subDay()->toDateString(),
        ]);
    }

    public function test_segunda_lectura_no_repite_la_query(): void
    {
        $this->service->tarifasVigentes();

        DB::enableQueryLog();
        $this->service->tarifasVigentes();
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $this->assertCount(0, $queries);
    }

    public function test_invalidar_hace_que_la_siguiente_lectura_refleje_el_cambio(): void
    {
        $primero = $this->service->buscar($this->ventana->id, $this->tramoHasta1_5->id);
        $this->assertSame(9500, $primero->precio_ml_max);

        Tarifa::where('tipo_espacio_id', $this->ventana->id)->update(['precio_ml_max' => 12000]);
        $this->service->invalidar();

        $segundo = $this->service->buscar($this->ventana->id, $this->tramoHasta1_5->id);
        $this->assertSame(12000, $segundo->precio_ml_max);
    }

    public function test_buscar_combinacion_sin_tarifa_devuelve_null(): void
    {
        $otroTramo = TramoAltura::create([
            'etiqueta' => '2 - 3 m',
            'altura_min' => 2,
            'altura_max' => 3,
            'requiere_visita' => false,
        ]);

        $this->assertNull($this->service->buscar($this->ventana->id, $otroTramo->id));
    }
}
