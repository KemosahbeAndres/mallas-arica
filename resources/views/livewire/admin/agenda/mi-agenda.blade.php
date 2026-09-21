@php
    use App\Support\FechaEsp;
    $estadoBadge = [
        'pendiente' => 'bg-cream-deep text-ink-soft',
        'en_curso' => 'bg-amber-100 text-amber-800',
        'ejecutada' => 'bg-green-100 text-green-800',
        'cancelada' => 'bg-gray-100 text-gray-400',
    ];
@endphp

<div class="border-line divide-line divide-y overflow-hidden rounded-lg border bg-white">
    @forelse ($this->misTrabajos as $ot)
        <a href="{{ route('admin.trabajos.show', $ot) }}" wire:navigate
            class="hover:bg-cream-deep/60 flex items-center justify-between gap-3 px-4 py-3" wire:key="ot-{{ $ot->id }}">
            <span class="min-w-0">
                <span class="text-ink block text-sm font-semibold">{{ $ot->titulo }}</span>
                <span class="text-ink-soft block text-xs">
                    {{ $ot->cliente?->nombre ?? 'Sin cliente' }}
                    @if ($ot->clienteDireccion) · {{ $ot->clienteDireccion->direccion }} @endif
                    @if ($ot->evento) · {{ FechaEsp::corto($ot->evento->inicio) }} @else · Sin agendar @endif
                </span>
            </span>
            <span class="shrink-0 rounded-full px-2 py-0.5 text-xs font-semibold {{ $estadoBadge[$ot->estado] ?? '' }}">
                {{ str_replace('_', ' ', ucfirst($ot->estado)) }}
            </span>
        </a>
    @empty
        <p class="text-ink-soft px-4 py-6 text-center text-sm">No tienes trabajos asignados.</p>
    @endforelse
</div>
