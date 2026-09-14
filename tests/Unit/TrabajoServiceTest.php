<?php

namespace Tests\Unit;

use App\Models\Cliente;
use App\Models\Evento;
use App\Models\Trabajo;
use App\Services\TrabajoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class TrabajoServiceTest extends TestCase
{
    use RefreshDatabase;

    private TrabajoService $service;

    private Trabajo $ot;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-09-15 12:00:00');
        $this->service = app(TrabajoService::class);

        $cliente = Cliente::create(['nombre' => 'Ana']);
        $direccion = $cliente->direcciones()->create(['direccion' => 'Calle 1']);
        $this->ot = Trabajo::create([
            'cliente_id' => $cliente->id,
            'cliente_direccion_id' => $direccion->id,
            'titulo' => 'Instalación balcón',
            'estado' => 'pendiente',
            'meses_mantencion' => 12,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_marcar_ejecutada_sin_fecha_usa_hoy(): void
    {
        $this->service->cambiarEstado($this->ot, 'ejecutada');

        $this->ot->refresh();
        $this->assertSame('ejecutada', $this->ot->estado);
        $this->assertSame('2026-09-15', $this->ot->finalizado_at->format('Y-m-d'));
    }

    public function test_marcar_ejecutada_con_fecha_explicita(): void
    {
        $this->service->cambiarEstado($this->ot, 'ejecutada', '2026-03-14');

        $this->assertSame('2026-03-14', $this->ot->refresh()->finalizado_at->format('Y-m-d'));
    }

    public function test_salir_de_ejecutada_limpia_finalizado_at(): void
    {
        $this->service->cambiarEstado($this->ot, 'ejecutada', '2026-03-14');
        $this->service->cambiarEstado($this->ot->refresh(), 'en_curso');

        $this->assertNull($this->ot->refresh()->finalizado_at);
    }

    public function test_mantencion_vencida_se_calcula_sobre_la_ot_ejecutada(): void
    {
        // ejecutada hace 14 meses, mantención 12 → vencida
        $this->service->cambiarEstado($this->ot, 'ejecutada', '2025-07-14');
        $this->assertTrue($this->ot->refresh()->mantencion_vencida);

        // ejecutada hace 2 meses → al día
        $this->service->cambiarEstado($this->ot->refresh(), 'ejecutada', '2026-07-14');
        $this->assertFalse($this->ot->refresh()->mantencion_vencida);

        // pendiente nunca está "vencida"
        $this->service->cambiarEstado($this->ot->refresh(), 'pendiente');
        $this->assertFalse($this->ot->refresh()->mantencion_vencida);
    }

    public function test_agendar_crea_un_evento_ligado_a_la_ot(): void
    {
        $evento = $this->service->agendar($this->ot, '2026-09-20', '10:30');

        $this->ot->refresh();
        $this->assertSame($evento->id, $this->ot->evento_id);
        $this->assertSame('2026-09-20 10:30:00', $evento->inicio->format('Y-m-d H:i:s'));
        $this->assertFalse($evento->todo_el_dia);
        $this->assertSame('terreno', $evento->tipo);
        $this->assertSame('Calle 1', $evento->ubicacion);
        $this->assertSame($this->ot->cliente_id, $evento->cliente_id);
    }

    public function test_reagendar_reutiliza_el_mismo_evento(): void
    {
        $ev1 = $this->service->agendar($this->ot, '2026-09-20', '10:00');
        $ev2 = $this->service->agendar($this->ot->refresh(), '2026-09-25', null);

        $this->assertSame($ev1->id, $ev2->id);
        $this->assertTrue($ev2->fresh()->todo_el_dia);
        $this->assertSame(1, Evento::count());
    }

    public function test_desagendar_borra_el_evento_y_desliga_la_ot(): void
    {
        $evento = $this->service->agendar($this->ot, '2026-09-20', '10:00');
        $this->service->desagendar($this->ot->refresh());

        $this->assertNull($this->ot->refresh()->evento_id);
        $this->assertSoftDeleted('eventos', ['id' => $evento->id]);
    }
}
