<?php

namespace App\Livewire\Admin\Trabajos;

use App\Models\Trabajo;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

class TrabajosIndex extends Component
{
    private function baseQuery()
    {
        return Trabajo::query()
            ->with(['cliente:id,nombre', 'clienteDireccion:id,direccion,etiqueta', 'cotizacion:id', 'evento'])
            ->whereNotIn('estado', ['cancelada']);
    }

    /** OT pendientes/en curso que aún no tienen fecha de agenda asignada. */
    #[Computed]
    public function porAgendar(): Collection
    {
        return $this->baseQuery()
            ->where('estado', '!=', 'ejecutada')
            ->whereNull('evento_id')
            ->orderByDesc('created_at')
            ->get();
    }

    /** OT agendadas para hoy (según su evento), aún no ejecutadas. */
    #[Computed]
    public function hoy(): Collection
    {
        $hoy = CarbonImmutable::now();

        return $this->baseQuery()
            ->where('estado', '!=', 'ejecutada')
            ->whereHas('evento', fn ($q) => $q->whereBetween('inicio', [$hoy->startOfDay(), $hoy->endOfDay()]))
            ->get()
            ->sortBy(fn (Trabajo $t) => $t->evento->inicio)
            ->values();
    }

    /** OT agendadas para el resto de la semana calendario (mañana a domingo), aún no ejecutadas. */
    #[Computed]
    public function restoSemana(): Collection
    {
        $hoy = CarbonImmutable::now();
        $manana = $hoy->addDay()->startOfDay();
        $finSemana = $hoy->endOfWeek(CarbonImmutable::SUNDAY);

        if ($manana->gt($finSemana)) {
            return new Collection;
        }

        return $this->baseQuery()
            ->where('estado', '!=', 'ejecutada')
            ->whereHas('evento', fn ($q) => $q->whereBetween('inicio', [$manana, $finSemana]))
            ->get()
            ->sortBy(fn (Trabajo $t) => $t->evento->inicio)
            ->values();
    }

    /** OT ya ejecutadas, más recientes primero. */
    #[Computed]
    public function realizados(): Collection
    {
        return $this->baseQuery()
            ->where('estado', 'ejecutada')
            ->orderByDesc('finalizado_at')
            ->limit(30)
            ->get();
    }

    public function render()
    {
        return view('livewire.admin.trabajos.trabajos-index')
            ->layout('components.layouts.admin', [
                'title' => 'Trabajos',
                'subtitle' => 'Órdenes de trabajo por fecha',
            ]);
    }
}
