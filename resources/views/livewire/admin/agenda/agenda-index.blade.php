@php
    use App\Support\FechaEsp;
    $hoyStr = now()->format('Y-m-d');
    $mesActualStr = \Illuminate\Support\Carbon::createFromFormat('Y-m-d', $mes.'-01')->format('Y-m');
@endphp

<div class="flex flex-col gap-3">
    @if ($flash)
        <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-2.5 text-sm font-medium text-green-800">
            {{ $flash }}
        </div>
    @endif

    {{-- Barra de acciones: nuevo evento + opciones (conectar con otro calendario) --}}
    <div class="flex items-center justify-end gap-2">
        <button type="button" wire:click="nuevoEvento"
            class="bg-brand-red-ui hover:bg-brand-red-dark rounded-lg px-4 py-2 text-sm font-semibold text-white">
            + Nuevo evento
        </button>

        <div x-data="{ open: false }" class="relative" x-on:click.outside="open = false">
            <button type="button" x-on:click="open = !open"
                class="border-line hover:bg-cream-deep flex items-center gap-1 rounded-lg border bg-white px-4 py-2 text-sm font-semibold text-ink">
                Opciones
                <svg x-bind:class="open ? 'rotate-180' : ''" class="h-3 w-3 shrink-0 transition-transform" viewBox="0 0 12 12" fill="none">
                    <path d="M2.5 4.5L6 8l3.5-3.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
            </button>

            <div
                x-show="open"
                x-cloak
                x-transition:enter="transition ease-out duration-150"
                x-transition:enter-start="opacity-0 -translate-y-1"
                x-transition:enter-end="opacity-100 translate-y-0"
                x-on:click="open = false"
                class="border-line absolute right-0 top-full z-20 mt-1 w-max min-w-[14rem] rounded-lg border bg-white p-1.5 shadow-lg"
            >
                <button type="button" wire:click="abrirConectarCalendario"
                    class="text-ink-soft hover:bg-cream-deep block w-full whitespace-nowrap rounded-md px-3 py-2 text-left text-sm font-medium">
                    Conectar con otro calendario
                </button>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-[7fr_3fr]">
        {{-- Calendario mensual (70%) --}}
        <div class="border-line rounded-lg border bg-white px-4 py-3">
            <div class="flex items-center justify-between">
                <button type="button" wire:click="mesAnterior"
                    class="border-line hover:bg-cream-deep rounded-lg border px-3 py-1.5 text-sm">‹</button>

                <div class="flex items-center gap-3">
                    <h2 class="text-ink text-sm font-bold tracking-tight">{{ $this->tituloMes() }}</h2>
                    @if ($mesActualStr !== now()->format('Y-m'))
                        <button type="button" wire:click="irAHoy"
                            class="text-brand-red-ui text-xs font-semibold hover:underline">Hoy</button>
                    @endif
                </div>

                <button type="button" wire:click="mesSiguiente"
                    class="border-line hover:bg-cream-deep rounded-lg border px-3 py-1.5 text-sm">›</button>
            </div>

            <div class="mt-3 grid grid-cols-7 gap-1 text-center">
                @foreach (FechaEsp::DIAS_CORTOS as $dia)
                    <div class="text-ink-soft py-2 text-xs font-semibold uppercase">{{ $dia }}</div>
                @endforeach

                @foreach ($this->semanas as $semana)
                    @foreach ($semana as $dia)
                        @php
                            $claveDia = $dia->format('Y-m-d');
                            $eventos = $this->eventosDelMes->get($claveDia, collect());
                            $esDeEsteMes = $dia->format('Y-m') === $mesActualStr;
                            $esHoy = $claveDia === $hoyStr;
                        @endphp
                        <button
                            type="button"
                            wire:click="nuevoEvento('{{ $claveDia }}')"
                            wire:key="dia-{{ $claveDia }}"
                            class="min-h-[5.5rem] rounded-lg border p-1.5 text-left transition-colors
                                {{ $esHoy ? 'border-brand-red-ui bg-brand-red-ui/5' : 'border-line' }}
                                {{ $esDeEsteMes ? 'hover:bg-cream-deep/60' : 'bg-cream-deep/30 text-ink-soft/50' }}"
                        >
                            <span class="block text-xs font-semibold {{ $esHoy ? 'text-brand-red-ui' : '' }}">
                                {{ $dia->day }}
                            </span>
                            <span class="mt-1 flex flex-col gap-0.5">
                                @foreach ($eventos->take(3) as $ev)
                                    <span
                                        wire:click.stop="editarEvento({{ $ev->id }})"
                                        class="truncate rounded px-1 py-0.5 text-[10px] font-medium
                                            {{ $ev->estado === 'cancelado' ? 'bg-gray-100 text-gray-400 line-through' : ($ev->tipo === 'terreno' ? 'bg-brand-red-ui/10 text-brand-red-dark' : 'bg-ink/10 text-ink') }}"
                                        title="{{ $ev->titulo }}"
                                    >
                                        @if (! $ev->todo_el_dia){{ $ev->inicio->format('H:i') }} @endif{{ $ev->titulo }}
                                    </span>
                                @endforeach
                                @if ($eventos->count() > 3)
                                    <span class="text-ink-soft text-[10px]">+{{ $eventos->count() - 3 }} más</span>
                                @endif
                            </span>
                        </button>
                    @endforeach
                @endforeach
            </div>
        </div>

        {{-- Panel derecho (30%): pendientes por agendar + agenda del mes --}}
        <div class="flex flex-col gap-3">
            <div class="border-line rounded-lg border bg-white px-4 py-3">
                <div class="flex items-center gap-2">
                    <span class="bg-brand-red-ui h-2 w-2 rounded-full"></span>
                    <h2 class="text-ink text-sm font-bold tracking-tight">Por agendar</h2>
                    <span class="text-ink-soft text-xs">({{ $this->pendientesPorAgendar->count() }})</span>
                </div>
                <div class="divide-line mt-1 divide-y">
                    @forelse ($this->pendientesPorAgendar as $t)
                        <div class="flex items-center justify-between gap-2 py-2 text-sm" wire:key="pendiente-{{ $t->id }}">
                            <span class="min-w-0">
                                <span class="text-ink block truncate font-medium">{{ $t->titulo }}</span>
                                <span class="text-ink-soft block truncate text-xs">
                                    {{ $t->cliente?->nombre ?? '—' }}
                                    @if ($t->clienteDireccion) · {{ $t->clienteDireccion->etiquetaCompleta() }} @endif
                                </span>
                            </span>
                            <button type="button" wire:click="abrirAgendarTrabajo({{ $t->id }})"
                                class="border-brand-red-ui/40 text-brand-red-ui hover:bg-brand-red-ui/5 shrink-0 rounded-lg border px-2.5 py-1 text-xs font-semibold">
                                Agendar
                            </button>
                        </div>
                    @empty
                        <p class="text-ink-soft py-4 text-center text-sm">Nada pendiente por agendar.</p>
                    @endforelse
                </div>
            </div>

            <div class="border-line rounded-lg border bg-white px-4 py-3">
                <h2 class="text-ink text-sm font-bold tracking-tight">Agenda del mes</h2>
                <div class="divide-line mt-1 divide-y">
                    @forelse ($this->agendaMes as $ev)
                        <button type="button" wire:click="editarEvento({{ $ev->id }})"
                            class="hover:bg-cream-deep/60 flex w-full items-center justify-between gap-3 py-2 text-left text-sm">
                            <span class="min-w-0">
                                <span class="text-ink block truncate">
                                    {{ $ev->titulo }}
                                    @if ($ev->cliente)<span class="text-ink-soft">· {{ $ev->cliente->nombre }}</span>@endif
                                </span>
                            </span>
                            <span class="text-ink-soft shrink-0 text-xs">{{ FechaEsp::diaMes($ev->inicio) }}</span>
                        </button>
                    @empty
                        <p class="text-ink-soft py-4 text-center text-sm">Sin eventos este mes.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    {{-- Modal: nuevo/editar evento --}}
    @if ($mostrandoForm)
        <div
            x-data
            x-on:keydown.escape.window="$wire.cerrarForm()"
            class="fixed inset-0 z-50 flex items-center justify-center p-4"
        >
            <div x-show="true" x-transition.opacity wire:click="cerrarForm" class="absolute inset-0 bg-ink/50 backdrop-blur-sm"></div>

            <div
                x-show="true"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                class="border-line relative flex max-h-[90vh] w-full max-w-md flex-col overflow-hidden rounded-xl border bg-white shadow-2xl"
            >
                <div class="flex items-center justify-between px-5 py-4">
                    <h3 class="text-ink text-sm font-bold tracking-tight">
                        {{ $editandoId ? 'Editar evento' : 'Nuevo evento' }}
                    </h3>
                    <button type="button" wire:click="cerrarForm" class="text-ink-soft text-sm hover:text-ink">✕</button>
                </div>

                <form wire:submit="guardarEvento" class="flex-1 overflow-y-auto px-5 pb-5">
                    <div class="flex flex-col gap-3">
                        <div>
                            <input type="text" wire:model="titulo" placeholder="Título"
                                class="border-line focus:border-brand-red-ui focus:ring-brand-red-ui/20 w-full rounded-lg border px-3 py-2 text-sm focus:ring">
                            @error('titulo') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <textarea wire:model="descripcion" rows="2" placeholder="Descripción / objetivo"
                            class="border-line focus:border-brand-red-ui focus:ring-brand-red-ui/20 w-full rounded-lg border px-3 py-2 text-sm focus:ring"></textarea>

                        <div class="grid grid-cols-2 gap-2">
                            <label class="text-ink-soft text-xs font-semibold">
                                Tipo
                                <select wire:model="tipo" class="border-line mt-1 w-full rounded-lg border px-2 py-2 text-sm">
                                    <option value="terreno">Salida a terreno</option>
                                    <option value="oficina">Oficina / gestión</option>
                                </select>
                            </label>
                            <label class="text-ink-soft text-xs font-semibold">
                                Estado
                                <select wire:model="estado" class="border-line mt-1 w-full rounded-lg border px-2 py-2 text-sm">
                                    <option value="agendado">Agendado</option>
                                    <option value="hecho">Hecho</option>
                                    <option value="cancelado">Cancelado</option>
                                </select>
                            </label>
                        </div>

                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <input type="date" wire:model="fecha"
                                    class="border-line focus:border-brand-red-ui w-full rounded-lg border px-3 py-2 text-sm">
                                @error('fecha') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <input type="time" wire:model="hora" @disabled($todo_el_dia)
                                    class="border-line focus:border-brand-red-ui w-full rounded-lg border px-3 py-2 text-sm disabled:bg-gray-100">
                                @error('hora') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <label class="text-ink-soft flex items-center gap-2 text-sm">
                            <input type="checkbox" wire:model="todo_el_dia" class="rounded">
                            Todo el día
                        </label>

                        <label class="text-ink-soft text-xs font-semibold">
                            Cliente (opcional)
                            <select wire:model="cliente_id" class="border-line mt-1 w-full rounded-lg border px-2 py-2 text-sm">
                                <option value="">— Sin cliente —</option>
                                @foreach ($this->clientes as $cli)
                                    <option value="{{ $cli->id }}">{{ $cli->nombre }}</option>
                                @endforeach
                            </select>
                        </label>

                        <input type="text" wire:model="ubicacion" placeholder="Ubicación / dirección"
                            class="border-line focus:border-brand-red-ui w-full rounded-lg border px-3 py-2 text-sm">

                        <textarea wire:model="notas" rows="2" placeholder="Notas"
                            class="border-line focus:border-brand-red-ui w-full rounded-lg border px-3 py-2 text-sm"></textarea>

                        <div class="mt-1 flex items-center justify-between">
                            <button type="submit"
                                class="bg-brand-red-ui hover:bg-brand-red-dark rounded-lg px-4 py-2 text-sm font-semibold text-white">
                                Guardar
                            </button>
                            @if ($editandoId)
                                <button type="button" wire:click="eliminarEvento" wire:confirm="¿Eliminar este evento?"
                                    class="text-ink-soft rounded-lg px-3 py-2 text-sm hover:bg-red-50 hover:text-red-600">
                                    Eliminar
                                </button>
                            @endif
                        </div>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- Modal: agendar una OT pendiente --}}
    @if ($agendandoTrabajoId)
        <div
            x-data
            x-on:keydown.escape.window="$wire.cerrarAgendarTrabajo()"
            class="fixed inset-0 z-50 flex items-center justify-center p-4"
        >
            <div x-show="true" x-transition.opacity wire:click="cerrarAgendarTrabajo" class="absolute inset-0 bg-ink/50 backdrop-blur-sm"></div>

            <div
                x-show="true"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                class="border-line relative flex w-full max-w-sm flex-col overflow-hidden rounded-xl border bg-white shadow-2xl"
            >
                <div class="flex items-center justify-between px-5 py-4">
                    <h3 class="text-ink text-sm font-bold tracking-tight">Agendar trabajo</h3>
                    <button type="button" wire:click="cerrarAgendarTrabajo" class="text-ink-soft text-sm hover:text-ink">✕</button>
                </div>

                <div class="flex flex-col gap-3 px-5 pb-5">
                    <div>
                        <label class="text-ink-soft text-xs font-semibold">Fecha</label>
                        <input type="date" wire:model="agendaFecha"
                            class="border-line focus:border-brand-red-ui mt-1 w-full rounded-lg border px-3 py-2 text-sm">
                        @error('agendaFecha') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="text-ink-soft text-xs font-semibold">Hora (opcional, vacío = todo el día)</label>
                        <input type="time" wire:model="agendaHora"
                            class="border-line focus:border-brand-red-ui mt-1 w-full rounded-lg border px-3 py-2 text-sm">
                        @error('agendaHora') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <button type="button" wire:click="confirmarAgendarTrabajo"
                        class="bg-brand-red-ui hover:bg-brand-red-dark mt-1 rounded-lg px-4 py-2 text-sm font-semibold text-white">
                        Confirmar agenda
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Modal: conectar con otro calendario --}}
    @if ($mostrandoConectarCalendario)
        <div
            x-data
            x-on:keydown.escape.window="$wire.cerrarConectarCalendario()"
            class="fixed inset-0 z-50 flex items-center justify-center p-4"
        >
            <div x-show="true" x-transition.opacity wire:click="cerrarConectarCalendario" class="absolute inset-0 bg-ink/50 backdrop-blur-sm"></div>

            <div
                x-show="true"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                class="border-line relative flex w-full max-w-sm flex-col overflow-hidden rounded-xl border bg-white shadow-2xl"
            >
                <div class="flex items-center justify-between px-5 py-4">
                    <h3 class="text-ink text-sm font-bold tracking-tight">Conectar con otro calendario</h3>
                    <button type="button" wire:click="cerrarConectarCalendario" class="text-ink-soft text-sm hover:text-ink">✕</button>
                </div>

                <div class="flex flex-col gap-3 px-5 pb-5">
                    <p class="text-ink-soft text-sm">Sincroniza la agenda con un proveedor externo.</p>

                    <button
                        type="button"
                        disabled
                        title="Disponible en una próxima entrega"
                        class="border-line flex cursor-not-allowed items-center justify-center gap-2 rounded-lg border bg-gray-100 px-4 py-2.5 text-sm font-semibold text-gray-400"
                    >
                        <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24">
                            <path fill="currentColor" d="M12 11v2.4h6.7c-.3 1.6-2.1 4.7-6.7 4.7-4 0-7.3-3.3-7.3-7.4S8 3.3 12 3.3c2.3 0 3.8.9 4.7 1.8l2.5-2.4C17.6 1.1 15 0 12 0 5.4 0 0 5.4 0 12s5.4 12 12 12c6.9 0 11.5-4.9 11.5-11.7 0-.8-.1-1.4-.2-2H12z"/>
                        </svg>
                        Google Calendar · próximamente
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
