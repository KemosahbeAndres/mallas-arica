<div class="grid gap-4 lg:grid-cols-[20rem_1fr]">
    {{-- Lista maestra --}}
    <div class="flex flex-col gap-3">
        <button
            type="button"
            wire:click="nuevo"
            class="bg-brand-red-ui hover:bg-brand-red-dark self-start rounded-lg px-4 py-2 text-sm font-semibold text-white transition-colors"
        >
            + Nuevo cliente
        </button>

        <input
            type="search"
            wire:model.live.debounce.300ms="buscar"
            placeholder="Buscar por nombre, teléfono o correo…"
            class="border-line focus:border-brand-red-ui focus:ring-brand-red-ui/20 w-full rounded-lg border px-3 py-2 text-sm focus:ring"
        >

        <div class="border-line divide-line divide-y overflow-hidden rounded-lg border bg-white">
            @forelse ($this->clientes as $cliente)
                <button
                    type="button"
                    wire:key="cliente-{{ $cliente->id }}"
                    wire:click="seleccionar({{ $cliente->id }})"
                    class="flex w-full items-start justify-between gap-3 px-4 py-2.5 text-left transition-colors {{ $seleccionado === $cliente->id ? 'bg-cream-deep' : 'hover:bg-cream-deep/60' }}"
                >
                    <span>
                        <span class="text-ink block text-sm font-semibold">{{ $cliente->nombre }}</span>
                        <span class="text-ink-soft block text-xs">{{ $cliente->telefono ?: 'Sin teléfono' }}</span>
                    </span>
                </button>
            @empty
                <p class="text-ink-soft px-4 py-6 text-center text-sm">
                    {{ $buscar !== '' ? 'Sin resultados.' : 'Todavía no hay clientes.' }}
                </p>
            @endforelse
        </div>
    </div>

    {{-- Detalle / formulario --}}
    <div class="border-line rounded-lg border bg-white px-4 py-4">
        @if ($guardado)
            <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-2.5 text-sm font-medium text-green-800">
                {{ $guardado }}
            </div>
        @endif

        <form wire:submit="guardar" class="flex flex-col gap-4">
            <div class="grid gap-4 sm:grid-cols-3">
                <div class="sm:col-span-1">
                    <label class="text-ink-soft block text-sm font-semibold" for="cli-nombre">Nombre</label>
                    <input id="cli-nombre" type="text" wire:model="nombre"
                        class="border-line focus:border-brand-red-ui focus:ring-brand-red-ui/20 mt-1.5 w-full rounded-lg border px-3 py-2 text-sm focus:ring">
                    @error('nombre') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="text-ink-soft block text-sm font-semibold" for="cli-telefono">Teléfono</label>
                    <input id="cli-telefono" type="text" wire:model="telefono"
                        class="border-line focus:border-brand-red-ui focus:ring-brand-red-ui/20 mt-1.5 w-full rounded-lg border px-3 py-2 text-sm focus:ring">
                    @error('telefono') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="text-ink-soft block text-sm font-semibold" for="cli-email">Correo</label>
                    <input id="cli-email" type="email" wire:model="email"
                        class="border-line focus:border-brand-red-ui focus:ring-brand-red-ui/20 mt-1.5 w-full rounded-lg border px-3 py-2 text-sm focus:ring">
                    @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label class="text-ink-soft block text-sm font-semibold" for="cli-notas">Notas</label>
                <textarea id="cli-notas" rows="2" wire:model="notas"
                    class="border-line focus:border-brand-red-ui focus:ring-brand-red-ui/20 mt-1.5 w-full rounded-lg border px-3 py-2 text-sm focus:ring"></textarea>
                @error('notas') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="border-line border-t pt-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-ink-soft text-sm font-bold tracking-wide uppercase">Direcciones</h3>
                    <button type="button" wire:click="agregarDireccion"
                        class="border-brand-red-ui/40 text-brand-red-ui hover:bg-brand-red-ui/5 rounded-lg border px-3 py-1.5 text-xs font-semibold transition-colors">
                        + Agregar dirección
                    </button>
                </div>

                <div class="mt-3 flex flex-col gap-2">
                    @forelse ($direcciones as $indice => $dir)
                        <div class="border-line bg-cream-deep/40 flex items-start gap-2 rounded-lg border p-3" wire:key="dir-{{ $indice }}">
                            <div class="flex-1 grid gap-2 sm:grid-cols-[1fr_10rem]">
                                <div>
                                    <input type="text" wire:model="direcciones.{{ $indice }}.direccion" placeholder="Dirección"
                                        class="border-line focus:border-brand-red-ui focus:ring-brand-red-ui/20 w-full rounded-lg border px-3 py-2 text-sm focus:ring">
                                    @error("direcciones.{$indice}.direccion") <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>
                                <input type="text" wire:model="direcciones.{{ $indice }}.etiqueta" placeholder="Etiqueta (opcional)"
                                    class="border-line focus:border-brand-red-ui focus:ring-brand-red-ui/20 w-full rounded-lg border px-3 py-2 text-sm focus:ring">
                            </div>
                            <button type="button" wire:click="quitarDireccion({{ $indice }})"
                                class="text-ink-soft shrink-0 rounded-lg p-2 text-sm hover:bg-red-50 hover:text-red-600" title="Quitar">
                                ✕
                            </button>
                        </div>
                    @empty
                        <p class="text-ink-soft text-sm">Sin direcciones registradas.</p>
                    @endforelse
                </div>
            </div>

            <div class="flex items-center justify-between">
                <button type="submit"
                    class="bg-brand-red-ui hover:bg-brand-red-dark rounded-lg px-5 py-2.5 text-sm font-semibold text-white transition-colors"
                    wire:loading.attr="disabled" wire:target="guardar">
                    <span wire:loading.remove wire:target="guardar">Guardar cliente</span>
                    <span wire:loading wire:target="guardar">Guardando…</span>
                </button>

                @if ($seleccionado)
                    <button type="button" wire:click="eliminar" wire:confirm="¿Eliminar este cliente? Sus direcciones también se quitan."
                        class="text-ink-soft rounded-lg px-3 py-2 text-sm hover:bg-red-50 hover:text-red-600">
                        Eliminar cliente
                    </button>
                @endif
            </div>
        </form>
    </div>

    @if ($seleccionado)
        <div class="lg:col-start-2">
            <livewire:admin.clientes.cliente-historial :cliente-id="$seleccionado" :key="'historial-'.$seleccionado" />
        </div>
    @endif
</div>
