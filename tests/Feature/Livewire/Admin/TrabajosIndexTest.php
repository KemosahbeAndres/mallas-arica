<?php

namespace Tests\Feature\Livewire\Admin;

use App\Livewire\Admin\Trabajos\TrabajosIndex;
use App\Models\Cliente;
use App\Models\Evento;
use App\Models\Trabajo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;
use Tests\Traits\ActuaComoAdmin;

class TrabajosIndexTest extends TestCase
{
    use ActuaComoAdmin, RefreshDatabase;

    private Cliente $cliente;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actuarComoAdmin();
        // Martes 15/09/2026 — semana calendario: lunes 14 a domingo 20.
        Carbon::setTestNow('2026-09-15 12:00:00');

        $this->cliente = Cliente::create(['nombre' => 'Cliente Trabajos']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function trabajoConEvento(string $titulo, string $estado, ?string $inicio, array $overrides = []): Trabajo
    {
        $eventoId = null;

        if ($inicio) {
            $eventoId = Evento::create([
                'titulo' => $titulo,
                'tipo' => 'terreno',
                'estado' => 'agendado',
                'inicio' => $inicio,
            ])->id;
        }

        return Trabajo::create(array_merge([
            'cliente_id' => $this->cliente->id,
            'titulo' => $titulo,
            'estado' => $estado,
            'evento_id' => $eventoId,
        ], $overrides));
    }

    public function test_agrupa_trabajos_de_hoy(): void
    {
        $this->trabajoConEvento('Instalación hoy', 'pendiente', '2026-09-15 09:00:00');

        Livewire::test(TrabajosIndex::class)
            ->assertSee('Instalación hoy')
            ->assertSeeInOrder(['Hoy', 'Instalación hoy']);
    }

    public function test_agrupa_trabajos_del_resto_de_la_semana(): void
    {
        $this->trabajoConEvento('Instalación jueves', 'pendiente', '2026-09-17 09:00:00');

        $test = Livewire::test(TrabajosIndex::class);

        $this->assertTrue($test->instance()->restoSemana->pluck('titulo')->contains('Instalación jueves'));
        $this->assertFalse($test->instance()->hoy->pluck('titulo')->contains('Instalación jueves'));
    }

    public function test_no_muestra_trabajos_de_la_proxima_semana_en_resto_de_semana(): void
    {
        $this->trabajoConEvento('Instalación semana siguiente', 'pendiente', '2026-09-22 09:00:00');

        $test = Livewire::test(TrabajosIndex::class);

        $this->assertFalse($test->instance()->restoSemana->pluck('titulo')->contains('Instalación semana siguiente'));
    }

    public function test_trabajos_sin_evento_van_a_por_agendar(): void
    {
        $this->trabajoConEvento('OT sin agendar', 'pendiente', null);

        $test = Livewire::test(TrabajosIndex::class);

        $this->assertTrue($test->instance()->porAgendar->pluck('titulo')->contains('OT sin agendar'));
        $this->assertFalse($test->instance()->hoy->pluck('titulo')->contains('OT sin agendar'));
    }

    public function test_trabajos_ejecutados_van_a_realizados_y_no_a_las_otras_listas(): void
    {
        $this->trabajoConEvento('OT ejecutada hoy', 'ejecutada', '2026-09-15 09:00:00', ['finalizado_at' => '2026-09-15']);

        $test = Livewire::test(TrabajosIndex::class);

        $this->assertTrue($test->instance()->realizados->pluck('titulo')->contains('OT ejecutada hoy'));
        $this->assertFalse($test->instance()->hoy->pluck('titulo')->contains('OT ejecutada hoy'));
    }

    public function test_trabajos_cancelados_no_aparecen_en_ninguna_lista(): void
    {
        $this->trabajoConEvento('OT cancelada', 'cancelada', '2026-09-15 09:00:00');

        $test = Livewire::test(TrabajosIndex::class);

        $this->assertFalse($test->instance()->hoy->pluck('titulo')->contains('OT cancelada'));
        $this->assertFalse($test->instance()->porAgendar->pluck('titulo')->contains('OT cancelada'));
    }
}
