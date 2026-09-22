@php
    use App\Support\FechaEsp;
@endphp

<div class="flex flex-col gap-6">
    <div>
        <h3 class="text-ink-soft text-sm font-bold tracking-wide uppercase">Hoy</h3>
        <div class="border-line divide-line mt-2 divide-y overflow-hidden rounded-lg border bg-white">
            @forelse ($this->miTrabajoHoy as $ot)
                <a href="{{ route('admin.trabajos.show', $ot) }}" wire:navigate
                    class="hover:bg-cream-deep/60 flex items-center justify-between gap-3 px-4 py-3">
                    <span>
                        <span class="text-ink block text-sm font-semibold">{{ $ot->titulo }}</span>
                        <span class="text-ink-soft block text-xs">
                            {{ $ot->cliente?->nombre }}
                            @if ($ot->evento) · {{ FechaEsp::corto($ot->evento->inicio) }} @endif
                        </span>
                    </span>
                </a>
            @empty
                <p class="text-ink-soft px-4 py-6 text-center text-sm">No tienes trabajos agendados para hoy.</p>
            @endforelse
        </div>
    </div>

    <div>
        <h3 class="text-ink-soft text-sm font-bold tracking-wide uppercase">Esta semana</h3>
        <div class="border-line divide-line mt-2 divide-y overflow-hidden rounded-lg border bg-white">
            @forelse ($this->misTrabajosSemana as $ot)
                <a href="{{ route('admin.trabajos.show', $ot) }}" wire:navigate
                    class="hover:bg-cream-deep/60 flex items-center justify-between gap-3 px-4 py-3">
                    <span>
                        <span class="text-ink block text-sm font-semibold">{{ $ot->titulo }}</span>
                        <span class="text-ink-soft block text-xs">
                            {{ $ot->cliente?->nombre }}
                            @if ($ot->evento) · {{ FechaEsp::corto($ot->evento->inicio) }} @endif
                        </span>
                    </span>
                </a>
            @empty
                <p class="text-ink-soft px-4 py-6 text-center text-sm">No tienes trabajos agendados esta semana.</p>
            @endforelse
        </div>
    </div>
</div>
