<?php

namespace App\Livewire\Admin\Cotizaciones;

use App\Models\Cotizacion;
use App\Services\CotizacionEstadoService;
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

        return Cotizacion::with(['cliente', 'clienteDireccion', 'items', 'trabajo'])
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

        $this->flash = $nuevoEstado === 'aceptada' && $cotizacion->fresh()->trabajo
            ? 'Cotización aceptada. Se creó la orden de trabajo.'
            : 'Estado actualizado.';
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
