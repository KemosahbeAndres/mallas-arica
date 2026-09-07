<?php

namespace Tests\Feature\Livewire\Admin;

use App\Livewire\Admin\Tarifas\TarifasMatriz;
use App\Models\Tarifa;
use App\Models\TipoEspacio;
use App\Models\TramoAltura;
use App\Services\TarifaCacheService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;
use Tests\Traits\ActuaComoAdmin;

class TarifasMatrizTest extends TestCase
{
    use ActuaComoAdmin, RefreshDatabase;

    private TipoEspacio $ventana;

    private TramoAltura $tramoHasta1_5;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actuarComoAdmin();

        $this->ventana = TipoEspacio::create([
            'slug' => 'ventana',
            'nombre' => 'Ventana',
            'permite_calculo' => true,
            'activo' => true,
            'orden' => 1,
        ]);

        $this->tramoHasta1_5 = TramoAltura::create([
            'etiqueta' => 'Hasta 1,5 m',
            'altura_min' => 0,
            'altura_max' => 1.5,
            'requiere_visita' => false,
            'orden' => 1,
        ]);
    }

    public function test_editar_celda_persiste_en_bd(): void
    {
        Livewire::test(TarifasMatriz::class)
            ->set("precios.{$this->ventana->id}.{$this->tramoHasta1_5->id}.min", 8000)
            ->set("precios.{$this->ventana->id}.{$this->tramoHasta1_5->id}.max", 9500)
            ->call('guardarCelda', $this->ventana->id, $this->tramoHasta1_5->id)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('tarifas', [
            'tipo_espacio_id' => $this->ventana->id,
            'tramo_altura_id' => $this->tramoHasta1_5->id,
            'precio_ml_min' => 8000,
            'precio_ml_max' => 9500,
        ]);
    }

    public function test_max_menor_a_min_falla_validacion(): void
    {
        Livewire::test(TarifasMatriz::class)
            ->set("precios.{$this->ventana->id}.{$this->tramoHasta1_5->id}.min", 9000)
            ->set("precios.{$this->ventana->id}.{$this->tramoHasta1_5->id}.max", 5000)
            ->call('guardarCelda', $this->ventana->id, $this->tramoHasta1_5->id)
            ->assertHasErrors(["precios.{$this->ventana->id}.{$this->tramoHasta1_5->id}.max"]);

        $this->assertDatabaseCount('tarifas', 0);
    }

    public function test_guardar_invalida_la_cache_de_tarifas(): void
    {
        Tarifa::create([
            'tipo_espacio_id' => $this->ventana->id,
            'tramo_altura_id' => $this->tramoHasta1_5->id,
            'precio_ml_min' => 8000,
            'precio_ml_max' => 9500,
            'vigente_desde' => now()->subDay()->toDateString(),
        ]);

        $cache = app(TarifaCacheService::class);
        $original = $cache->buscar($this->ventana->id, $this->tramoHasta1_5->id);
        $this->assertSame(9500, $original->precio_ml_max);

        Livewire::test(TarifasMatriz::class)
            ->set("precios.{$this->ventana->id}.{$this->tramoHasta1_5->id}.min", 8000)
            ->set("precios.{$this->ventana->id}.{$this->tramoHasta1_5->id}.max", 15000)
            ->call('guardarCelda', $this->ventana->id, $this->tramoHasta1_5->id);

        $actualizado = $cache->buscar($this->ventana->id, $this->tramoHasta1_5->id);
        $this->assertSame(15000, $actualizado->precio_ml_max);
    }
}
