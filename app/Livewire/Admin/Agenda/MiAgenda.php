<?php

namespace App\Livewire\Admin\Agenda;

use App\Models\Trabajo;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Agenda de solo lectura para el colaborador: sus propias OT asignadas,
 * ordenadas por fecha de agenda. Sin CRUD de eventos ni acceso a clientes
 * (eso es Agenda completa, ver AgendaIndex).
 */
class MiAgenda extends Component
{
    public function mount(): void
    {
        abort_unless(auth()->user()->esColaborador(), 403);
    }

    #[Computed]
    public function misTrabajos(): Collection
    {
        return Trabajo::query()
            ->with(['cliente:id,nombre', 'clienteDireccion:id,direccion', 'evento'])
            ->whereHas('colaboradores', fn ($q) => $q->where('users.id', auth()->id()))
            ->whereNotIn('estado', ['cancelada'])
            ->get()
            ->sortBy(fn (Trabajo $t) => $t->evento?->inicio ?? now()->addYears(10))
            ->values();
    }

    public function render()
    {
        return view('livewire.admin.agenda.mi-agenda')
            ->layout('components.layouts.admin', [
                'title' => 'Mi agenda',
                'subtitle' => 'Tus trabajos asignados',
            ]);
    }
}
