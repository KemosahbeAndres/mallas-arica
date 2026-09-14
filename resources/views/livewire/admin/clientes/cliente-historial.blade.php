@php
    use App\Support\FechaEsp;
    $clp = fn ($v) => '$'.number_format((int) $v, 0, ',', '.');
    $cliente = $this->cliente;
    $direccionesPorId = $cliente->direcciones->keyBy('id');
    $estadoBadge = [
        'pendiente' => 'bg-cream-deep text-ink-soft',
        'en_curso' => 'bg-amber-100 text-amber-800',
        'ejecutada' => 'bg-green-100 text-green-800',
        'cancelada' => 'bg-gray-100 text-gray-400',
    ];
    $cotBadge = [
        'borrador' => 'bg-cream-deep text-ink-soft',
        'generada' => 'bg-amber-100 text-amber-800',
        'aceptada' => 'bg-green-100 text-green-800',
        'rechazada' => 'bg-red-100 text-red-700',
    ];
@endphp

<div class="border-line mt-4 rounded-lg border bg-white px-4 py-4">
    @if ($flash)
        <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-2.5 text-sm font-medium text-green-800">
            {{ $flash }}
        </div>
    @endif

    {{-- Órdenes de trabajo por dirección --}}
    <h3 class="text-ink-soft text-sm font-bold tracking-wide uppercase">Órdenes de trabajo</h3>

    @forelse ($this->otPorDireccion as $direccionId => $ots)
        <div class="border-line mt-3 rounded-lg border">
            <p class="border-line bg-cream-deep/40 border-b px-4 py-2 text-sm font-semibold text-ink">
                📍 {{ $direccionesPorId[$direccionId]->direccion ?? 'Sin dirección asignada' }}
            </p>

            <div class="divide-line divide-y">
                @foreach ($ots as $ot)
                    <div class="px-4 py-3" wire:key="ot-{{ $ot->id }}">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <div class="min-w-0">
                                <p class="text-ink text-sm font-medium">{{ $ot->titulo }}</p>
                                <p class="text-ink-soft text-xs">
                                    @if ($ot->finalizado_at)
                                        Ejecutada el {{ FechaEsp::largo($ot->finalizado_at) }}
                                    @elseif ($ot->evento)
                                        Agendada · {{ FechaEsp::corto($ot->evento->inicio) }}
                                    @else
                                        Sin agendar
                                    @endif
                                    @if ($ot->cotizacion) · cotización #{{ $ot->cotizacion->folio }} @endif
                                </p>
                            </div>
                            <div class="flex items-center gap-2">
                                @if ($ot->mantencion_vencida)
                                    <span class="rounded-full bg-red-100 px-2 py-0.5 text-xs font-semibold text-red-700">Mantención vencida</span>
                                @endif
                                <span class="rounded-full px-2 py-0.5 text-xs font-semibold {{ $estadoBadge[$ot->estado] ?? '' }}">
                                    {{ str_replace('_', ' ', ucfirst($ot->estado)) }}
                                </span>
                            </div>
                        </div>

                        <div class="mt-2 flex flex-wrap gap-2">
                            <button type="button" wire:click="editarOt({{ $ot->id }})"
                                class="border-line hover:bg-cream-deep rounded-lg border px-2.5 py-1 text-xs font-semibold">
                                Editar estado
                            </button>
                            @if ($ot->evento)
                                <button type="button" wire:click="abrirAgenda({{ $ot->id }})"
                                    class="border-line hover:bg-cream-deep rounded-lg border px-2.5 py-1 text-xs font-semibold">
                                    Reagendar
                                </button>
                                <button type="button" wire:click="desagendar({{ $ot->id }})"
                                    class="text-ink-soft rounded-lg px-2.5 py-1 text-xs hover:bg-red-50 hover:text-red-600">
                                    Quitar del calendario
                                </button>
                            @else
                                <button type="button" wire:click="abrirAgenda({{ $ot->id }})"
                                    class="border-brand-red-ui/40 text-brand-red-ui hover:bg-brand-red-ui/5 rounded-lg border px-2.5 py-1 text-xs font-semibold">
                                    Agendar
                                </button>
                            @endif
                        </div>

                        {{-- Editor de estado inline --}}
                        @if ($editandoOtId === $ot->id)
                            <div class="border-line bg-cream-deep/30 mt-3 grid gap-2 rounded-lg border p-3 sm:grid-cols-[9rem_9rem_7rem_auto]">
                                <label class="text-ink-soft text-xs font-semibold">
                                    Estado
                                    <select wire:model="otEstado" class="border-line mt-1 w-full rounded-lg border px-2 py-1.5 text-sm">
                                        @foreach (\App\Models\Trabajo::ESTADOS as $e)
                                            <option value="{{ $e }}">{{ str_replace('_', ' ', ucfirst($e)) }}</option>
                                        @endforeach
                                    </select>
                                </label>
                                <label class="text-ink-soft text-xs font-semibold">
                                    Fecha de ejecución
                                    <input type="date" wire:model="otFecha" class="border-line mt-1 w-full rounded-lg border px-2 py-1.5 text-sm">
                                </label>
                                <label class="text-ink-soft text-xs font-semibold">
                                    Meses mantención
                                    <input type="number" min="0" max="120" wire:model="otMesesMantencion" class="border-line mt-1 w-full rounded-lg border px-2 py-1.5 text-sm">
                                </label>
                                <div class="flex items-end gap-2">
                                    <button type="button" wire:click="guardarOt"
                                        class="bg-brand-red-ui hover:bg-brand-red-dark rounded-lg px-3 py-1.5 text-xs font-semibold text-white">Guardar</button>
                                    <button type="button" wire:click="cancelarEdicionOt"
                                        class="text-ink-soft px-2 py-1.5 text-xs">Cancelar</button>
                                </div>
                                @error('otFecha') <p class="col-span-full text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                        @endif

                        {{-- Sub-form de agenda --}}
                        @if ($agendandoOtId === $ot->id)
                            <div class="border-line bg-cream-deep/30 mt-3 grid gap-2 rounded-lg border p-3 sm:grid-cols-[10rem_8rem_auto]">
                                <label class="text-ink-soft text-xs font-semibold">
                                    Fecha
                                    <input type="date" wire:model="agendaFecha" class="border-line mt-1 w-full rounded-lg border px-2 py-1.5 text-sm">
                                </label>
                                <label class="text-ink-soft text-xs font-semibold">
                                    Hora
                                    <input type="time" wire:model="agendaHora" class="border-line mt-1 w-full rounded-lg border px-2 py-1.5 text-sm">
                                </label>
                                <div class="flex items-end gap-2">
                                    <button type="button" wire:click="guardarAgenda"
                                        class="bg-brand-red-ui hover:bg-brand-red-dark rounded-lg px-3 py-1.5 text-xs font-semibold text-white">Agendar</button>
                                    <button type="button" wire:click="$set('agendandoOtId', null)"
                                        class="text-ink-soft px-2 py-1.5 text-xs">Cancelar</button>
                                </div>
                                @error('agendaFecha') <p class="col-span-full text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @empty
        <p class="text-ink-soft mt-2 text-sm">Todavía no hay órdenes de trabajo. Se crean al aceptar una cotización.</p>
    @endforelse

    {{-- Cotizaciones relacionadas --}}
    <h3 class="text-ink-soft mt-6 text-sm font-bold tracking-wide uppercase">Cotizaciones relacionadas</h3>
    <div class="divide-line mt-2 divide-y">
        @forelse ($cliente->cotizaciones as $cot)
            <a href="{{ route('admin.cotizaciones', ['seleccionada' => $cot->id]) }}" wire:navigate
                class="hover:bg-cream-deep/60 flex items-center justify-between gap-3 py-2.5 text-sm">
                <span class="text-ink">#{{ $cot->folio }} · {{ $clp($cot->total) }}</span>
                <span class="rounded-full px-2 py-0.5 text-xs font-semibold {{ $cotBadge[$cot->estado] ?? '' }}">{{ ucfirst($cot->estado) }}</span>
            </a>
        @empty
            <p class="text-ink-soft py-2 text-sm">Sin cotizaciones.</p>
        @endforelse
    </div>
</div>
