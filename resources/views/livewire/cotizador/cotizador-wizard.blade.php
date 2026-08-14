<section id="cotizador" class="scroll-mt-24 bg-cream">
    <div class="mx-auto max-w-7xl px-6 py-20 lg:px-8">
        <div class="max-w-2xl">
            <p class="text-brand-red-ui text-sm font-bold tracking-wide uppercase">Cotizador</p>
            <h2 class="text-ink mt-3 text-3xl font-extrabold tracking-[-0.02em] sm:text-4xl">
                Calcula tu instalación
            </h2>
            <p class="text-ink-soft mt-4 text-lg">
                Completa el formulario con tus espacios y te mostramos un rango de precio referencial al instante.
            </p>
        </div>

        @if ($numeroGenerado)
            <div class="border-brand-red-ui/30 bg-brand-red-ui/5 mt-10 rounded-2xl border p-6">
                <p class="text-ink font-semibold">
                    ¡Listo! Tu cotización quedó guardada con el N°
                    <span class="text-brand-red-ui">{{ $numeroGenerado }}</span>.
                </p>
                <p class="text-ink-soft mt-1 text-sm">
                    Deberías haber sido redirigido a WhatsApp. Si no ocurrió,
                    <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener" class="text-brand-red-ui font-semibold underline">
                        abre la conversación aquí
                    </a>.
                </p>
            </div>
        @endif

        <div class="mt-10 grid grid-cols-1 gap-8 lg:grid-cols-[1fr_400px] lg:items-start lg:gap-12">
            <div class="space-y-6">
                <div class="space-y-4">
                    @foreach ($items as $index => $item)
                        <x-cotizador.item-espacio
                            :item="$item"
                            :index="$index"
                            :tiposEspacio="$this->tiposEspacio"
                            :tramosAltura="$this->tramosAltura"
                            :tiposMalla="$this->tiposMalla"
                            :puedeQuitar="count($items) > 1"
                        />
                    @endforeach
                </div>

                <button
                    type="button"
                    wire:click="agregarItem"
                    class="border-line text-ink-soft hover:border-brand-red-ui hover:text-brand-red-ui w-full rounded-2xl border border-dashed px-4 py-3 text-sm font-semibold transition-colors"
                >
                    + Agregar otro espacio
                </button>

                <div class="border-line rounded-2xl border bg-white p-6">
                    <h3 class="text-ink font-bold">Tus datos de contacto</h3>
                    <p class="text-ink-soft mt-1 text-sm">Los usamos solo para coordinar tu visita y enviarte la cotización.</p>

                    <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="text-ink-soft mb-1.5 block text-xs font-semibold uppercase" for="nombre">Nombre</label>
                            <input
                                id="nombre"
                                type="text"
                                wire:model.blur="nombre"
                                placeholder="Tu nombre"
                                class="border-line text-ink w-full rounded-xl border bg-white px-4 py-2.5 text-sm focus:border-brand-red-ui focus:ring-brand-red-ui/20 focus:ring-4 focus:outline-none"
                            >
                            @error('nombre') <p class="text-brand-red-ui mt-1 text-xs">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="text-ink-soft mb-1.5 block text-xs font-semibold uppercase" for="telefono">Teléfono</label>
                            <input
                                id="telefono"
                                type="tel"
                                wire:model.blur="telefono"
                                placeholder="+56 9 1234 5678"
                                class="border-line text-ink w-full rounded-xl border bg-white px-4 py-2.5 text-sm focus:border-brand-red-ui focus:ring-brand-red-ui/20 focus:ring-4 focus:outline-none"
                            >
                            @error('telefono') <p class="text-brand-red-ui mt-1 text-xs">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="text-ink-soft mb-1.5 block text-xs font-semibold uppercase" for="direccion">Condominio/Dirección (opcional)</label>
                            <input
                                id="direccion"
                                type="text"
                                wire:model.blur="direccion"
                                placeholder="Ej: Condominio Las Torres, depto 302"
                                class="border-line text-ink w-full rounded-xl border bg-white px-4 py-2.5 text-sm focus:border-brand-red-ui focus:ring-brand-red-ui/20 focus:ring-4 focus:outline-none"
                            >
                        </div>

                        <div>
                            <label class="text-ink-soft mb-1.5 block text-xs font-semibold uppercase" for="email">Correo electrónico (opcional)</label>
                            <input
                                id="email"
                                type="email"
                                wire:model.blur="email"
                                placeholder="tu@correo.cl"
                                class="border-line text-ink w-full rounded-xl border bg-white px-4 py-2.5 text-sm focus:border-brand-red-ui focus:ring-brand-red-ui/20 focus:ring-4 focus:outline-none"
                            >
                            @error('email') <p class="text-brand-red-ui mt-1 text-xs">{{ $message }}</p> @enderror
                        </div>

                        {{-- Honeypot anti-spam: campo oculto que solo un bot rellenaría --}}
                        <div class="absolute -left-[9999px]" aria-hidden="true">
                            <label for="sitio_web">Sitio web</label>
                            <input id="sitio_web" type="text" wire:model="sitioWeb" tabindex="-1" autocomplete="off">
                        </div>
                    </div>
                </div>
            </div>

            <livewire:cotizador.panel-precio :items="$items" :puedeEnviar="$this->puedeEnviar" />
        </div>
    </div>
</section>
