<?php

namespace App\Livewire\Admin\Clientes;

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

    // Sub-form de agenda.
    public ?int $agendandoOtId = null;

    public string $agendaFecha = '';

    public string $agendaHora = '09:00';

    public ?string $flash = null;

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
        ]);

        $ot->update(['meses_mantencion' => $this->otMesesMantencion]);
        $trabajos->cambiarEstado($ot, $this->otEstado, $this->otFecha);

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
