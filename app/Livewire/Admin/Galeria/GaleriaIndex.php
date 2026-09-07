<?php

namespace App\Livewire\Admin\Galeria;

use App\Models\GaleriaItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class GaleriaIndex extends Component
{
    public ?int $editandoId = null;

    public bool $mostrandoFormulario = false;

    #[Computed]
    public function items()
    {
        return GaleriaItem::query()->orderBy('orden')->get();
    }

    public function nuevo(): void
    {
        $this->editandoId = null;
        $this->mostrandoFormulario = true;
    }

    public function editar(int $id): void
    {
        $this->editandoId = $id;
        $this->mostrandoFormulario = true;
    }

    public function togglePublicado(GaleriaItem $item): void
    {
        $item->update(['publicado' => ! $item->publicado]);
    }

    public function moverArriba(GaleriaItem $item): void
    {
        $anterior = GaleriaItem::where('orden', '<', $item->orden)->orderByDesc('orden')->first();

        if (! $anterior) {
            return;
        }

        DB::transaction(function () use ($item, $anterior) {
            [$ordenItem, $ordenAnterior] = [$item->orden, $anterior->orden];
            $item->update(['orden' => $ordenAnterior]);
            $anterior->update(['orden' => $ordenItem]);
        });
    }

    public function moverAbajo(GaleriaItem $item): void
    {
        $siguiente = GaleriaItem::where('orden', '>', $item->orden)->orderBy('orden')->first();

        if (! $siguiente) {
            return;
        }

        DB::transaction(function () use ($item, $siguiente) {
            [$ordenItem, $ordenSiguiente] = [$item->orden, $siguiente->orden];
            $item->update(['orden' => $ordenSiguiente]);
            $siguiente->update(['orden' => $ordenItem]);
        });
    }

    public function eliminar(GaleriaItem $item): void
    {
        Storage::disk('public')->delete($item->foto_path);
        $item->delete();
    }

    #[On('galeria-actualizada')]
    public function cerrarFormulario(): void
    {
        $this->mostrandoFormulario = false;
        $this->editandoId = null;
    }

    public function render()
    {
        return view('livewire.admin.galeria.galeria-index')->layout('components.layouts.admin', ['title' => 'Galería']);
    }
}
