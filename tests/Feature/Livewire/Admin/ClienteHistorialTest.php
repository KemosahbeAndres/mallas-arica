<?php

namespace Tests\Feature\Livewire\Admin;

use App\Livewire\Admin\Clientes\ClienteHistorial;
use App\Models\Cliente;
use App\Models\Cotizacion;
use App\Models\Evento;
use App\Models\Trabajo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;
use Tests\Traits\ActuaComoAdmin;

class ClienteHistorialTest extends TestCase
{
    use ActuaComoAdmin, RefreshDatabase;

    private Cliente $cliente;

    private Trabajo $ot;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actuarComoAdmin();
        Carbon::setTestNow('2026-09-15 12:00:00');

        $this->cliente = Cliente::create(['nombre' => 'María González', 'telefono' => '+56955210032']);
        $direccion = $this->cliente->direcciones()->create(['direccion' => 'Los Aromos 221, Arica']);
        $cot = Cotizacion::create(['cliente_id' => $this->cliente->id, 'cliente_direccion_id' => $direccion->id, 'nombre' => 'María', 'estado' => 'aceptada', 'total_max' => 90000]);
        $cot->items()->create(['descripcion' => 'Malla balcón', 'precio_unitario' => 30000, 'cantidad' => 3, 'subtotal' => 90000]);
        $this->ot = Trabajo::create([
            'cliente_id' => $this->cliente->id,
            'cliente_direccion_id' => $direccion->id,
            'cotizacion_id' => $cot->id,
            'titulo' => 'Instalación balcón',
            'estado' => 'pendiente',
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_muestra_ot_agrupada_por_direccion_y_cotizaciones(): void
    {
        Livewire::test(ClienteHistorial::class, ['clienteId' => $this->cliente->id])
            ->assertSee('Los Aromos 221, Arica')
            ->assertSee('Instalación balcón')
            ->assertSee('Cotizaciones relacionadas');
    }

    public function test_marcar_ot_como_ejecutada_setea_la_fecha(): void
    {
        Livewire::test(ClienteHistorial::class, ['clienteId' => $this->cliente->id])
            ->call('editarOt', $this->ot->id)
            ->set('otEstado', 'ejecutada')
            ->set('otFecha', '2026-03-14')
            ->set('otMesesMantencion', 18)
            ->call('guardarOt')
            ->assertHasNoErrors();

        $this->ot->refresh();
        $this->assertSame('ejecutada', $this->ot->estado);
        $this->assertSame('2026-03-14', $this->ot->finalizado_at->format('Y-m-d'));
        $this->assertSame(18, $this->ot->meses_mantencion);
    }

    public function test_alerta_de_mantencion_vencida_aparece_en_la_ficha(): void
    {
        $this->ot->update(['estado' => 'ejecutada', 'finalizado_at' => '2025-01-01', 'meses_mantencion' => 12]);

        Livewire::test(ClienteHistorial::class, ['clienteId' => $this->cliente->id])
            ->assertSee('Mantención vencida');
    }

    public function test_agendar_la_ot_crea_un_evento(): void
    {
        Livewire::test(ClienteHistorial::class, ['clienteId' => $this->cliente->id])
            ->call('abrirAgenda', $this->ot->id)
            ->set('agendaFecha', '2026-09-20')
            ->set('agendaHora', '11:00')
            ->call('guardarAgenda')
            ->assertHasNoErrors();

        $this->ot->refresh();
        $this->assertNotNull($this->ot->evento_id);
        $this->assertSame(1, Evento::count());
        $this->assertSame('2026-09-20 11:00:00', $this->ot->evento->inicio->format('Y-m-d H:i:s'));
    }

    public function test_desagendar_borra_el_evento(): void
    {
        $evento = Evento::create(['titulo' => 'x', 'tipo' => 'terreno', 'inicio' => '2026-09-20 10:00:00']);
        $this->ot->update(['evento_id' => $evento->id]);

        Livewire::test(ClienteHistorial::class, ['clienteId' => $this->cliente->id])
            ->call('desagendar', $this->ot->id);

        $this->assertNull($this->ot->refresh()->evento_id);
        $this->assertSoftDeleted('eventos', ['id' => $evento->id]);
    }

    public function test_ot_sin_direccion_va_al_grupo_sin_direccion_asignada(): void
    {
        Trabajo::create(['cliente_id' => $this->cliente->id, 'titulo' => 'OT suelta', 'estado' => 'pendiente']);

        Livewire::test(ClienteHistorial::class, ['clienteId' => $this->cliente->id])
            ->assertSee('Sin dirección asignada')
            ->assertSee('OT suelta');
    }
}
