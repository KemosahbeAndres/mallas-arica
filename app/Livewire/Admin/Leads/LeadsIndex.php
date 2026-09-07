<?php

namespace App\Livewire\Admin\Leads;

use App\Models\Cotizacion;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class LeadsIndex extends Component
{
    use WithPagination;

    public string $estado = '';

    public function updatingEstado(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function cotizaciones()
    {
        return Cotizacion::query()
            ->when($this->estado !== '', fn ($q) => $q->where('estado', $this->estado))
            ->orderByDesc('created_at')
            ->paginate(20);
    }

    public function render()
    {
        return view('livewire.admin.leads.leads-index')->layout('components.layouts.admin', ['title' => 'Leads']);
    }
}
