<?php

namespace App\Livewire\Admin\Clientes;

use App\Exceptions\TrabajoTransicionInvalidaException;
use App\Models\Cliente;
use App\Models\Trabajo;
use App\Services\TrabajoService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Panel de "historial" de la ficha de Cliente (Sprint 13): OT agrupadas por
 * dirección con editor de estado / fecha de ejecución / agenda, más las
 * cotizaciones relacionadas en lectura.
 */
class ClienteHistorial extends Component
{
    public int $clienteId;

    // OT en edición inline (por id).
    public ?int $editandoOtId = null;

    public string $otEstado = 'pendiente';

    public string $otFecha = '';

    public int $otMesesMantencion = 12;

    public int $otCantidadVentanas = 0;

    public int $otCantidadBalcones = 0;

    // Sub-form de agenda.
    public ?int $agendandoOtId = null;

    public string $agendaFecha = '';

    public string $agendaHora = '09:00';

    public ?string $flash = null;

    public function mount(int $clienteId): void
    {
        abort_unless(auth()->user()->puedeGestionarClientesYCotizaciones(), 403);

        $this->clienteId = $clienteId;
    }

    #[Computed]
    public function cliente(): Cliente
    {
        return Cliente::with([
            'direcciones',
            'trabajos' => fn ($q) => $q->with('evento', 'cotizacion')->orderByDesc('created_at'),
            'cotizaciones' => fn ($q) => $q->orderByDesc('created_at'),
        ])->findOrFail($this->clienteId);
    }

    /**
     * OT agrupadas por dirección (clave: id de dirección o 0 = sin dirección).
     *
     * @return Collection<int, Collection<int, Trabajo>>
     */
    #[Computed]
    public function otPorDireccion(): Collection
    {
        return $this->cliente->trabajos->groupBy(fn (Trabajo $t) => $t->cliente_direccion_id ?? 0);
    }

    public function editarOt(int $id): void
    {
        $ot = $this->cliente->trabajos->firstWhere('id', $id);
        if (! $ot) {
            return;
        }

        $this->editandoOtId = $id;
        $this->otEstado = $ot->estado;
        $this->otFecha = $ot->finalizado_at?->format('Y-m-d') ?? CarbonImmutable::now()->format('Y-m-d');
        $this->otMesesMantencion = $ot->meses_mantencion;
        $this->otCantidadVentanas = $ot->cantidad_ventanas;
        $this->otCantidadBalcones = $ot->cantidad_balcones;
        $this->agendandoOtId = null;
        $this->flash = null;
    }

    public function guardarOt(TrabajoService $trabajos): void
    {
        $ot = Trabajo::find($this->editandoOtId);
        if (! $ot || $ot->cliente_id !== $this->clienteId) {
            return;
        }

        $this->validate([
            'otEstado' => ['required', 'in:'.implode(',', Trabajo::ESTADOS)],
            'otFecha' => ['required', 'date'],
            'otMesesMantencion' => ['integer', 'min:0', 'max:120'],
            'otCantidadVentanas' => ['integer', 'min:0', 'max:200'],
            'otCantidadBalcones' => ['integer', 'min:0', 'max:200'],
        ]);

        $ot->update([
            'meses_mantencion' => $this->otMesesMantencion,
            'cantidad_ventanas' => $this->otCantidadVentanas,
            'cantidad_balcones' => $this->otCantidadBalcones,
        ]);

        try {
            $trabajos->cambiarEstado($ot, $this->otEstado, $this->otFecha, auth()->user());
        } catch (TrabajoTransicionInvalidaException $e) {
            $this->addError('otEstado', $e->getMessage());

            return;
        }

        $this->editandoOtId = null;
        unset($this->cliente, $this->otPorDireccion);
        $this->flash = 'Orden de trabajo actualizada.';
    }

    public function cancelarEdicionOt(): void
    {
        $this->editandoOtId = null;
    }

    public function abrirAgenda(int $id): void
    {
        abort_unless(auth()->user()->puedeAgendarYAsignarTrabajos(), 403);

        $ot = $this->cliente->trabajos->firstWhere('id', $id);
        if (! $ot) {
            return;
        }

        $this->agendandoOtId = $id;
        $this->agendaFecha = $ot->evento?->inicio->format('Y-m-d') ?? CarbonImmutable::now()->addDay()->format('Y-m-d');
        $this->agendaHora = $ot->evento && ! $ot->evento->todo_el_dia ? $ot->evento->inicio->format('H:i') : '09:00';
        $this->editandoOtId = null;
        $this->flash = null;
    }

    public function guardarAgenda(TrabajoService $trabajos): void
    {
        abort_unless(auth()->user()->puedeAgendarYAsignarTrabajos(), 403);

        $ot = Trabajo::find($this->agendandoOtId);
        if (! $ot || $ot->cliente_id !== $this->clienteId) {
            return;
        }

        $this->validate([
            'agendaFecha' => ['required', 'date'],
            'agendaHora' => ['nullable', 'date_format:H:i'],
        ]);

        $trabajos->agendar($ot, $this->agendaFecha, $this->agendaHora ?: null);

        $this->agendandoOtId = null;
        unset($this->cliente, $this->otPorDireccion);
        $this->flash = 'Orden de trabajo agendada. Aparece en el calendario.';
    }

    public function desagendar(int $id, TrabajoService $trabajos): void
    {
        abort_unless(auth()->user()->puedeAgendarYAsignarTrabajos(), 403);

        $ot = Trabajo::find($id);
        if ($ot && $ot->cliente_id === $this->clienteId) {
            $trabajos->desagendar($ot);
            unset($this->cliente, $this->otPorDireccion);
            $this->flash = 'Se quitó la OT del calendario.';
        }
    }

    public function render()
    {
        return view('livewire.admin.clientes.cliente-historial');
    }
}
