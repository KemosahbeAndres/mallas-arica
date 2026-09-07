<?php

namespace App\Livewire\Admin\Clientes;

use App\Models\Cliente;
use App\Models\ClienteDireccion;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;

class ClientesIndex extends Component
{
    #[Url]
    public ?int $seleccionado = null;

    public string $buscar = '';

    // --- Formulario del cliente seleccionado ---
    public string $nombre = '';

    public string $telefono = '';

    public string $email = '';

    public string $notas = '';

    /** @var array<int, array{id: int|null, direccion: string, etiqueta: string}> */
    public array $direcciones = [];

    public ?string $guardado = null;

    protected function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:255'],
            'telefono' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:255'],
            'notas' => ['nullable', 'string', 'max:2000'],
            'direcciones' => ['array'],
            'direcciones.*.direccion' => ['required', 'string', 'max:255'],
            'direcciones.*.etiqueta' => ['nullable', 'string', 'max:60'],
        ];
    }

    protected function messages(): array
    {
        return [
            'nombre.required' => 'El nombre es obligatorio.',
            'direcciones.*.direccion.required' => 'La dirección no puede quedar vacía.',
            'email.email' => 'El correo no es válido.',
        ];
    }

    public function mount(): void
    {
        if ($this->seleccionado && Cliente::whereKey($this->seleccionado)->exists()) {
            $this->cargar($this->seleccionado);
        } else {
            $this->seleccionado = null;
        }
    }

    #[Computed]
    public function clientes()
    {
        return Cliente::query()
            ->when($this->buscar !== '', function ($q) {
                $termino = '%'.$this->buscar.'%';
                $q->where(fn ($sub) => $sub
                    ->where('nombre', 'like', $termino)
                    ->orWhere('telefono', 'like', $termino)
                    ->orWhere('email', 'like', $termino));
            })
            ->orderBy('nombre')
            ->get();
    }

    public function nuevo(): void
    {
        $this->seleccionado = null;
        $this->reset(['nombre', 'telefono', 'email', 'notas', 'direcciones', 'guardado']);
        $this->resetValidation();
    }

    public function seleccionar(int $id): void
    {
        $this->cargar($id);
    }

    private function cargar(int $id): void
    {
        $cliente = Cliente::with('direcciones')->findOrFail($id);

        $this->seleccionado = $cliente->id;
        $this->nombre = $cliente->nombre;
        $this->telefono = (string) $cliente->telefono;
        $this->email = (string) $cliente->email;
        $this->notas = (string) $cliente->notas;
        $this->direcciones = $cliente->direcciones
            ->map(fn (ClienteDireccion $d) => [
                'id' => $d->id,
                'direccion' => $d->direccion,
                'etiqueta' => (string) $d->etiqueta,
            ])
            ->all();
        $this->guardado = null;
        $this->resetValidation();
    }

    public function agregarDireccion(): void
    {
        $this->direcciones[] = ['id' => null, 'direccion' => '', 'etiqueta' => ''];
    }

    public function quitarDireccion(int $indice): void
    {
        $fila = $this->direcciones[$indice] ?? null;

        if ($fila && $fila['id']) {
            ClienteDireccion::whereKey($fila['id'])->delete();
        }

        unset($this->direcciones[$indice]);
        $this->direcciones = array_values($this->direcciones);
    }

    public function guardar(): void
    {
        $this->validate();

        DB::transaction(function () {
            $cliente = $this->seleccionado
                ? Cliente::findOrFail($this->seleccionado)
                : new Cliente;

            $cliente->fill([
                'nombre' => trim($this->nombre),
                'telefono' => trim($this->telefono) ?: null,
                'email' => trim($this->email) ?: null,
                'notas' => trim($this->notas) ?: null,
            ])->save();

            $idsConservados = [];

            foreach ($this->direcciones as $fila) {
                $direccion = $cliente->direcciones()->updateOrCreate(
                    ['id' => $fila['id']],
                    [
                        'direccion' => trim($fila['direccion']),
                        'etiqueta' => trim($fila['etiqueta']) ?: null,
                    ],
                );
                $idsConservados[] = $direccion->id;
            }

            $cliente->direcciones()->whereNotIn('id', $idsConservados)->delete();

            $this->seleccionado = $cliente->id;
        });

        unset($this->clientes);
        $this->cargar($this->seleccionado);
        $this->guardado = 'Cliente guardado.';
    }

    public function eliminar(): void
    {
        if (! $this->seleccionado) {
            return;
        }

        Cliente::whereKey($this->seleccionado)->delete();

        unset($this->clientes);
        $this->nuevo();
    }

    public function render()
    {
        return view('livewire.admin.clientes.clientes-index')
            ->layout('components.layouts.admin', [
                'title' => 'Clientes',
                'subtitle' => 'Datos de contacto y direcciones',
            ]);
    }
}
