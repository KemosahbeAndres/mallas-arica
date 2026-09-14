@php
    $clp = fn ($v) => '$'.number_format((int) $v, 0, ',', '.');
    $badge = [
        'borrador' => 'bg-cream-deep text-ink-soft',
        'generada' => 'bg-amber-100 text-amber-800',
        'aceptada' => 'bg-green-100 text-green-800',
        'rechazada' => 'bg-red-100 text-red-700',
    ];
@endphp

<div class="flex flex-col gap-4">
    @if ($flash)
        <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-2.5 text-sm font-medium text-green-800">
            {{ $flash }}
        </div>
    @endif

    <div class="flex flex-wrap items-center justify-between gap-3">
        <a href="{{ route('admin.cotizaciones.nueva') }}" wire:navigate
            class="bg-brand-red-ui hover:bg-brand-red-dark rounded-lg px-4 py-2 text-sm font-semibold text-white transition-colors">
            + Nueva cotización
        </a>

        <select wire:model.live="estadoFiltro" class="border-line rounded-lg border px-3 py-2 text-sm">
            <option value="">Todos los estados</option>
            @foreach (\App\Models\Cotizacion::ESTADOS as $e)
                <option value="{{ $e }}">{{ ucfirst($e) }}</option>
            @endforeach
        </select>
    </div>

    <div class="grid gap-4 lg:grid-cols-[3fr_2fr]">
        <div class="border-line overflow-hidden rounded-lg border bg-white">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-ink text-white">
                        <th class="px-4 py-2 text-left font-semibold">Folio</th>
                        <th class="px-4 py-2 text-left font-semibold">Cliente</th>
                        <th class="px-4 py-2 text-right font-semibold">Total</th>
                        <th class="px-4 py-2 text-left font-semibold">Fecha</th>
                        <th class="px-4 py-2 text-left font-semibold">Estado</th>
                    </tr>
                </thead>
                <tbody class="divide-line divide-y">
                    @forelse ($this->cotizaciones as $cot)
                        <tr wire:key="cot-{{ $cot->id }}"
                            wire:click="seleccionar({{ $cot->id }})"
                            class="cursor-pointer transition-colors {{ $seleccionada === $cot->id ? 'bg-cream-deep' : 'hover:bg-cream-deep/60' }}">
                            <td class="text-ink-soft px-4 py-2 font-mono">#{{ $cot->folio }}</td>
                            <td class="text-ink px-4 py-2 font-medium">{{ $cot->cliente?->nombre ?? $cot->nombre ?? '—' }}</td>
                            <td class="text-ink px-4 py-2 text-right">{{ $clp($cot->total) }}</td>
                            <td class="text-ink-soft px-4 py-2 whitespace-nowrap">{{ \App\Support\FechaEsp::diaMesAnio($cot->created_at) }}</td>
                            <td class="px-4 py-2">
                                <span class="rounded-full px-2 py-0.5 text-xs font-semibold {{ $badge[$cot->estado] ?? 'bg-cream-deep text-ink-soft' }}">
                                    {{ ucfirst($cot->estado) }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-ink-soft px-4 py-6 text-center">
                            {{ $estadoFiltro ? 'Sin cotizaciones en ese estado.' : 'Todavía no hay cotizaciones.' }}
                        </td></tr>
                    @endforelse
                </tbody>
            </table>

            <div class="border-line border-t px-4 py-2.5">
                {{ $this->cotizaciones->links() }}
            </div>
        </div>

        {{-- Vista previa --}}
        <div class="border-line rounded-lg border bg-white px-4 py-3">
            @if ($this->detalle)
                @php $d = $this->detalle; @endphp
                <div class="flex items-start justify-between gap-2">
                    <div>
                        <p class="text-ink-soft text-xs font-semibold tracking-wide uppercase">Cotización #{{ $d->folio }}</p>
                        <p class="text-ink mt-1 text-lg font-bold">{{ $d->cliente?->nombre ?? $d->nombre ?? '—' }}</p>
                        @if ($d->cliente?->telefono ?? $d->telefono)
                            <p class="text-ink-soft text-sm">{{ $d->cliente?->telefono ?? $d->telefono }}</p>
                        @endif
                    </div>
                    <span class="rounded-full px-2 py-0.5 text-xs font-semibold {{ $badge[$d->estado] ?? '' }}">{{ ucfirst($d->estado) }}</span>
                </div>

                @if ($d->clienteDireccion)
                    <p class="text-ink-soft mt-3 text-sm">📍 {{ $d->clienteDireccion->direccion }}</p>
                @endif

                <div class="border-line divide-line mt-4 divide-y border-t border-b py-2">
                    @forelse ($d->items as $item)
                        <div class="flex items-center justify-between gap-3 py-2 text-sm">
                            <span class="text-ink-soft">{{ $item->descripcion }} <span class="text-ink-soft/60">×{{ rtrim(rtrim(number_format($item->cantidad, 2), '0'), '.') }}</span></span>
                            <span class="text-ink shrink-0">{{ $clp($item->subtotal) }}</span>
                        </div>
                    @empty
                        <p class="text-ink-soft py-2 text-sm">Sin ítems.</p>
                    @endforelse
                </div>

                <div class="mt-3 flex items-center justify-between">
                    <span class="text-ink font-bold">Total</span>
                    <span class="text-brand-red-ui text-lg font-bold">{{ $clp($d->total) }}</span>
                </div>

                <p class="text-ink-soft mt-5 text-xs font-semibold tracking-wide uppercase">Cambiar estado</p>
                <div class="mt-2 flex flex-wrap gap-2">
                    @foreach (\App\Models\Cotizacion::ESTADOS as $e)
                        <button type="button" wire:click="cambiarEstado('{{ $e }}')"
                            class="flex-1 rounded-lg px-3 py-2 text-sm font-semibold whitespace-nowrap transition-colors
                                {{ $d->estado === $e ? 'bg-brand-red-dark text-white' : 'border-line bg-white text-ink-soft hover:bg-cream-deep border' }}">
                            {{ ucfirst($e) }}
                        </button>
                    @endforeach
                </div>

                @if ($d->trabajo)
                    <div class="mt-3 flex items-center justify-between gap-2 rounded-lg bg-green-50 px-3 py-2 text-xs text-green-800">
                        <span>
                            OT creada · estado {{ $d->trabajo->estado }}
                            @if ($d->trabajo->evento)
                                · agendada para {{ \App\Support\FechaEsp::diaMesAnio($d->trabajo->evento->inicio) }}
                            @endif
                        </span>
                        <button type="button" wire:click="abrirAgendar"
                            class="shrink-0 font-semibold whitespace-nowrap text-green-800 underline hover:text-green-900">
                            {{ $d->trabajo->evento ? 'Reagendar' : 'Agendar' }}
                        </button>
                    </div>
                @endif

                <div class="mt-4 flex flex-col gap-2">
                    <a href="{{ route('admin.cotizaciones.pdf', $d) }}"
                        class="border-line hover:bg-cream-deep rounded-lg border px-3 py-2 text-center text-sm font-semibold">
                        Descargar PDF
                    </a>
                    <div class="flex gap-2">
                        <a href="{{ route('admin.cotizaciones.editar', $d) }}" wire:navigate
                            class="border-line hover:bg-cream-deep flex-1 rounded-lg border px-3 py-2 text-center text-sm font-semibold">
                            Editar
                        </a>
                        <button type="button" wire:click="eliminar" wire:confirm="¿Eliminar esta cotización?"
                            class="text-ink-soft rounded-lg px-3 py-2 text-sm hover:bg-red-50 hover:text-red-600">
                            Eliminar
                        </button>
                    </div>
                </div>
            @else
                <p class="text-ink-soft py-8 text-center text-sm">Selecciona una cotización para ver su vista previa.</p>
            @endif
        </div>
    </div>

    {{-- Modal: agendar el trabajo --}}
    @if ($mostrandoAgendar && $this->detalle)
        @php $t = $this->detalle->trabajo; $mesAgendarStr = \Illuminate\Support\Carbon::createFromFormat('Y-m-d', $mesAgendar.'-01')->format('Y-m'); @endphp
        <div
            x-data
            x-on:keydown.escape.window="$wire.cerrarAgendar()"
            class="fixed inset-0 z-50 flex items-center justify-center p-4"
        >
            <div
                x-show="true" x-transition.opacity
                wire:click="cerrarAgendar"
                class="absolute inset-0 bg-ink/50 backdrop-blur-sm"
            ></div>

            <div
                x-show="true"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                class="border-line relative flex max-h-[90vh] w-full max-w-2xl flex-col overflow-hidden rounded-xl border bg-white shadow-2xl"
            >
                {{-- Header --}}
                <div class="bg-ink relative overflow-hidden px-5 py-4">
                    <div class="bg-brand-red-ui/30 absolute -top-10 -right-10 h-32 w-32 rounded-full blur-2xl"></div>
                    <div class="relative flex items-center justify-between gap-3">
                        <div>
                            <p class="text-brand-red-ui text-xs font-bold tracking-wide uppercase">¡Cotización aceptada!</p>
                            <h3 class="mt-1 text-lg font-bold text-white">Agenda el trabajo</h3>
                            @if ($t)
                                <p class="mt-0.5 text-sm text-white/70">{{ $t->titulo }}</p>
                            @endif
                        </div>
                        <button type="button" wire:click="cerrarAgendar"
                            class="shrink-0 rounded-lg p-1.5 text-white/70 hover:bg-white/10 hover:text-white">✕</button>
                    </div>
                </div>

                <div class="flex-1 overflow-y-auto px-5 py-4">
                    <div class="grid gap-6 sm:grid-cols-[1fr_11rem]">
                        {{-- Mini calendario --}}
                        <div>
                            <div class="flex items-center justify-between">
                                <button type="button" wire:click="mesAgendarAnterior"
                                    class="border-line hover:bg-cream-deep rounded-lg border px-2.5 py-1 text-sm">‹</button>
                                <h4 class="text-ink text-sm font-bold tracking-tight">
                                    {{ ucfirst(\App\Support\FechaEsp::mesAnio(\Illuminate\Support\Carbon::createFromFormat('Y-m-d', $mesAgendar.'-01'))) }}
                                </h4>
                                <button type="button" wire:click="mesAgendarSiguiente"
                                    class="border-line hover:bg-cream-deep rounded-lg border px-2.5 py-1 text-sm">›</button>
                            </div>

                            <div class="mt-3 grid grid-cols-7 gap-1 text-center">
                                @foreach (\App\Support\FechaEsp::DIAS_CORTOS as $dia)
                                    <div class="text-ink-soft py-1 text-[10px] font-semibold uppercase">{{ mb_substr($dia, 0, 2) }}</div>
                                @endforeach

                                @foreach ($this->semanasAgendar as $semana)
                                    @foreach ($semana as $dia)
                                        @php
                                            $claveDia = $dia->format('Y-m-d');
                                            $esDeEsteMes = $dia->format('Y-m') === $mesAgendarStr;
                                            $esHoy = $claveDia === now()->format('Y-m-d');
                                            $esElegido = $claveDia === $fechaAgendar;
                                            $tieneEvento = $this->diasConEventoAgendar->contains($claveDia);
                                        @endphp
                                        <button
                                            type="button"
                                            wire:click="elegirDiaAgendar('{{ $claveDia }}')"
                                            wire:key="agendar-dia-{{ $claveDia }}"
                                            class="relative flex aspect-square items-center justify-center rounded-lg text-sm transition-colors
                                                {{ $esElegido ? 'bg-brand-red-ui font-bold text-white shadow-sm' : ($esHoy ? 'border-brand-red-ui text-brand-red-ui border font-semibold' : 'hover:bg-cream-deep') }}
                                                {{ $esDeEsteMes ? '' : 'text-ink-soft/40' }}"
                                        >
                                            {{ $dia->day }}
                                            @if ($tieneEvento && ! $esElegido)
                                                <span class="bg-brand-red-ui absolute bottom-1 h-1 w-1 rounded-full"></span>
                                            @endif
                                        </button>
                                    @endforeach
                                @endforeach
                            </div>
                        </div>

                        {{-- Hora + resumen --}}
                        <div class="flex flex-col gap-4">
                            <div>
                                <label class="text-ink-soft text-xs font-semibold tracking-wide uppercase">Hora</label>
                                <input type="time" wire:model="horaAgendar" @disabled($todoElDiaAgendar)
                                    class="border-line focus:border-brand-red-ui mt-1.5 w-full rounded-lg border px-3 py-2 text-sm disabled:bg-cream-deep disabled:text-ink-soft/60">
                                @error('horaAgendar') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <label class="text-ink-soft flex items-center gap-2 text-sm">
                                <input type="checkbox" wire:model="todoElDiaAgendar" class="rounded">
                                Todo el día
                            </label>

                            <div class="border-line mt-auto rounded-xl border bg-cream-deep/60 p-3">
                                <p class="text-ink-soft text-[10px] font-semibold tracking-wide uppercase">Se agendará para</p>
                                <p class="text-ink mt-1 text-sm font-bold">
                                    {{ \App\Support\FechaEsp::diaMesAnio(\Illuminate\Support\Carbon::createFromFormat('Y-m-d', $fechaAgendar)) }}
                                </p>
                                <p class="text-ink-soft text-xs">
                                    {{ $todoElDiaAgendar ? 'Todo el día' : $horaAgendar.' hrs' }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Footer --}}
                <div class="border-line flex items-center justify-between gap-3 border-t bg-white px-5 py-3">
                    <button type="button" wire:click="cerrarAgendar"
                        class="text-ink-soft rounded-lg px-4 py-2 text-sm font-semibold hover:bg-cream-deep">
                        Ahora no
                    </button>
                    <button type="button" wire:click="confirmarAgendar"
                        class="bg-brand-red-ui hover:bg-brand-red-dark rounded-lg px-5 py-2 text-sm font-bold text-white shadow-sm">
                        Confirmar agenda
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
