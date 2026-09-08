@php
    use App\Support\FechaEsp;

    $clp = fn ($v) => '$'.number_format((int) $v, 0, ',', '.');

    $variacion = function (?int $pct) {
        if ($pct === null) {
            return ['texto' => 'Sin dato del mes anterior', 'clase' => 'text-ink-soft'];
        }
        $signo = $pct > 0 ? '+' : '';
        $clase = $pct > 0 ? 'text-green-600' : ($pct < 0 ? 'text-red-600' : 'text-ink-soft');

        return ['texto' => "{$signo}{$pct}% vs. mes anterior", 'clase' => $clase];
    };

    $cotMes = $this->cotizacionesMes;
    $ticket = $this->ticketPromedio;
    $vCot = $variacion($cotMes['variacion']);
    $vTicket = $variacion($ticket['variacion']);
@endphp

<div class="flex flex-col gap-6">
    {{-- KPIs --}}
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="border-line rounded-2xl border bg-white p-5">
            <p class="text-ink-soft text-sm">Cotizaciones (mes)</p>
            <p class="text-ink mt-2 text-3xl font-extrabold tracking-tight">{{ $cotMes['valor'] }}</p>
            <p class="mt-2 text-xs font-semibold {{ $vCot['clase'] }}">{{ $vCot['texto'] }}</p>
        </div>

        <div class="border-line rounded-2xl border bg-white p-5">
            <p class="text-ink-soft text-sm">Pendientes</p>
            <p class="text-ink mt-2 text-3xl font-extrabold tracking-tight">{{ $this->pendientes }}</p>
            <p class="text-ink-soft mt-2 text-xs font-semibold">Por responder</p>
        </div>

        <div class="border-line rounded-2xl border bg-white p-5">
            <p class="text-ink-soft text-sm">Clientes activos</p>
            <p class="text-ink mt-2 text-3xl font-extrabold tracking-tight">{{ $this->clientesActivos }}</p>
            <p class="text-ink-soft mt-2 text-xs font-semibold">Con dirección registrada</p>
        </div>

        <div class="border-line rounded-2xl border bg-white p-5">
            <p class="text-ink-soft text-sm">Ticket promedio</p>
            <p class="text-ink mt-2 text-3xl font-extrabold tracking-tight">{{ $clp($ticket['valor']) }}</p>
            <p class="mt-2 text-xs font-semibold {{ $vTicket['clase'] }}">{{ $vTicket['texto'] }}</p>
        </div>
    </div>

    {{-- Listados --}}
    <div class="grid gap-6 lg:grid-cols-2">
        <div class="border-line rounded-2xl border bg-white p-5">
            <div class="flex items-center justify-between">
                <h2 class="text-ink text-base font-bold tracking-tight">Trabajos de esta semana</h2>
                <a href="{{ route('admin.calendario') }}" wire:navigate
                    class="text-brand-red-ui text-sm font-semibold hover:underline">Ver calendario →</a>
            </div>

            <div class="divide-line mt-3 divide-y">
                @forelse ($this->trabajosSemana as $ev)
                    <div class="flex items-center justify-between gap-3 py-3 text-sm">
                        <span class="text-ink">
                            {{ $ev->titulo }}
                            @if ($ev->cliente)<span class="text-ink-soft">· {{ $ev->cliente->nombre }}</span>@endif
                        </span>
                        <span class="text-ink-soft shrink-0 text-xs">{{ FechaEsp::corto($ev->inicio) }}</span>
                    </div>
                @empty
                    <p class="text-ink-soft py-6 text-center text-sm">Sin trabajos agendados esta semana.</p>
                @endforelse
            </div>
        </div>

        <div class="border-line rounded-2xl border bg-white p-5">
            <div class="flex items-center justify-between">
                <h2 class="text-ink text-base font-bold tracking-tight">Últimas cotizaciones</h2>
                <a href="{{ route('admin.cotizaciones') }}" wire:navigate
                    class="text-brand-red-ui text-sm font-semibold hover:underline">Ver todas →</a>
            </div>

            <div class="divide-line mt-3 divide-y">
                @forelse ($this->ultimasCotizaciones as $cot)
                    <a href="{{ route('admin.cotizaciones', ['seleccionada' => $cot->id]) }}" wire:navigate
                        class="hover:bg-cream-deep/60 flex items-center justify-between gap-3 py-3 text-sm">
                        <span class="text-ink font-medium">{{ $cot->cliente?->nombre ?? $cot->nombre ?? '—' }}</span>
                        <span class="text-right">
                            <span class="text-ink block font-semibold">
                                @if ($cot->total_max > 0){{ $clp($cot->total_max) }}@else<span class="text-ink-soft font-normal">Sin monto</span>@endif
                            </span>
                            <span class="text-ink-soft block text-xs">{{ $cot->created_at->diffForHumans(['short' => true]) }}</span>
                        </span>
                    </a>
                @empty
                    <p class="text-ink-soft py-6 text-center text-sm">Todavía no hay cotizaciones.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
