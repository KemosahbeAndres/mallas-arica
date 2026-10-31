<?php

namespace App\Livewire\Admin\Cotizaciones;

use App\Models\Cliente;
use App\Models\Cotizacion;
use App\Models\CotizacionItem;
use App\Models\Empresa;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Component;

class CotizacionForm extends Component
{
    public ?Cotizacion $cotizacion = null;

    // --- Cliente ---
    public string $modoCliente = 'existente'; // existente | nuevo

    public ?int $clienteId = null;

    public string $buscarCliente = '';

    public string $nuevoNombre = '';

    public string $nuevoTelefono = '';

    public string $nuevoEmail = '';

    public ?int $clienteDireccionId = null;

    public string $direccionLibre = '';

    // --- Ítems ---
    /** @var array<int, array{descripcion: string, precio_unitario: int|string, cantidad: float|string, descuento_pct: float|string}> */
    public array $items = [];

    public float|string $descuentoPct = 0;

    public string $estado = 'borrador';

    // --- Empresa emisora ---
    public ?int $empresaId = null;

    public bool $mostrarFormEmpresa = false;

    /** null = creando una empresa nueva; id = editando esa. */
    public ?int $empresaEditandoId = null;

    public string $empNombre = '';

    public string $empSubtitulo = '';

    public string $empRut = '';

    public string $empDireccion = '';

    public string $empTelefono = '';

    public string $empEmail = '';

    public bool $empPredeterminada = false;

    public function mount(?Cotizacion $cotizacion = null): void
    {
        abort_unless(auth()->user()->puedeGestionarClientesYCotizaciones(), 403);

        if ($cotizacion && $cotizacion->exists) {
            $this->cotizacion = $cotizacion->load(['items', 'cliente', 'clienteDireccion']);
            $this->modoCliente = 'existente';
            $this->clienteId = $cotizacion->cliente_id;
            $this->clienteDireccionId = $cotizacion->cliente_direccion_id;
            $this->direccionLibre = $cotizacion->cliente_direccion_id ? '' : (string) $cotizacion->direccion;
            $this->descuentoPct = (float) $cotizacion->descuento_pct;
            $this->estado = $cotizacion->estado;
            $this->items = $cotizacion->items->map(fn (CotizacionItem $i) => [
                'descripcion' => $i->descripcion,
                'precio_unitario' => (int) $i->precio_unitario,
                'cantidad' => (float) $i->cantidad,
                'descuento_pct' => (float) $i->descuento_pct,
            ])->all();
        }

        if ($this->items === []) {
            $this->items = [$this->itemVacio()];
        }

        // Cotización nueva (o vieja sin empresa) → la predeterminada.
        $this->empresaId = $this->cotizacion?->empresa_id ?? Empresa::predeterminada()?->id;
    }

    private function itemVacio(): array
    {
        return ['descripcion' => '', 'precio_unitario' => 0, 'cantidad' => 1, 'descuento_pct' => 0];
    }

    protected function rules(): array
    {
        return [
            'modoCliente' => ['required', 'in:existente,nuevo'],
            'clienteId' => ['nullable', 'required_if:modoCliente,existente', 'exists:clientes,id'],
            'nuevoNombre' => ['nullable', 'required_if:modoCliente,nuevo', 'string', 'max:255'],
            'nuevoTelefono' => ['nullable', 'string', 'max:40'],
            'nuevoEmail' => ['nullable', 'email', 'max:255'],
            'clienteDireccionId' => ['nullable', 'exists:cliente_direcciones,id'],
            'direccionLibre' => ['nullable', 'string', 'max:255'],
            'empresaId' => ['nullable', 'exists:empresas,id'],
            'estado' => ['required', 'in:'.implode(',', Cotizacion::ESTADOS)],
            'descuentoPct' => ['numeric', 'min:0', 'max:100'],
            'items' => ['array', 'min:1'],
            'items.*.descripcion' => ['required', 'string', 'max:255'],
            'items.*.precio_unitario' => ['numeric', 'min:0'],
            'items.*.cantidad' => ['integer', 'min:1'],
            'items.*.descuento_pct' => ['numeric', 'min:0', 'max:100'],
        ];
    }

    protected function messages(): array
    {
        return [
            'clienteId.required_if' => 'Elige un cliente.',
            'nuevoNombre.required_if' => 'El nombre del cliente nuevo es obligatorio.',
            'items.*.descripcion.required' => 'La descripción de la línea es obligatoria.',
        ];
    }

    #[Computed]
    public function clientesFiltrados()
    {
        return Cliente::query()
            ->withCount('trabajos')
            ->when($this->buscarCliente !== '', function ($q) {
                $t = '%'.$this->buscarCliente.'%';
                $q->where(fn ($s) => $s->where('nombre', 'like', $t)->orWhere('telefono', 'like', $t));
            })
            ->orderBy('nombre')
            ->limit(20)
            ->get(['id', 'nombre', 'telefono']);
    }

    #[Computed]
    public function empresas()
    {
        return Empresa::query()->orderByDesc('predeterminada')->orderBy('nombre')->get();
    }

    #[Computed]
    public function empresaActual(): ?Empresa
    {
        return $this->empresas->firstWhere('id', $this->empresaId);
    }

    public function seleccionarEmpresa(int $id): void
    {
        if ($this->empresas->contains('id', $id)) {
            $this->empresaId = $id;
        }
    }

    public function nuevaEmpresa(): void
    {
        $this->resetErrorBag();
        $this->reset(['empresaEditandoId', 'empNombre', 'empSubtitulo', 'empRut', 'empDireccion', 'empTelefono', 'empEmail', 'empPredeterminada']);
        $this->mostrarFormEmpresa = true;
    }

    public function editarEmpresa(): void
    {
        $empresa = $this->empresaActual;
        if (! $empresa) {
            return;
        }

        $this->resetErrorBag();
        $this->empresaEditandoId = $empresa->id;
        $this->empNombre = $empresa->nombre;
        $this->empSubtitulo = (string) $empresa->subtitulo;
        $this->empRut = (string) $empresa->rut;
        $this->empDireccion = (string) $empresa->direccion;
        $this->empTelefono = (string) $empresa->telefono;
        $this->empEmail = (string) $empresa->email;
        $this->empPredeterminada = $empresa->predeterminada;
        $this->mostrarFormEmpresa = true;
    }

    public function cancelarEmpresa(): void
    {
        $this->mostrarFormEmpresa = false;
        $this->resetErrorBag();
    }

    public function guardarEmpresa(): void
    {
        $this->validate([
            'empNombre' => ['required', 'string', 'max:255'],
            'empSubtitulo' => ['nullable', 'string', 'max:255'],
            'empRut' => ['nullable', 'string', 'max:20'],
            'empDireccion' => ['nullable', 'string', 'max:255'],
            'empTelefono' => ['nullable', 'string', 'max:40'],
            'empEmail' => ['nullable', 'email', 'max:255'],
            'empPredeterminada' => ['boolean'],
        ], [
            'empNombre.required' => 'El nombre de la empresa es obligatorio.',
        ]);

        $empresa = DB::transaction(function () {
            $empresa = $this->empresaEditandoId ? Empresa::findOrFail($this->empresaEditandoId) : new Empresa;

            // La primera empresa que exista siempre es la predeterminada.
            $predeterminada = $this->empPredeterminada || ! Empresa::where('id', '!=', $empresa->id ?? 0)->exists();

            if ($predeterminada) {
                Empresa::where('id', '!=', $empresa->id ?? 0)->update(['predeterminada' => false]);
            }

            $empresa->fill([
                'nombre' => trim($this->empNombre),
                'subtitulo' => trim($this->empSubtitulo) ?: null,
                'rut' => trim($this->empRut) ?: null,
                'direccion' => trim($this->empDireccion) ?: null,
                'telefono' => trim($this->empTelefono) ?: null,
                'email' => trim($this->empEmail) ?: null,
                // Al editar sin marcar el check, se conserva su estado actual.
                'predeterminada' => $predeterminada || ($empresa->predeterminada ?? false),
            ])->save();

            return $empresa;
        });

        unset($this->empresas, $this->empresaActual);
        $this->empresaId = $empresa->id;
        $this->mostrarFormEmpresa = false;
    }

    #[Computed]
    public function direccionesDelCliente()
    {
        if (! $this->clienteId) {
            return collect();
        }

        return Cliente::find($this->clienteId)?->direcciones()->orderBy('direccion')->get() ?? collect();
    }

    public function agregarItem(): void
    {
        $this->items[] = $this->itemVacio();
    }

    public function quitarItem(int $i): void
    {
        unset($this->items[$i]);
        $this->items = array_values($this->items);

        if ($this->items === []) {
            $this->items = [$this->itemVacio()];
        }
    }

    public function subtotalLinea(array $item): int
    {
        return CotizacionItem::calcularSubtotal(
            (int) ($item['precio_unitario'] ?: 0),
            (float) ($item['cantidad'] ?: 0),
            (float) ($item['descuento_pct'] ?: 0),
        );
    }

    #[Computed]
    public function neto(): int
    {
        $bruto = array_sum(array_map(fn ($i) => $this->subtotalLinea($i), $this->items));

        return (int) round($bruto * (1 - ((float) ($this->descuentoPct ?: 0) / 100)));
    }

    #[Computed]
    public function iva(): int
    {
        return (int) round($this->neto * Cotizacion::IVA_TASA);
    }

    #[Computed]
    public function total(): int
    {
        return $this->neto + $this->iva;
    }

    public function guardar()
    {
        $datos = $this->validate();

        $cotizacion = DB::transaction(function () use ($datos) {
            $clienteId = $datos['clienteId'];
            $direccionId = $datos['clienteDireccionId'];
            $direccionTexto = trim($datos['direccionLibre'] ?? '');

            if ($datos['modoCliente'] === 'nuevo') {
                $cliente = Cliente::firstOrNew(['telefono' => trim($datos['nuevoTelefono']) ?: null]);
                $cliente->fill([
                    'nombre' => trim($datos['nuevoNombre']),
                    'email' => trim($datos['nuevoEmail']) ?: $cliente->email,
                ])->save();
                $clienteId = $cliente->id;
                $direccionId = null;
            }

            // Dirección libre sobre un cliente existente → se guarda como dirección del cliente.
            if ($clienteId && ! $direccionId && $direccionTexto !== '') {
                $direccion = Cliente::find($clienteId)->direcciones()->firstOrCreate(['direccion' => $direccionTexto]);
                $direccionId = $direccion->id;
            }

            $cotizacion = $this->cotizacion ?? new Cotizacion;
            $cotizacion->fill([
                'cliente_id' => $clienteId,
                'cliente_direccion_id' => $direccionId,
                'empresa_id' => $datos['empresaId'],
                'nombre' => $datos['modoCliente'] === 'nuevo' ? trim($datos['nuevoNombre']) : (Cliente::find($clienteId)?->nombre),
                'telefono' => $datos['modoCliente'] === 'nuevo' ? trim($datos['nuevoTelefono']) : (Cliente::find($clienteId)?->telefono),
                'email' => $datos['modoCliente'] === 'nuevo' ? (trim($datos['nuevoEmail']) ?: null) : (Cliente::find($clienteId)?->email),
                'direccion' => $direccionId ? null : ($direccionTexto ?: null),
                'estado' => $datos['estado'],
                'descuento_pct' => (float) $datos['descuentoPct'],
            ]);

            // total_min/total_max se conservan en el esquema; con ítems libres
            // no hay rango, así que ambos = total neto.
            $cotizacion->total_min = $this->neto;
            $cotizacion->total_max = $this->neto;
            $cotizacion->save();

            $cotizacion->items()->delete();
            foreach ($this->items as $item) {
                $cotizacion->items()->create([
                    'descripcion' => trim($item['descripcion']),
                    'precio_unitario' => (int) ($item['precio_unitario'] ?: 0),
                    'cantidad' => (float) ($item['cantidad'] ?: 1),
                    'descuento_pct' => (float) ($item['descuento_pct'] ?: 0),
                    'subtotal' => $this->subtotalLinea($item),
                ]);
            }

            return $cotizacion;
        });

        session()->flash('cotizacion-guardada', "Cotización #{$cotizacion->folio} guardada.");

        return $this->redirect(route('admin.cotizaciones', ['seleccionada' => $cotizacion->id]), navigate: true);
    }

    public function render()
    {
        return view('livewire.admin.cotizaciones.cotizacion-form')
            ->layout('components.layouts.admin', [
                'title' => $this->cotizacion ? "Cotización #{$this->cotizacion->folio}" : 'Nueva cotización',
                'subtitle' => 'Cliente, dirección e ítems',
            ]);
    }
}
