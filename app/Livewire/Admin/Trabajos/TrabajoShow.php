<?php

namespace App\Livewire\Admin\Trabajos;

use App\Exceptions\TrabajoTransicionInvalidaException;
use App\Models\Trabajo;
use App\Models\TrabajoFoto;
use App\Services\TrabajoService;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Ficha de una OT: estado, colaboradores asignados (solo lectura, se editan
 * desde Agenda) y evidencia fotográfica. Usada por colaborador (su propia OT),
 * supervisor y administrador/super_admin.
 */
class TrabajoShow extends Component
{
    use WithFileUploads;

    public Trabajo $trabajo;

    /** @var array<int, mixed> */
    public array $fotosNuevas = [];

    public ?string $flash = null;

    public function mount(Trabajo $trabajo): void
    {
        $user = auth()->user();

        abort_unless(
            $user->puedeAgendarYAsignarTrabajos() || $trabajo->colaboradores->contains('id', $user->id),
            403
        );

        $this->trabajo = $trabajo->load(['cliente', 'clienteDireccion', 'evento', 'colaboradores', 'fotos.subidoPor']);
    }

    public function subirFotos(): void
    {
        $this->validate([
            'fotosNuevas' => ['required', 'array', 'min:1'],
            'fotosNuevas.*' => ['image', 'max:2048'],
        ]);

        foreach ($this->fotosNuevas as $foto) {
            TrabajoFoto::create([
                'trabajo_id' => $this->trabajo->id,
                'foto_path' => $foto->store('trabajos', 'public'),
                'subida_por' => auth()->id(),
            ]);
        }

        $this->reset('fotosNuevas');
        $this->trabajo->load('fotos.subidoPor');
        $this->flash = 'Fotos subidas.';
    }

    public function eliminarFoto(int $fotoId): void
    {
        abort_unless(auth()->user()->puedeEliminar(), 403);

        TrabajoFoto::whereKey($fotoId)->where('trabajo_id', $this->trabajo->id)->delete();
        $this->trabajo->load('fotos.subidoPor');
    }

    public function cambiarEstado(string $estado, TrabajoService $trabajos): void
    {
        try {
            $trabajos->cambiarEstado($this->trabajo, $estado, null, auth()->user());
        } catch (TrabajoTransicionInvalidaException $e) {
            $this->addError('estado', $e->getMessage());

            return;
        }

        $this->trabajo->refresh();
        $this->flash = 'Estado actualizado a "'.$estado.'".';
    }

    #[Computed]
    public function minimoFotos(): int
    {
        return $this->trabajo->minimoFotos();
    }

    #[Computed]
    public function totalFotos(): int
    {
        return $this->trabajo->fotos->count();
    }

    public function render()
    {
        return view('livewire.admin.trabajos.trabajo-show')
            ->layout('components.layouts.admin', [
                'title' => $this->trabajo->titulo,
                'subtitle' => 'Ficha de la orden de trabajo',
            ]);
    }
}
