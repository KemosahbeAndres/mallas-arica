<?php

namespace App\Livewire\Admin\Cotizaciones;

use App\Models\Cotizacion;
use App\Models\Evento;
use App\Services\CotizacionEstadoService;
use App\Services\TrabajoService;
use App\Support\FechaEsp;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class CotizacionesIndex extends Component
{
    use WithPagination;

    #[Url]
    public string $estadoFiltro = '';

    #[Url]
    public ?int $seleccionada = null;

    public ?string $flash = null;

    // --- Modal "agendar al aceptar" ---
    public bool $mostrandoAgendar = false;

    public string $mesAgendar = '';

    public string $fechaAgendar = '';

    public string $horaAgendar = '09:00';

    public bool $todoElDiaAgendar = false;

    public function updatingEstadoFiltro(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function cotizaciones()
    {
        return Cotizacion::query()
            ->with('cliente:id,nombre')
            ->when($this->estadoFiltro !== '', fn ($q) => $q->where('estado', $this->estadoFiltro))
            ->orderByDesc('created_at')
            ->paginate(15);
    }

    #[Computed]
    public function detalle(): ?Cotizacion
    {
        if (! $this->seleccionada) {
            return null;
        }

        return Cotizacion::with(['cliente', 'clienteDireccion', 'items', 'trabajo.evento'])
            ->find($this->seleccionada);
    }

    public function seleccionar(int $id): void
    {
        $this->seleccionada = $id;
        $this->flash = null;
    }

    public function cambiarEstado(string $nuevoEstado, CotizacionEstadoService $estados): void
    {
        $cotizacion = $this->detalle;

        if (! $cotizacion) {
            return;
        }

        $estados->cambiar($cotizacion, $nuevoEstado);

        unset($this->detalle, $this->cotizaciones);

        $trabajo = $cotizacion->fresh()->trabajo;

        if ($nuevoEstado === 'aceptada' && $trabajo) {
            $this->flash = 'Cotización aceptada. Se creó la orden de trabajo.';

            if (! $trabajo->evento_id) {
                $this->abrirAgendar();
            }
        } else {
            $this->flash = 'Estado actualizado.';
        }
    }

    public function abrirAgendar(): void
    {
        $hoy = CarbonImmutable::now();
        $this->mesAgendar = $hoy->format('Y-m');
        $this->fechaAgendar = $hoy->format('Y-m-d');
        $this->horaAgendar = '09:00';
        $this->todoElDiaAgendar = false;
        $this->mostrandoAgendar = true;
    }

    public function cerrarAgendar(): void
    {
        $this->mostrandoAgendar = false;
    }

    private function mesAgendarActual(): CarbonImmutable
    {
        return CarbonImmutable::createFromFormat('Y-m-d', $this->mesAgendar.'-01')->startOfDay();
    }

    public function mesAgendarAnterior(): void
    {
        $this->mesAgendar = $this->mesAgendarActual()->subMonth()->format('Y-m');
    }

    public function mesAgendarSiguiente(): void
    {
        $this->mesAgendar = $this->mesAgendarActual()->addMonth()->format('Y-m');
    }

    public function elegirDiaAgendar(string $fecha): void
    {
        $this->fechaAgendar = $fecha;
    }

    #[Computed]
    public function semanasAgendar(): array
    {
        $primero = $this->mesAgendarActual()->startOfMonth();
        $ultimo = $this->mesAgendarActual()->endOfMonth();

        $cursor = $primero->startOfWeek(CarbonImmutable::MONDAY);
        $fin = $ultimo->endOfWeek(CarbonImmutable::SUNDAY);

        $semanas = [];
        while ($cursor <= $fin) {
            $semana = [];
            for ($i = 0; $i < 7; $i++) {
                $semana[] = $cursor;
                $cursor = $cursor->addDay();
            }
            $semanas[] = $semana;
        }

        return $semanas;
    }

    /** Días del mes visible que ya tienen algún evento agendado (para el punto indicador). */
    #[Computed]
    public function diasConEventoAgendar(): Collection
    {
        $inicio = $this->mesAgendarActual()->startOfMonth();
        $fin = $this->mesAgendarActual()->endOfMonth();

        return Evento::query()
            ->entre($inicio, $fin)
            ->pluck('inicio')
            ->map(fn ($f) => $f->format('Y-m-d'))
            ->unique();
    }

    public function confirmarAgendar(TrabajoService $trabajoService): void
    {
        $this->validate([
            'fechaAgendar' => ['required', 'date'],
            'horaAgendar' => ['nullable', 'required_if:todoElDiaAgendar,false', 'date_format:H:i'],
        ]);

        $cotizacion = $this->detalle;
        $trabajo = $cotizacion?->trabajo;

        if (! $trabajo) {
            return;
        }

        $trabajoService->agendar(
            $trabajo,
            $this->fechaAgendar,
            $this->todoElDiaAgendar ? null : $this->horaAgendar,
        );

        unset($this->detalle, $this->cotizaciones);
        $this->mostrandoAgendar = false;
        $this->flash = 'Trabajo agendado para el '.FechaEsp::diaMesAnio(
            CarbonImmutable::createFromFormat('Y-m-d', $this->fechaAgendar)
        ).'.';
    }

    public function eliminar(): void
    {
        if ($this->detalle) {
            $this->detalle->delete();
            $this->seleccionada = null;
            unset($this->detalle, $this->cotizaciones);
            $this->flash = 'Cotización eliminada.';
        }
    }

    public function render()
    {
        return view('livewire.admin.cotizaciones.cotizaciones-index')
            ->layout('components.layouts.admin', [
                'title' => 'Cotizaciones',
                'subtitle' => 'Cotizaciones para clientes',
            ]);
    }
}
