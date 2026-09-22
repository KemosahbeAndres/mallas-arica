<?php

namespace Tests\Feature\Livewire\Admin;

use App\Jobs\SincronizarEventoGoogle;
use App\Livewire\Admin\Agenda\AgendaIndex;
use App\Models\Cliente;
use App\Models\Evento;
use App\Models\Trabajo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Bus;
use Livewire\Livewire;
use Tests\TestCase;
use Tests\Traits\ActuaComoAdmin;

class AgendaIndexTest extends TestCase
{
    use ActuaComoAdmin, RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = $this->actuarComoAdmin();
        Carbon::setTestNow('2026-09-07 12:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_crea_un_evento_con_hora(): void
    {
        Livewire::test(AgendaIndex::class)
            ->call('nuevoEvento', '2026-09-15')
            ->set('titulo', 'Instalación balcón')
            ->set('tipo', 'terreno')
            ->set('hora', '10:30')
            ->call('guardarEvento')
            ->assertHasNoErrors();

        $evento = Evento::firstWhere('titulo', 'Instalación balcón');
        $this->assertNotNull($evento);
        $this->assertSame('2026-09-15 10:30:00', $evento->inicio->format('Y-m-d H:i:s'));
        $this->assertFalse($evento->todo_el_dia);
    }

    public function test_evento_todo_el_dia_no_exige_hora(): void
    {
        Livewire::test(AgendaIndex::class)
            ->call('nuevoEvento', '2026-09-20')
            ->set('titulo', 'Feriado local')
            ->set('todo_el_dia', true)
            ->set('hora', '')
            ->call('guardarEvento')
            ->assertHasNoErrors();

        $evento = Evento::firstWhere('titulo', 'Feriado local');
        $this->assertSame('2026-09-20 00:00:00', $evento->inicio->format('Y-m-d H:i:s'));
        $this->assertTrue($evento->todo_el_dia);
    }

    public function test_guardar_evento_exige_al_menos_un_usuario_asignado(): void
    {
        Livewire::test(AgendaIndex::class)
            ->call('nuevoEvento', '2026-09-15')
            ->set('titulo', 'Instalación balcón')
            ->set('hora', '10:30')
            ->set('usuarios_ids', [])
            ->call('guardarEvento')
            ->assertHasErrors('usuarios_ids');

        $this->assertDatabaseCount('eventos', 0);
    }

    public function test_guardar_evento_asigna_usuarios_y_encola_sincronizacion(): void
    {
        Bus::fake();
        $colaborador = User::factory()->rol('colaborador')->create();

        Livewire::test(AgendaIndex::class)
            ->call('nuevoEvento', '2026-09-15')
            ->set('titulo', 'Instalación balcón')
            ->set('hora', '10:30')
            ->set('usuarios_ids', [$this->admin->id, $colaborador->id])
            ->call('guardarEvento')
            ->assertHasNoErrors();

        $evento = Evento::firstWhere('titulo', 'Instalación balcón');
        $this->assertSame(
            [$this->admin->id, $colaborador->id],
            $evento->usuarios()->pluck('users.id')->sort()->values()->all()
        );
        Bus::assertDispatched(SincronizarEventoGoogle::class, fn ($job) => $job->evento->is($evento));
    }

    public function test_eliminar_evento_encola_sincronizacion(): void
    {
        Bus::fake();
        $evento = Evento::create([
            'titulo' => 'Temporal',
            'tipo' => 'oficina',
            'inicio' => '2026-09-12 09:00:00',
        ]);

        Livewire::test(AgendaIndex::class)
            ->call('editarEvento', $evento->id)
            ->call('eliminarEvento');

        Bus::assertDispatched(SincronizarEventoGoogle::class, fn ($job) => $job->evento->is($evento));
    }

    public function test_titulo_es_obligatorio(): void
    {
        Livewire::test(AgendaIndex::class)
            ->call('nuevoEvento', '2026-09-10')
            ->set('titulo', '')
            ->call('guardarEvento')
            ->assertHasErrors('titulo');

        $this->assertDatabaseCount('eventos', 0);
    }

    public function test_editar_actualiza_el_evento(): void
    {
        $evento = Evento::create([
            'titulo' => 'Original',
            'tipo' => 'terreno',
            'inicio' => '2026-09-12 09:00:00',
        ]);

        Livewire::test(AgendaIndex::class)
            ->call('editarEvento', $evento->id)
            ->assertSet('titulo', 'Original')
            ->set('titulo', 'Editado')
            ->set('estado', 'hecho')
            ->set('usuarios_ids', [$this->admin->id])
            ->call('guardarEvento')
            ->assertHasNoErrors();

        $evento->refresh();
        $this->assertSame('Editado', $evento->titulo);
        $this->assertSame('hecho', $evento->estado);
    }

    public function test_eliminar_hace_soft_delete(): void
    {
        $evento = Evento::create([
            'titulo' => 'Temporal',
            'tipo' => 'oficina',
            'inicio' => '2026-09-12 09:00:00',
        ]);

        Livewire::test(AgendaIndex::class)
            ->call('editarEvento', $evento->id)
            ->call('eliminarEvento');

        $this->assertSoftDeleted('eventos', ['id' => $evento->id]);
    }

    public function test_navegacion_de_meses(): void
    {
        Livewire::test(AgendaIndex::class)
            ->assertSet('mes', '2026-09')
            ->call('mesSiguiente')
            ->assertSet('mes', '2026-10')
            ->call('mesAnterior')
            ->call('mesAnterior')
            ->assertSet('mes', '2026-08')
            ->call('irAHoy')
            ->assertSet('mes', '2026-09');
    }

    public function test_la_grilla_solo_muestra_eventos_del_mes_visible(): void
    {
        Evento::create(['titulo' => 'De septiembre', 'tipo' => 'terreno', 'inicio' => '2026-09-15 10:00:00']);
        Evento::create(['titulo' => 'De octubre', 'tipo' => 'terreno', 'inicio' => '2026-10-03 10:00:00']);

        $test = Livewire::test(AgendaIndex::class);
        $eventos = $test->instance()->eventosDelMes();

        $this->assertTrue($eventos->has('2026-09-15'));
        $this->assertFalse($eventos->has('2026-10-03'));
    }

    public function test_agenda_del_mes_ordenada_del_mas_cercano_a_hoy_primero(): void
    {
        // "Hoy" es 2026-09-07. Distancias: Cercano = 1 día, Medio = 6 días, Lejano = 13 días.
        Evento::create(['titulo' => 'Lejano', 'tipo' => 'terreno', 'inicio' => '2026-09-01 10:00:00']);
        Evento::create(['titulo' => 'Cercano', 'tipo' => 'terreno', 'inicio' => '2026-09-08 10:00:00']);
        Evento::create(['titulo' => 'Medio', 'tipo' => 'terreno', 'inicio' => '2026-09-13 10:00:00']);
        Evento::create(['titulo' => 'Cancelado', 'tipo' => 'terreno', 'estado' => 'cancelado', 'inicio' => '2026-09-07 13:00:00']);

        $agenda = Livewire::test(AgendaIndex::class)->instance()->agendaMes();

        $this->assertSame(['Cercano', 'Medio', 'Lejano'], $agenda->pluck('titulo')->all());
    }

    public function test_evento_puede_vincularse_a_un_cliente(): void
    {
        $cliente = Cliente::create(['nombre' => 'María']);

        Livewire::test(AgendaIndex::class)
            ->call('nuevoEvento', '2026-09-15')
            ->set('titulo', 'Visita María')
            ->set('hora', '11:00')
            ->set('cliente_id', $cliente->id)
            ->call('guardarEvento')
            ->assertHasNoErrors();

        $this->assertSame($cliente->id, Evento::firstWhere('titulo', 'Visita María')->cliente_id);
    }

    public function test_pendientes_por_agendar_son_ot_sin_evento(): void
    {
        $cliente = Cliente::create(['nombre' => 'Cliente OT']);

        $conEvento = Evento::create(['titulo' => 'Ya agendada', 'tipo' => 'terreno', 'inicio' => '2026-09-10 09:00:00']);
        Trabajo::create(['cliente_id' => $cliente->id, 'titulo' => 'OT agendada', 'estado' => 'pendiente', 'evento_id' => $conEvento->id]);
        Trabajo::create(['cliente_id' => $cliente->id, 'titulo' => 'OT sin agendar', 'estado' => 'pendiente']);
        Trabajo::create(['cliente_id' => $cliente->id, 'titulo' => 'OT ejecutada', 'estado' => 'ejecutada']);
        Trabajo::create(['cliente_id' => $cliente->id, 'titulo' => 'OT cancelada', 'estado' => 'cancelada']);

        $pendientes = Livewire::test(AgendaIndex::class)->instance()->pendientesPorAgendar();

        $this->assertSame(['OT sin agendar'], $pendientes->pluck('titulo')->all());
    }

    public function test_agendar_un_trabajo_pendiente_crea_el_evento_y_lo_saca_de_la_lista(): void
    {
        $cliente = Cliente::create(['nombre' => 'Cliente OT']);
        $trabajo = Trabajo::create(['cliente_id' => $cliente->id, 'titulo' => 'OT sin agendar', 'estado' => 'pendiente']);

        Livewire::test(AgendaIndex::class)
            ->call('abrirAgendarTrabajo', $trabajo->id)
            ->set('agendaFecha', '2026-09-15')
            ->set('agendaHora', '10:00')
            ->call('confirmarAgendarTrabajo')
            ->assertHasNoErrors();

        $trabajo->refresh();
        $this->assertNotNull($trabajo->evento_id);
        $this->assertSame('2026-09-15 10:00:00', $trabajo->evento->inicio->format('Y-m-d H:i:s'));

        $pendientes = Livewire::test(AgendaIndex::class)->instance()->pendientesPorAgendar();
        $this->assertFalse($pendientes->pluck('titulo')->contains('OT sin agendar'));
    }

    public function test_colaborador_es_redirigido_a_mi_agenda(): void
    {
        $this->actuarComoUsuario('colaborador');

        Livewire::test(AgendaIndex::class)->assertRedirect(route('admin.agenda.mia'));
    }

    public function test_supervisor_puede_agendar_un_trabajo(): void
    {
        $this->actuarComoUsuario('supervisor');
        $cliente = Cliente::create(['nombre' => 'Cliente OT']);
        $trabajo = Trabajo::create(['cliente_id' => $cliente->id, 'titulo' => 'OT sin agendar', 'estado' => 'pendiente']);

        Livewire::test(AgendaIndex::class)
            ->call('abrirAgendarTrabajo', $trabajo->id)
            ->set('agendaFecha', '2026-09-15')
            ->set('agendaHora', '10:00')
            ->call('confirmarAgendarTrabajo')
            ->assertHasNoErrors();

        $this->assertNotNull($trabajo->refresh()->evento_id);
    }

    public function test_confirmar_agendar_trabajo_asigna_colaboradores(): void
    {
        $colaborador = User::factory()->rol('colaborador')->create();
        $cliente = Cliente::create(['nombre' => 'Cliente OT']);
        $trabajo = Trabajo::create(['cliente_id' => $cliente->id, 'titulo' => 'OT', 'estado' => 'pendiente']);

        Livewire::test(AgendaIndex::class)
            ->call('abrirAgendarTrabajo', $trabajo->id)
            ->set('agendaFecha', '2026-09-15')
            ->set('agendaColaboradores', [$colaborador->id])
            ->call('confirmarAgendarTrabajo')
            ->assertHasNoErrors();

        $this->assertSame([$colaborador->id], $trabajo->colaboradores()->pluck('users.id')->all());
    }

    public function test_reasignar_colaboradores_sin_reagendar_no_cambia_la_fecha(): void
    {
        $c1 = User::factory()->rol('colaborador')->create();
        $c2 = User::factory()->rol('colaborador')->create();
        $cliente = Cliente::create(['nombre' => 'Cliente OT']);
        $trabajo = Trabajo::create(['cliente_id' => $cliente->id, 'titulo' => 'OT', 'estado' => 'pendiente']);

        Livewire::test(AgendaIndex::class)
            ->call('abrirAgendarTrabajo', $trabajo->id)
            ->set('agendaFecha', '2026-09-15')
            ->set('agendaColaboradores', [$c1->id])
            ->call('confirmarAgendarTrabajo');

        $eventoId = $trabajo->refresh()->evento_id;

        Livewire::test(AgendaIndex::class)
            ->call('abrirAgendarTrabajo', $trabajo->id)
            ->assertSet('agendaColaboradores', [$c1->id])
            ->set('agendaColaboradores', [$c2->id])
            ->call('confirmarAgendarTrabajo');

        $trabajo->refresh();
        $this->assertSame($eventoId, $trabajo->evento_id);
        $this->assertSame([$c2->id], $trabajo->colaboradores()->pluck('users.id')->all());
    }
}
