<?php

namespace App\Livewire\Admin\Leads;

use App\Models\Cotizacion;
use Livewire\Component;

class LeadDetalle extends Component
{
    public Cotizacion $cotizacion;

    public function mount(Cotizacion $cotizacion): void
    {
        $this->cotizacion = $cotizacion->load(['items.tipoEspacio', 'items.tipoMalla', 'items.tramoAltura', 'visita']);
    }

    public function cambiarEstado(string $nuevoEstado): void
    {
        if (! in_array($nuevoEstado, Cotizacion::ESTADOS, true)) {
            return;
        }

        $this->cotizacion->update(['estado' => $nuevoEstado]);
    }

    public function render()
    {
        return view('livewire.admin.leads.lead-detalle')
            ->layout('components.layouts.admin', ['title' => 'Cotización '.$this->cotizacion->numero]);
    }
}
