@php $clp = fn ($v) => '$'.number_format((int) $v, 0, ',', '.'); @endphp

<form wire:submit="guardar" class="flex flex-col gap-4">
    {{-- Barra de acciones: estado + guardar/cancelar --}}
    <div class="flex items-center justify-between">
        <label class="text-ink-soft text-sm font-semibold">
            Estado
            <select wire:model="estado" class="border-line ml-2 rounded-lg border px-3 py-2 text-sm">
                @foreach (\App\Models\Cotizacion::ESTADOS as $e)
                    <option value="{{ $e }}">{{ ucfirst($e) }}</option>
                @endforeach
            </select>
        </label>

        <div class="flex gap-2">
            <a href="{{ route('admin.cotizaciones') }}" wire:navigate
                class="border-line hover:bg-cream-deep rounded-lg border px-4 py-2 text-sm font-semibold">Cancelar</a>
            <button type="submit"
                class="bg-brand-red-ui hover:bg-brand-red-dark rounded-lg px-5 py-2 text-sm font-semibold text-white">
                Guardar cotización
            </button>
        </div>
    </div>

    {{-- Cliente (40%) + Cotización (60%), cada una con su propio scroll --}}
    <div class="flex flex-col gap-6 md:flex-row md:items-start">
        {{-- Cliente --}}
        <section class="border-line flex max-h-[calc(100vh-16rem)] w-full flex-col rounded-2xl border bg-white md:w-[30%]">
            <div class="shrink-0 p-6 pb-0">
                <h2 class="text-ink text-base font-bold tracking-tight">Cliente</h2>

                <div class="mt-4 flex gap-2">
                    <button type="button" wire:click="$set('modoCliente', 'existente')"
                        class="rounded-lg px-4 py-2 text-sm font-semibold transition-colors {{ $modoCliente === 'existente' ? 'bg-brand-red-ui text-white' : 'border-line bg-white text-ink-soft border' }}">
                        Cliente existente
                    </button>
                    <button type="button" wire:click="$set('modoCliente', 'nuevo')"
                        class="rounded-lg px-4 py-2 text-sm font-semibold transition-colors {{ $modoCliente === 'nuevo' ? 'bg-brand-red-ui text-white' : 'border-line bg-white text-ink-soft border' }}">
                        Cliente nuevo
                    </button>
                </div>
            </div>

            <div class="min-h-0 flex-1 overflow-y-auto p-6 pt-4">
                @if ($modoCliente === 'existente')
                    <input type="search" wire:model.live.debounce.300ms="buscarCliente" placeholder="Buscar por nombre o teléfono…"
                        class="border-line focus:border-brand-red-ui w-full rounded-lg border px-3 py-2 text-sm">
                    @error('clienteId') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror

                    <div class="mt-3 flex flex-col gap-2">
                        @foreach ($this->clientesFiltrados as $c)
                            <button type="button" wire:click="$set('clienteId', {{ $c->id }})"
                                class="flex w-full items-center justify-between rounded-lg border px-3 py-2.5 text-left transition-colors {{ $clienteId === $c->id ? 'bg-brand-red-ui border-brand-red-ui text-white' : 'border-line bg-white text-ink hover:bg-cream-deep' }}">
                                <span class="min-w-0">
                                    <span class="block truncate text-sm font-semibold">{{ $c->nombre }}</span>
                                    <span class="block text-xs {{ $clienteId === $c->id ? 'text-white/80' : 'text-ink-soft' }}">
                                        {{ $c->trabajos_count }} {{ $c->trabajos_count === 1 ? 'trabajo' : 'trabajos' }}
                                        @if ($c->telefono)
                                            · {{ $c->telefono }}
                                        @endif
                                    </span>
                                </span>
                            </button>
                        @endforeach
                        @if ($this->clientesFiltrados->isEmpty())
                            <p class="text-ink-soft text-sm">Sin resultados. Usa "Cliente nuevo".</p>
                        @endif
                    </div>

                    @if ($clienteId)
                        <div class="mt-4">
                            <label class="text-ink-soft text-xs font-semibold tracking-wide uppercase">Dirección del trabajo</label>
                            <select wire:model="clienteDireccionId" class="border-line mt-1 w-full rounded-lg border px-3 py-2 text-sm">
                                <option value="">— Escribir una nueva abajo —</option>
                                @foreach ($this->direccionesDelCliente as $dir)
                                    <option value="{{ $dir->id }}">{{ $dir->direccion }}</option>
                                @endforeach
                            </select>
                            <input type="text" wire:model="direccionLibre" placeholder="O una dirección nueva para este trabajo"
                                class="border-line focus:border-brand-red-ui mt-2 w-full rounded-lg border px-3 py-2 text-sm">
                        </div>
                    @endif
                @else
                    <div class="grid gap-3">
                        <div>
                            <input type="text" wire:model="nuevoNombre" placeholder="Nombre"
                                class="border-line focus:border-brand-red-ui w-full rounded-lg border px-3 py-2 text-sm">
                            @error('nuevoNombre') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <input type="text" wire:model="nuevoTelefono" placeholder="Teléfono"
                            class="border-line focus:border-brand-red-ui w-full rounded-lg border px-3 py-2 text-sm">
                        <div>
                            <input type="email" wire:model="nuevoEmail" placeholder="Correo (opcional)"
                                class="border-line focus:border-brand-red-ui w-full rounded-lg border px-3 py-2 text-sm">
                            @error('nuevoEmail') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <input type="text" wire:model="direccionLibre" placeholder="Dirección del trabajo"
                        class="border-line focus:border-brand-red-ui mt-3 w-full rounded-lg border px-3 py-2 text-sm">
                @endif
            </div>
        </section>

        {{-- Ítems --}}
        <section class="border-line flex max-h-[calc(100vh-16rem)] w-full flex-col rounded-2xl border bg-white md:flex-1">
            <div class="flex shrink-0 items-center justify-between p-6 pb-0">
                <h2 class="text-ink text-base font-bold tracking-tight">Ítems de la cotización</h2>
                <button type="button" wire:click="agregarItem"
                    class="border-brand-red-ui/40 text-brand-red-ui hover:bg-brand-red-ui/5 rounded-lg border px-3 py-1.5 text-xs font-semibold">
                    + Agregar línea
                </button>
            </div>

            <div class="min-h-0 flex-1 overflow-y-auto p-6 pt-4">
                <div class="flex flex-col gap-2">
                    <div class="text-ink-soft hidden grid-cols-[1fr_7rem_5rem_5rem_7rem_2rem] gap-2 px-1 text-xs font-semibold uppercase sm:grid">
                        <span>Descripción</span><span class="text-right">P. unitario</span><span class="text-right">Cant.</span>
                        <span class="text-right">Desc. %</span><span class="text-right">Subtotal</span><span></span>
                    </div>

                    @foreach ($items as $i => $item)
                        <div class="grid grid-cols-2 items-center gap-2 sm:grid-cols-[1fr_7rem_5rem_5rem_7rem_2rem]" wire:key="item-{{ $i }}">
                            <div class="col-span-2 sm:col-span-1">
                                <input type="text" wire:model.live.debounce.400ms="items.{{ $i }}.descripcion" placeholder="Descripción del ítem"
                                    class="border-line focus:border-brand-red-ui w-full rounded-lg border px-3 py-2 text-sm">
                                @error("items.{$i}.descripcion") <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <input type="number" min="0" step="1" wire:model.live.debounce.400ms="items.{{ $i }}.precio_unitario"
                                class="border-line focus:border-brand-red-ui w-full rounded-lg border px-2 py-2 text-right text-sm">
                            <input type="number" min="1" step="1" wire:model.live.debounce.400ms="items.{{ $i }}.cantidad"
                                class="border-line focus:border-brand-red-ui w-full rounded-lg border px-2 py-2 text-right text-sm">
                            <input type="number" min="0" max="100" step="1" wire:model.live.debounce.400ms="items.{{ $i }}.descuento_pct"
                                class="border-line focus:border-brand-red-ui w-full rounded-lg border px-2 py-2 text-right text-sm">
                            <span class="text-ink text-right text-sm font-semibold">{{ $clp($this->subtotalLinea($item)) }}</span>
                            <button type="button" wire:click="quitarItem({{ $i }})"
                                class="text-ink-soft rounded-lg p-1.5 text-sm hover:bg-red-50 hover:text-red-600">✕</button>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="border-line shrink-0 border-t p-6 pt-4">
                <div class="flex flex-col items-end gap-1 text-sm">
                    <div class="flex w-full max-w-xs items-center justify-between">
                        <span class="text-ink-soft">Neto (líneas)</span>
                        <span class="text-ink">{{ $clp(array_sum(array_map(fn ($it) => $this->subtotalLinea($it), $items))) }}</span>
                    </div>
                    <div class="flex w-full max-w-xs items-center justify-between">
                        <span class="text-ink-soft">Descuento sobre neto (%)</span>
                        <input type="number" min="0" max="100" step="1" wire:model.live.debounce.400ms="descuentoPct"
                            class="border-line focus:border-brand-red-ui w-20 rounded-lg border px-2 py-1 text-right text-sm">
                    </div>
                    <div class="flex w-full max-w-xs items-center justify-between">
                        <span class="text-ink-soft">Neto con descuento</span>
                        <span class="text-ink">{{ $clp($this->neto) }}</span>
                    </div>
                    <div class="flex w-full max-w-xs items-center justify-between">
                        <span class="text-ink-soft">IVA (19%)</span>
                        <span class="text-ink">{{ $clp($this->iva) }}</span>
                    </div>
                    <div class="border-line flex w-full max-w-xs items-center justify-between border-t pt-2">
                        <span class="text-ink font-bold">Total</span>
                        <span class="text-brand-red-ui text-lg font-bold">{{ $clp($this->total) }}</span>
                    </div>
                </div>
            </div>
        </section>
    </div>
</form>
