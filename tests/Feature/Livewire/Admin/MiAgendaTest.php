<?php

namespace Tests\Feature\Livewire\Admin;

use App\Livewire\Admin\Agenda\MiAgenda;
use App\Models\Cliente;
use App\Models\Trabajo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;
use Tests\Traits\ActuaComoAdmin;

class MiAgendaTest extends TestCase
{
    use ActuaComoAdmin, RefreshDatabase;

    public function test_muestra_solo_los_trabajos_asignados_al_colaborador(): void
    {
        $colaborador = $this->actuarComoUsuario('colaborador');
        $cliente = Cliente::create(['nombre' => 'Cliente X']);

        $mia = Trabajo::create(['cliente_id' => $cliente->id, 'titulo' => 'Mi OT', 'estado' => 'pendiente']);
        $mia->colaboradores()->attach($colaborador);

        Livewire::test(MiAgenda::class)->assertSee('Mi OT');
    }

    public function test_no_muestra_trabajos_de_otro_colaborador(): void
    {
        $this->actuarComoUsuario('colaborador');
        $otro = User::factory()->rol('colaborador')->create();
        $cliente = Cliente::create(['nombre' => 'Cliente Y']);

        $ajena = Trabajo::create(['cliente_id' => $cliente->id, 'titulo' => 'OT Ajena', 'estado' => 'pendiente']);
        $ajena->colaboradores()->attach($otro);

        Livewire::test(MiAgenda::class)->assertDontSee('OT Ajena');
    }

    public function test_no_muestra_trabajos_cancelados(): void
    {
        $colaborador = $this->actuarComoUsuario('colaborador');
        $cliente = Cliente::create(['nombre' => 'Cliente Z']);

        $cancelada = Trabajo::create(['cliente_id' => $cliente->id, 'titulo' => 'OT Cancelada', 'estado' => 'cancelada']);
        $cancelada->colaboradores()->attach($colaborador);

        Livewire::test(MiAgenda::class)->assertDontSee('OT Cancelada');
    }

    public function test_no_colaborador_recibe_403(): void
    {
        $this->actuarComoAdmin();

        Livewire::test(MiAgenda::class)->assertForbidden();
    }

    public function test_no_permite_crear_ni_editar_eventos(): void
    {
        $this->actuarComoUsuario('colaborador');

        Livewire::test(MiAgenda::class)->assertDontSee('Nuevo evento');
        $this->assertFalse(method_exists(MiAgenda::class, 'guardarEvento'));
    }

    public function test_sin_google_calendar_conectado_no_muestra_seccion_de_google(): void
    {
        $this->actuarComoUsuario('colaborador');

        Livewire::test(MiAgenda::class)->assertDontSee('De tu Google Calendar');
    }
}
