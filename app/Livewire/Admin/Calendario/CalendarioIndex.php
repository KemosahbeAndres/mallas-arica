<?php

namespace App\Livewire\Admin\Calendario;

use App\Models\Cliente;
use App\Models\Evento;
use App\Support\FechaEsp;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;

class CalendarioIndex extends Component
{
    /** Mes visible, formato Y-m. */
    #[Url]
    public string $mes = '';

    // --- Formulario de evento (panel lateral / modal) ---
    public bool $mostrandoForm = false;

    public ?int $editandoId = null;

    public string $titulo = '';

    public string $descripcion = '';

    public string $tipo = 'terreno';

    public string $estado = 'agendado';

    public string $fecha = '';

    public string $hora = '';

    public bool $todo_el_dia = false;

    public ?int $cliente_id = null;

    public string $ubicacion = '';

    public string $notas = '';

    public ?string $flash = null;

    protected function rules(): array
    {
        return [
            'titulo' => ['required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string', 'max:2000'],
            'tipo' => ['required', 'in:'.implode(',', Evento::TIPOS)],
            'estado' => ['required', 'in:'.implode(',', Evento::ESTADOS)],
            'fecha' => ['required', 'date'],
            'hora' => ['nullable', 'required_if:todo_el_dia,false', 'date_format:H:i'],
            'todo_el_dia' => ['boolean'],
            'cliente_id' => ['nullable', 'exists:clientes,id'],
            'ubicacion' => ['nullable', 'string', 'max:255'],
            'notas' => ['nullable', 'string', 'max:2000'],
        ];
    }

    protected function messages(): array
    {
        return [
            'titulo.required' => 'El título es obligatorio.',
            'fecha.required' => 'La fecha es obligatoria.',
            'hora.required_if' => 'Indica la hora, o marca "todo el día".',
        ];
    }

    public function mount(): void
    {
        if (! preg_match('/^\d{4}-\d{2}$/', $this->mes)) {
            $this->mes = CarbonImmutable::now()->format('Y-m');
        }
    }

    private function mesActual(): CarbonImmutable
    {
        return CarbonImmutable::createFromFormat('Y-m-d', $this->mes.'-01')->startOfDay();
    }

    public function mesAnterior(): void
    {
        $this->mes = $this->mesActual()->subMonth()->format('Y-m');
    }

    public function mesSiguiente(): void
    {
        $this->mes = $this->mesActual()->addMonth()->format('Y-m');
    }

    public function irAHoy(): void
    {
        $this->mes = CarbonImmutable::now()->format('Y-m');
    }

    #[Computed]
    public function clientes(): Collection
    {
        return Cliente::query()->orderBy('nombre')->get(['id', 'nombre']);
    }

    /**
     * Eventos del mes visible, agrupados por día (Y-m-d).
     *
     * @return Collection<string, Collection<int, Evento>>
     */
    #[Computed]
    public function eventosDelMes(): Collection
    {
        $inicio = $this->mesActual()->startOfMonth();
        $fin = $this->mesActual()->endOfMonth();

        return Evento::query()
            ->with('cliente:id,nombre')
            ->entre($inicio, $fin)
            ->orderBy('inicio')
            ->get()
            ->groupBy(fn (Evento $e) => $e->inicio->format('Y-m-d'));
    }

    /**
     * Matriz de semanas para la grilla mensual (lunes a domingo).
     *
     * @return array<int, array<int, CarbonImmutable>>
     */
    #[Computed]
    public function semanas(): array
    {
        $primero = $this->mesActual()->startOfMonth();
        $ultimo = $this->mesActual()->endOfMonth();

        // La grilla arranca el lunes de la semana del día 1.
        $cursor = $primero->startOfWeek(CarbonImmutable::MONDAY);
        $fin = $ultimo->endOfWeek(CarbonImmutable::SUNDAY);

        $semanas = [];
        while ($cursor <= $fin) {
            $semana = [];
            for ($i = 0; $i < 7; $i++) {
                $semana[] = $cursor;
                $cursor = $cursor->addDay();
            }
            $semanas[] = $semana;
        }

        return $semanas;
    }

    #[Computed]
    public function agendaSemana(): Collection
    {
        $hoy = CarbonImmutable::now();

        return Evento::query()
            ->with('cliente:id,nombre')
            ->vigentes()
            ->entre($hoy->startOfWeek(CarbonImmutable::MONDAY), $hoy->endOfWeek(CarbonImmutable::SUNDAY))
            ->orderBy('inicio')
            ->get();
    }

    #[Computed]
    public function agendaMes(): Collection
    {
        return Evento::query()
            ->with('cliente:id,nombre')
            ->vigentes()
            ->entre($this->mesActual()->startOfMonth(), $this->mesActual()->endOfMonth())
            ->orderBy('inicio')
            ->get();
    }

    public function tituloMes(): string
    {
        return ucfirst(FechaEsp::mesAnio($this->mesActual()));
    }

    // --- CRUD ---

    public function nuevoEvento(?string $fecha = null): void
    {
        $this->resetForm();
        $this->fecha = $fecha ?? CarbonImmutable::now()->format('Y-m-d');
        $this->hora = '09:00';
        $this->mostrandoForm = true;
    }

    public function editarEvento(int $id): void
    {
        $evento = Evento::findOrFail($id);

        $this->editandoId = $evento->id;
        $this->titulo = $evento->titulo;
        $this->descripcion = (string) $evento->descripcion;
        $this->tipo = $evento->tipo;
        $this->estado = $evento->estado;
        $this->fecha = $evento->inicio->format('Y-m-d');
        $this->hora = $evento->inicio->format('H:i');
        $this->todo_el_dia = $evento->todo_el_dia;
        $this->cliente_id = $evento->cliente_id;
        $this->ubicacion = (string) $evento->ubicacion;
        $this->notas = (string) $evento->notas;
        $this->flash = null;
        $this->resetValidation();
        $this->mostrandoForm = true;
    }

    public function guardarEvento(): void
    {
        $datos = $this->validate();

        $inicio = $datos['todo_el_dia']
            ? CarbonImmutable::createFromFormat('Y-m-d', $datos['fecha'])->startOfDay()
            : CarbonImmutable::createFromFormat('Y-m-d H:i', $datos['fecha'].' '.$datos['hora']);

        Evento::updateOrCreate(
            ['id' => $this->editandoId],
            [
                'titulo' => trim($datos['titulo']),
                'descripcion' => trim($datos['descripcion'] ?? '') ?: null,
                'tipo' => $datos['tipo'],
                'estado' => $datos['estado'],
                'inicio' => $inicio,
                'fin' => null,
                'todo_el_dia' => $datos['todo_el_dia'],
                'cliente_id' => $datos['cliente_id'] ?: null,
                'ubicacion' => trim($datos['ubicacion'] ?? '') ?: null,
                'notas' => trim($datos['notas'] ?? '') ?: null,
            ],
        );

        $this->mes = $inicio->format('Y-m');
        $this->limpiarComputadas();
        $this->mostrandoForm = false;
        $this->flash = $this->editandoId ? 'Evento actualizado.' : 'Evento agendado.';
        $this->resetForm();
    }

    public function eliminarEvento(): void
    {
        if ($this->editandoId) {
            Evento::whereKey($this->editandoId)->delete();
            $this->limpiarComputadas();
            $this->flash = 'Evento eliminado.';
        }

        $this->mostrandoForm = false;
        $this->resetForm();
    }

    public function cerrarForm(): void
    {
        $this->mostrandoForm = false;
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->reset([
            'editandoId', 'titulo', 'descripcion', 'tipo', 'estado',
            'fecha', 'hora', 'todo_el_dia', 'cliente_id', 'ubicacion', 'notas',
        ]);
        $this->tipo = 'terreno';
        $this->estado = 'agendado';
        $this->resetValidation();
    }

    private function limpiarComputadas(): void
    {
        unset($this->eventosDelMes, $this->semanas, $this->agendaSemana, $this->agendaMes);
    }

    public function render()
    {
        return view('livewire.admin.calendario.calendario-index')
            ->layout('components.layouts.admin', [
                'title' => 'Calendario',
                'subtitle' => 'Agenda de trabajos',
            ]);
    }
}
