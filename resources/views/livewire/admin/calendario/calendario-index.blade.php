@php
    use App\Support\FechaEsp;
    $hoyStr = now()->format('Y-m-d');
    $mesActualStr = \Illuminate\Support\Carbon::createFromFormat('Y-m-d', $mes.'-01')->format('Y-m');
@endphp

<div class="flex flex-col gap-6">
    @if ($flash)
        <div class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-800">
            {{ $flash }}
        </div>
    @endif

    {{-- Barra Google Calendar (deshabilitada — sprint posterior) --}}
    <div class="border-line flex flex-wrap items-center justify-between gap-3 rounded-xl border bg-white px-5 py-4">
        <p class="text-ink-soft text-sm">No conectado a Google Calendar</p>
        <button
            type="button"
            disabled
            class="cursor-not-allowed rounded-lg bg-gray-200 px-4 py-2 text-sm font-semibold text-gray-500"
            title="Disponible en una próxima entrega"
        >
            Conectar con Google Calendar · próximamente
        </button>
    </div>

    <div class="grid gap-6 lg:grid-cols-[1fr_22rem]">
        {{-- Grilla mensual --}}
        <div class="border-line rounded-2xl border bg-white p-5">
            <div class="flex items-center justify-between">
                <button type="button" wire:click="mesAnterior"
                    class="border-line hover:bg-cream-deep rounded-lg border px-3 py-1.5 text-sm">‹</button>

                <div class="flex items-center gap-3">
                    <h2 class="text-ink text-base font-bold tracking-tight">{{ $this->tituloMes() }}</h2>
                    @if ($mesActualStr !== now()->format('Y-m'))
                        <button type="button" wire:click="irAHoy"
                            class="text-brand-red-ui text-xs font-semibold hover:underline">Hoy</button>
                    @endif
                </div>

                <button type="button" wire:click="mesSiguiente"
                    class="border-line hover:bg-cream-deep rounded-lg border px-3 py-1.5 text-sm">›</button>
            </div>

            <div class="mt-4 grid grid-cols-7 gap-1 text-center">
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

        {{-- Agendas + formulario --}}
        <div class="flex flex-col gap-5">
            @if ($mostrandoForm)
                <div class="border-line rounded-2xl border bg-white p-5">
                    <div class="flex items-center justify-between">
                        <h3 class="text-ink text-sm font-bold tracking-tight">
                            {{ $editandoId ? 'Editar evento' : 'Nuevo evento' }}
                        </h3>
                        <button type="button" wire:click="cerrarForm" class="text-ink-soft text-sm hover:text-ink">✕</button>
                    </div>

                    <form wire:submit="guardarEvento" class="mt-4 flex flex-col gap-3">
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
                    </form>
                </div>
            @else
                <button type="button" wire:click="nuevoEvento"
                    class="bg-brand-red-ui hover:bg-brand-red-dark self-start rounded-lg px-4 py-2 text-sm font-semibold text-white">
                    + Nuevo evento
                </button>
            @endif

            <div class="border-line rounded-2xl border bg-white p-5">
                <h3 class="text-ink text-sm font-bold tracking-tight">Agenda semanal</h3>
                <div class="divide-line mt-3 divide-y">
                    @forelse ($this->agendaSemana as $ev)
                        <button type="button" wire:click="editarEvento({{ $ev->id }})"
                            class="hover:bg-cream-deep/60 flex w-full items-center justify-between gap-3 py-2.5 text-left text-sm">
                            <span class="text-ink">
                                {{ $ev->titulo }}
                                @if ($ev->cliente)<span class="text-ink-soft">· {{ $ev->cliente->nombre }}</span>@endif
                            </span>
                            <span class="text-ink-soft shrink-0 text-xs">{{ FechaEsp::corto($ev->inicio) }}</span>
                        </button>
                    @empty
                        <p class="text-ink-soft py-3 text-sm">Sin eventos esta semana.</p>
                    @endforelse
                </div>
            </div>

            <div class="border-line rounded-2xl border bg-white p-5">
                <h3 class="text-ink text-sm font-bold tracking-tight">Agenda del mes</h3>
                <div class="divide-line mt-3 divide-y">
                    @forelse ($this->agendaMes as $ev)
                        <button type="button" wire:click="editarEvento({{ $ev->id }})"
                            class="hover:bg-cream-deep/60 flex w-full items-center justify-between gap-3 py-2.5 text-left text-sm">
                            <span class="text-ink">
                                {{ $ev->titulo }}
                                @if ($ev->cliente)<span class="text-ink-soft">· {{ $ev->cliente->nombre }}</span>@endif
                            </span>
                            <span class="text-ink-soft shrink-0 text-xs">{{ FechaEsp::diaMes($ev->inicio) }}</span>
                        </button>
                    @empty
                        <p class="text-ink-soft py-3 text-sm">Sin eventos este mes.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
