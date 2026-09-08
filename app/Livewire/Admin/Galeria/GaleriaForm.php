<?php

namespace App\Livewire\Admin\Galeria;

use App\Models\GaleriaItem;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

class GaleriaForm extends Component
{
    use WithFileUploads;

    public ?int $itemId = null;

    public $foto;

    public string $titulo = '';

    public bool $publicado = true;

    public function mount(?int $editandoId = null): void
    {
        $this->cargar($editandoId);
    }

    #[On('cargar-item')]
    public function cargar(?int $editandoId): void
    {
        $this->itemId = $editandoId;
        $this->foto = null;

        if ($editandoId) {
            $item = GaleriaItem::findOrFail($editandoId);
            $this->titulo = $item->titulo;
            $this->publicado = $item->publicado;
        } else {
            $this->titulo = '';
            $this->publicado = true;
        }
    }

    public function guardar(): void
    {
        $item = $this->itemId ? GaleriaItem::find($this->itemId) : null;

        $this->validate([
            'titulo' => ['required', 'string', 'max:255'],
            'foto' => [$item ? 'nullable' : 'required', 'image', 'max:4096'],
        ]);

        $path = $item?->foto_path;

        if ($this->foto) {
            if ($item && $path) {
                Storage::disk('public')->delete($path);
            }
            $path = $this->foto->store('galeria', 'public');
        }

        GaleriaItem::updateOrCreate(
            ['id' => $item?->id],
            [
                'foto_path' => $path,
                'titulo' => $this->titulo,
                'publicado' => $this->publicado,
                'orden' => $item?->orden ?? ((GaleriaItem::max('orden') ?? 0) + 1),
            ],
        );

        $this->dispatch('galeria-actualizada');
    }

    public function render()
    {
        return view('livewire.admin.galeria.galeria-form');
    }
}
