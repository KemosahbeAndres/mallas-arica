<?php

namespace Tests\Feature\Livewire\Admin;

use App\Livewire\Admin\Resumen\ResumenIndex;
use App\Models\Cliente;
use App\Models\Cotizacion;
use App\Models\Evento;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;
use Tests\Traits\ActuaComoAdmin;

class ResumenIndexTest extends TestCase
{
    use ActuaComoAdmin, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actuarComoAdmin();
        Carbon::setTestNow('2026-09-15 12:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function cotizacion(array $attrs = []): Cotizacion
    {
        $creadoEn = $attrs['created_at'] ?? null;
        unset($attrs['created_at']);

        $cotizacion = Cotizacion::create(array_merge([
            'nombre' => 'Cliente X',
            'telefono' => '+56900000000',
            'canal' => 'web',
            'estado' => 'borrador',
            'total_max' => 0,
        ], $attrs));

        if ($creadoEn) {
            $cotizacion->forceFill(['created_at' => $creadoEn])->saveQuietly();
        }

        return $cotizacion;
    }

    public function test_cuenta_cotizaciones_del_mes_y_variacion(): void
    {
        // 2 este mes
        $this->cotizacion(['created_at' => '2026-09-02']);
        $this->cotizacion(['created_at' => '2026-09-10']);
        // 4 el mes pasado
        foreach (range(1, 4) as $i) {
            $this->cotizacion(['created_at' => '2026-08-05']);
        }

        $data = Livewire::test(ResumenIndex::class)->instance()->cotizacionesMes();

        $this->assertSame(2, $data['valor']);
        $this->assertSame(-50, $data['variacion']); // 2 vs 4
    }

    public function test_variacion_null_sin_mes_anterior(): void
    {
        $this->cotizacion(['created_at' => '2026-09-02']);

        $data = Livewire::test(ResumenIndex::class)->instance()->cotizacionesMes();

        $this->assertSame(1, $data['valor']);
        $this->assertSame(100, $data['variacion']); // 1 vs 0 → +100
    }

    public function test_pendientes_solo_cuenta_borradores(): void
    {
        $this->cotizacion(['estado' => 'borrador']);
        $this->cotizacion(['estado' => 'borrador']);
        $this->cotizacion(['estado' => 'contactado']);
        $this->cotizacion(['estado' => 'cerrado']);

        Livewire::test(ResumenIndex::class)->assertSet('pendientes', 2);
    }

    public function test_clientes_activos_son_los_que_tienen_direccion(): void
    {
        $conDir = Cliente::create(['nombre' => 'Con dirección']);
        $conDir->direcciones()->create(['direccion' => 'Calle 1']);
        Cliente::create(['nombre' => 'Sin dirección']);

        Livewire::test(ResumenIndex::class)->assertSet('clientesActivos', 1);
    }

    public function test_ticket_promedio_usa_total_max_e_ignora_ceros(): void
    {
        $this->cotizacion(['total_max' => 100000, 'created_at' => '2026-09-03']);
        $this->cotizacion(['total_max' => 200000, 'created_at' => '2026-09-04']);
        $this->cotizacion(['total_max' => 0, 'created_at' => '2026-09-05']); // se ignora

        $data = Livewire::test(ResumenIndex::class)->instance()->ticketPromedio();

        $this->assertSame(150000, $data['valor']);
    }

    public function test_trabajos_de_la_semana_desde_eventos(): void
    {
        Evento::create(['titulo' => 'Esta semana', 'tipo' => 'terreno', 'inicio' => '2026-09-16 10:00:00']);
        Evento::create(['titulo' => 'Cancelado', 'tipo' => 'terreno', 'estado' => 'cancelado', 'inicio' => '2026-09-17 10:00:00']);
        Evento::create(['titulo' => 'Otra semana', 'tipo' => 'terreno', 'inicio' => '2026-09-30 10:00:00']);

        $trabajos = Livewire::test(ResumenIndex::class)->instance()->trabajosSemana();

        $this->assertSame(['Esta semana'], $trabajos->pluck('titulo')->all());
    }

    public function test_ultimas_cotizaciones_ordenadas_por_fecha_descendente(): void
    {
        $vieja = $this->cotizacion(['nombre' => 'Vieja', 'created_at' => '2026-09-01']);
        $nueva = $this->cotizacion(['nombre' => 'Nueva', 'created_at' => '2026-09-14']);

        $ultimas = Livewire::test(ResumenIndex::class)->instance()->ultimasCotizaciones();

        $this->assertSame(['Nueva', 'Vieja'], $ultimas->pluck('nombre')->all());
    }

    public function test_la_pagina_carga_con_la_base_vacia(): void
    {
        Livewire::test(ResumenIndex::class)
            ->assertOk()
            ->assertSet('pendientes', 0)
            ->assertSet('clientesActivos', 0);
    }

    public function test_raiz_del_admin_redirige_a_resumen(): void
    {
        $this->get('/admin')->assertRedirect('/admin/resumen');
    }
}
