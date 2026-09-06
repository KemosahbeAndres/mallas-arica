<section id="cotizador" class="scroll-mt-24 bg-cream">
    <div class="mx-auto max-w-7xl px-6 py-20 lg:px-8">
        <div class="max-w-2xl">
            <p class="text-brand-red-ui text-sm font-bold tracking-wide uppercase">Cotizador</p>
            <h2 class="text-ink mt-3 text-3xl font-extrabold tracking-[-0.02em] sm:text-4xl">
                Solicita tu cotización en terreno
            </h2>
            <p class="text-ink-soft mt-4 text-lg">
                Cada espacio es distinto, así que preferimos medir antes de darte un precio. Déjanos tus datos y coordinamos una visita técnica gratuita, sin costo ni compromiso.
            </p>
        </div>

        @if ($numeroGenerado)
            <div class="border-brand-red-ui/30 bg-brand-red-ui/5 mt-10 rounded-2xl border p-6">
                <p class="text-ink font-semibold">
                    ¡Listo! Tu solicitud quedó guardada con el N°
                    <span class="text-brand-red-ui">{{ $numeroGenerado }}</span>.
                </p>
                <p class="text-ink-soft mt-1 text-sm">
                    Te contactaremos pronto para coordinar la visita. Si prefieres, también puedes
                    <a href="https://wa.me/56986455205" target="_blank" rel="noopener" class="text-brand-red-ui font-semibold underline">
                        escribirnos por WhatsApp
                    </a>.
                </p>
            </div>
        @else
            <form wire:submit="enviar" class="border-line mt-10 max-w-2xl rounded-2xl border bg-white p-6">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="text-ink-soft mb-1.5 block text-xs font-semibold uppercase" for="nombre">Nombre</label>
                        <input
                            id="nombre"
                            type="text"
                            wire:model="nombre"
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
                            wire:model="telefono"
                            placeholder="+56 9 1234 5678"
                            class="border-line text-ink w-full rounded-xl border bg-white px-4 py-2.5 text-sm focus:border-brand-red-ui focus:ring-brand-red-ui/20 focus:ring-4 focus:outline-none"
                        >
                        @error('telefono') <p class="text-brand-red-ui mt-1 text-xs">{{ $message }}</p> @enderror
                    </div>

                    <div class="sm:col-span-2">
                        <label class="text-ink-soft mb-1.5 block text-xs font-semibold uppercase" for="direccion">Dirección</label>
                        <input
                            id="direccion"
                            type="text"
                            wire:model="direccion"
                            placeholder="Ej: Condominio Las Torres, depto 302, Arica"
                            class="border-line text-ink w-full rounded-xl border bg-white px-4 py-2.5 text-sm focus:border-brand-red-ui focus:ring-brand-red-ui/20 focus:ring-4 focus:outline-none"
                        >
                        @error('direccion') <p class="text-brand-red-ui mt-1 text-xs">{{ $message }}</p> @enderror
                    </div>

                    <div class="sm:col-span-2">
                        <label class="text-ink-soft mb-1.5 block text-xs font-semibold uppercase" for="email">Correo electrónico (opcional)</label>
                        <input
                            id="email"
                            type="email"
                            wire:model="email"
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

                    @error('throttle') <p class="text-brand-red-ui mt-2 text-sm sm:col-span-2">{{ $message }}</p> @enderror
                </div>

                <button
                    type="submit"
                    wire:loading.attr="disabled"
                    wire:target="enviar"
                    class="bg-brand-red-ui hover:bg-brand-red-dark mt-6 w-full rounded-full px-7 py-3.5 text-base font-semibold text-white transition-colors disabled:opacity-60 sm:w-auto"
                >
                    <span wire:loading.remove wire:target="enviar">Solicitar cotización</span>
                    <span wire:loading wire:target="enviar">Enviando…</span>
                </button>
            </form>
        @endif
    </div>
</section>
