@php
    use App\Support\FechaEsp;

    $estadoBadge = [
        'pendiente' => 'bg-cream-deep text-ink-soft',
        'en_curso' => 'bg-amber-100 text-amber-800',
        'ejecutada' => 'bg-green-100 text-green-800',
    ];
@endphp

<div class="flex flex-col gap-6">
    <div class="grid gap-6 lg:grid-cols-2">
        {{-- Por agendar --}}
        @if ($this->porAgendar->isNotEmpty())
            <div class="border-line rounded-2xl border bg-white p-5 lg:col-span-2">
                <div class="flex items-center gap-2">
                    <span class="bg-brand-red-ui h-2 w-2 rounded-full"></span>
                    <h2 class="text-ink text-sm font-bold tracking-tight">Por agendar</h2>
                    <span class="text-ink-soft text-xs">({{ $this->porAgendar->count() }})</span>
                </div>
                <div class="divide-line mt-3 divide-y">
                    @foreach ($this->porAgendar as $t)
                        <a href="{{ route('admin.calendario') }}" wire:navigate
                            class="hover:bg-cream-deep/60 flex items-center justify-between gap-3 py-2.5 text-sm">
                            <span class="min-w-0">
                                <span class="text-ink block truncate font-medium">{{ $t->titulo }}</span>
                                <span class="text-ink-soft block truncate text-xs">
                                    {{ $t->cliente?->nombre ?? '—' }}
                                    @if ($t->clienteDireccion) · {{ $t->clienteDireccion->etiquetaCompleta() }} @endif
                                </span>
                            </span>
                            <span class="rounded-full px-2 py-0.5 text-xs font-semibold {{ $estadoBadge[$t->estado] ?? '' }} shrink-0">
                                {{ ucfirst($t->estado) }}
                            </span>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Hoy --}}
        <div class="border-line rounded-2xl border-2 bg-white p-5">
            <div class="flex items-center justify-between">
                <h2 class="text-ink text-base font-bold tracking-tight">Hoy</h2>
                <span class="text-brand-red-ui text-xs font-semibold">{{ ucfirst(FechaEsp::diaMes(now())) }}</span>
            </div>
            <div class="divide-line mt-3 divide-y">
                @forelse ($this->hoy as $t)
                    <div class="flex items-center justify-between gap-3 py-3 text-sm">
                        <span class="min-w-0">
                            <span class="text-ink block truncate font-semibold">{{ $t->titulo }}</span>
                            <span class="text-ink-soft block truncate text-xs">
                                {{ $t->cliente?->nombre ?? '—' }}
                                @if ($t->clienteDireccion) · {{ $t->clienteDireccion->etiquetaCompleta() }} @endif
                            </span>
                        </span>
                        <span class="shrink-0 text-right">
                            <span class="text-ink block text-sm font-bold">
                                {{ $t->evento->todo_el_dia ? 'Todo el día' : $t->evento->inicio->format('H:i') }}
                            </span>
                            <span class="rounded-full px-2 py-0.5 text-xs font-semibold {{ $estadoBadge[$t->estado] ?? '' }}">
                                {{ ucfirst($t->estado) }}
                            </span>
                        </span>
                    </div>
                @empty
                    <p class="text-ink-soft py-4 text-sm">Sin trabajos agendados para hoy.</p>
                @endforelse
            </div>
        </div>

        {{-- Resto de la semana --}}
        <div class="border-line rounded-2xl border bg-white p-5">
            <h2 class="text-ink text-base font-bold tracking-tight">Resto de la semana</h2>
            <div class="divide-line mt-3 divide-y">
                @forelse ($this->restoSemana as $t)
                    <div class="flex items-center justify-between gap-3 py-3 text-sm">
                        <span class="min-w-0">
                            <span class="text-ink block truncate font-medium">{{ $t->titulo }}</span>
                            <span class="text-ink-soft block truncate text-xs">
                                {{ $t->cliente?->nombre ?? '—' }}
                                @if ($t->clienteDireccion) · {{ $t->clienteDireccion->etiquetaCompleta() }} @endif
                            </span>
                        </span>
                        <span class="shrink-0 text-right">
                            <span class="text-ink-soft block text-xs font-semibold">{{ FechaEsp::corto($t->evento->inicio) }}</span>
                            <span class="rounded-full px-2 py-0.5 text-xs font-semibold {{ $estadoBadge[$t->estado] ?? '' }}">
                                {{ ucfirst($t->estado) }}
                            </span>
                        </span>
                    </div>
                @empty
                    <p class="text-ink-soft py-4 text-sm">Sin más trabajos agendados esta semana.</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Realizados --}}
    <div class="border-line rounded-2xl border bg-gray-50 p-5">
        <h2 class="text-sm font-bold tracking-tight text-gray-500">Realizados</h2>
        <div class="divide-y divide-gray-200 mt-3">
            @forelse ($this->realizados as $t)
                <div class="flex items-center justify-between gap-3 py-2.5 text-sm text-gray-500">
                    <span class="min-w-0">
                        <span class="block truncate font-medium">{{ $t->titulo }}</span>
                        <span class="block truncate text-xs text-gray-400">
                            {{ $t->cliente?->nombre ?? '—' }}
                            @if ($t->clienteDireccion) · {{ $t->clienteDireccion->etiquetaCompleta() }} @endif
                        </span>
                    </span>
                    <span class="shrink-0 text-xs text-gray-400">
                        {{ $t->finalizado_at ? FechaEsp::diaMesAnio($t->finalizado_at) : '—' }}
                    </span>
                </div>
            @empty
                <p class="py-4 text-sm text-gray-400">Todavía no hay trabajos realizados.</p>
            @endforelse
        </div>
    </div>
</div>
