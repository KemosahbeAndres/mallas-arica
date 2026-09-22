<?php

namespace App\Livewire\Admin\Resumen;

use App\Models\Cliente;
use App\Models\Cotizacion;
use App\Models\Evento;
use App\Models\Trabajo;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Dashboard «Resumen» (diseño/dashboard-v1.pdf pág. 1). Los criterios de los
 * KPIs son aproximados a lo que hay hoy en BD y están documentados en
 * CLAUDE.md §11 sexies — se afinan cuando lleguen el rediseño de Cotizaciones
 * y las OT (tabla `trabajos`).
 */
class ResumenIndex extends Component
{
    #[Computed]
    public function cotizacionesMes(): array
    {
        $inicioMes = CarbonImmutable::now()->startOfMonth();
        $inicioMesPasado = $inicioMes->subMonth();

        $esteMes = Cotizacion::query()->where('created_at', '>=', $inicioMes)->count();
        $mesPasado = Cotizacion::query()
            ->whereBetween('created_at', [$inicioMesPasado, $inicioMes])
            ->count();

        return [
            'valor' => $esteMes,
            'variacion' => $this->variacionPorcentual($esteMes, $mesPasado),
        ];
    }

    #[Computed]
    public function pendientes(): int
    {
        // "Por responder": leads que llegaron y nadie procesó todavía.
        // Con el enum actual eso es 'borrador'; se remapea al rediseñar Cotizaciones.
        return Cotizacion::query()->where('estado', 'borrador')->count();
    }

    #[Computed]
    public function clientesActivos(): int
    {
        // "Con historial": por ahora, clientes con al menos una dirección
        // registrada. Cuando existan las OT, pasará a "con OT ejecutada".
        return Cliente::query()->has('direcciones')->count();
    }

    #[Computed]
    public function ticketPromedio(): array
    {
        $inicioMes = CarbonImmutable::now()->startOfMonth();
        $inicioMesPasado = $inicioMes->subMonth();

        // Aproximación: promedio de total_max (techo del rango) de las
        // cotizaciones con monto. Se afina con el precio real al rediseñar.
        $promedio = fn ($desde, $hasta = null) => (int) round(
            Cotizacion::query()
                ->where('total_max', '>', 0)
                ->when($hasta, fn ($q) => $q->whereBetween('created_at', [$desde, $hasta]))
                ->when(! $hasta, fn ($q) => $q->where('created_at', '>=', $desde))
                ->avg('total_max') ?? 0
        );

        $esteMes = $promedio($inicioMes);
        $mesPasado = $promedio($inicioMesPasado, $inicioMes);

        return [
            'valor' => $esteMes,
            'variacion' => $this->variacionPorcentual($esteMes, $mesPasado),
        ];
    }

    #[Computed]
    public function trabajosSemana(): Collection
    {
        $hoy = CarbonImmutable::now();

        return Evento::query()
            ->with('cliente:id,nombre')
            ->vigentes()
            ->entre($hoy->startOfWeek(CarbonImmutable::MONDAY), $hoy->endOfWeek(CarbonImmutable::SUNDAY))
            ->orderBy('inicio')
            ->limit(8)
            ->get();
    }

    #[Computed]
    public function ultimasCotizaciones(): Collection
    {
        return Cotizacion::query()
            ->with('cliente:id,nombre')
            ->orderByDesc('created_at')
            ->limit(6)
            ->get();
    }

    private function variacionPorcentual(int|float $actual, int|float $anterior): ?int
    {
        if ($anterior <= 0) {
            return $actual > 0 ? 100 : null;
        }

        return (int) round((($actual - $anterior) / $anterior) * 100);
    }

    /** OT asignadas al colaborador autenticado, agendadas para hoy. */
    #[Computed]
    public function miTrabajoHoy(): Collection
    {
        $hoy = CarbonImmutable::now();

        return $this->misOtDelRango($hoy->startOfDay(), $hoy->endOfDay());
    }

    /** OT asignadas al colaborador autenticado, agendadas esta semana. */
    #[Computed]
    public function misTrabajosSemana(): Collection
    {
        $hoy = CarbonImmutable::now();

        return $this->misOtDelRango(
            $hoy->startOfWeek(CarbonImmutable::MONDAY),
            $hoy->endOfWeek(CarbonImmutable::SUNDAY),
        );
    }

    private function misOtDelRango(CarbonImmutable $desde, CarbonImmutable $hasta): Collection
    {
        return Trabajo::query()
            ->with(['cliente:id,nombre', 'clienteDireccion:id,direccion', 'evento'])
            ->whereHas('colaboradores', fn ($q) => $q->where('users.id', auth()->id()))
            ->whereHas('evento', fn ($q) => $q->whereBetween('inicio', [$desde, $hasta]))
            ->orderBy('created_at')
            ->get();
    }

    public function render()
    {
        if (auth()->user()->esColaborador()) {
            return view('livewire.admin.resumen.resumen-colaborador')
                ->layout('components.layouts.admin', [
                    'title' => 'Resumen',
                    'subtitle' => 'Tus trabajos de hoy y de esta semana',
                ]);
        }

        return view('livewire.admin.resumen.resumen-index')
            ->layout('components.layouts.admin', [
                'title' => 'Resumen',
                'subtitle' => 'Actividad de la semana y últimas cotizaciones',
            ]);
    }
}
