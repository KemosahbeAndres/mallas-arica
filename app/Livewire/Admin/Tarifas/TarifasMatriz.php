<?php

namespace App\Livewire\Admin\Tarifas;

use App\Models\Tarifa;
use App\Models\TipoEspacio;
use App\Models\TramoAltura;
use Livewire\Component;

class TarifasMatriz extends Component
{
    /** @var array<int, array<int, array{min: int|string|null, max: int|string|null}>> */
    public array $precios = [];

    public function mount(): void
    {
        $tarifas = Tarifa::query()
            ->whereDate('vigente_desde', '<=', now()->toDateString())
            ->where(fn ($q) => $q->whereNull('vigente_hasta')->orWhereDate('vigente_hasta', '>=', now()->toDateString()))
            ->orderByDesc('vigente_desde')
            ->get()
            ->groupBy(fn (Tarifa $t) => "{$t->tipo_espacio_id}:{$t->tramo_altura_id}")
            ->map(fn ($g) => $g->first());

        foreach ($this->tiposEspacio() as $tipoEspacio) {
            foreach ($this->tramosAltura() as $tramoAltura) {
                $tarifa = $tarifas->get("{$tipoEspacio->id}:{$tramoAltura->id}");

                $this->precios[$tipoEspacio->id][$tramoAltura->id] = [
                    'min' => $tarifa?->precio_ml_min,
                    'max' => $tarifa?->precio_ml_max,
                ];
            }
        }
    }

    public function tiposEspacio()
    {
        return TipoEspacio::query()->where('activo', true)->orderBy('orden')->get();
    }

    public function tramosAltura()
    {
        return TramoAltura::query()->orderBy('orden')->get();
    }

    public function guardarCelda(int $tipoEspacioId, int $tramoAlturaId): void
    {
        $this->validate([
            "precios.$tipoEspacioId.$tramoAlturaId.min" => ['required', 'integer', 'min:0'],
            "precios.$tipoEspacioId.$tramoAlturaId.max" => ['required', 'integer', "gte:precios.$tipoEspacioId.$tramoAlturaId.min"],
        ]);

        $celda = $this->precios[$tipoEspacioId][$tramoAlturaId];

        Tarifa::updateOrCreate(
            [
                'tipo_espacio_id' => $tipoEspacioId,
                'tramo_altura_id' => $tramoAlturaId,
                'vigente_desde' => now()->toDateString(),
            ],
            [
                'precio_ml_min' => $celda['min'],
                'precio_ml_max' => $celda['max'],
            ],
        );

        $this->dispatch('celda-guardada', tipoEspacioId: $tipoEspacioId, tramoAlturaId: $tramoAlturaId);
    }

    public function render()
    {
        return view('livewire.admin.tarifas.tarifas-matriz', [
            'tiposEspacio' => $this->tiposEspacio(),
            'tramosAltura' => $this->tramosAltura(),
        ])->layout('components.layouts.admin', ['title' => 'Tarifas']);
    }
}
