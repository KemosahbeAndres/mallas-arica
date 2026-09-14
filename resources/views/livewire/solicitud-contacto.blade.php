<section id="cotizador" class="scroll-mt-24 bg-cream">
    <div class="mx-auto max-w-7xl px-6 py-20 lg:px-8">
        <div class="max-w-2xl">
            <p class="text-brand-red-ui text-sm font-bold tracking-wide uppercase">Contacto</p>
            <h2 class="text-ink mt-3 text-3xl font-extrabold tracking-[-0.02em] sm:text-4xl">
                Pide tu visita en terreno
            </h2>
            <p class="text-ink-soft mt-4 text-lg">
                Para cotizar necesitamos medir y verificar que el espacio sea factible para instalar. Cuéntanos qué necesitas y coordinamos una visita técnica gratuita, sin compromiso.
            </p>
        </div>

        <div class="border-line mt-10 overflow-hidden rounded-3xl border bg-white shadow-sm lg:grid lg:grid-cols-2">
            <div class="p-6 lg:p-10">
                @if ($enviado)
                    <div class="border-brand-red-ui/30 bg-brand-red-ui/5 rounded-2xl border p-6">
                        <p class="text-ink font-semibold">
                            ¡Listo! Recibimos tus datos.
                        </p>
                        <p class="text-ink-soft mt-1 text-sm">
                            Te contactaremos pronto para coordinar la visita técnica. Si prefieres, también puedes
                            <a href="https://wa.me/56986455205" target="_blank" rel="noopener" class="text-brand-red-ui font-semibold underline">
                                escribirnos por WhatsApp
                            </a>.
                        </p>
                    </div>
                @else
                    <form wire:submit="enviar">
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label class="text-ink mb-1.5 block text-sm font-medium" for="nombre">Nombre</label>
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
                                <label class="text-ink mb-1.5 block text-sm font-medium" for="telefono">Teléfono</label>
                                <input
                                    id="telefono"
                                    type="tel"
                                    wire:model="telefono"
                                    placeholder="+56 9 ..."
                                    class="border-line text-ink w-full rounded-xl border bg-white px-4 py-2.5 text-sm focus:border-brand-red-ui focus:ring-brand-red-ui/20 focus:ring-4 focus:outline-none"
                                >
                                @error('telefono') <p class="text-brand-red-ui mt-1 text-xs">{{ $message }}</p> @enderror
                            </div>

                            <div class="sm:col-span-2">
                                <label class="text-ink mb-1.5 block text-sm font-medium" for="direccion">Dirección o sector</label>
                                <input
                                    id="direccion"
                                    type="text"
                                    wire:model="direccion"
                                    placeholder="Comuna / dirección aproximada"
                                    class="border-line text-ink w-full rounded-xl border bg-white px-4 py-2.5 text-sm focus:border-brand-red-ui focus:ring-brand-red-ui/20 focus:ring-4 focus:outline-none"
                                >
                                @error('direccion') <p class="text-brand-red-ui mt-1 text-xs">{{ $message }}</p> @enderror
                            </div>

                            <div class="sm:col-span-2">
                                <label class="text-ink mb-1.5 block text-sm font-medium" for="email">Correo electrónico (opcional)</label>
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
                            <span wire:loading.remove wire:target="enviar">Pide tu cotización →</span>
                            <span wire:loading wire:target="enviar">Enviando…</span>
                        </button>
                    </form>
                @endif
            </div>

            <div class="bg-ink flex flex-col justify-between gap-8 p-6 lg:p-10">
                <div>
                    <p class="text-cream-deep text-xs font-bold tracking-wide uppercase">Visita técnica</p>
                    <h3 class="mt-3 text-2xl font-bold tracking-[-0.02em] text-white">
                        Medimos, cotizamos y coordinamos la instalación
                    </h3>
                    <p class="text-cream-deep/70 mt-2 text-sm">Sin costo ni compromiso</p>

                    <div class="border-white/10 mt-6 flex flex-col gap-4 border-t pt-6">
                        <div class="flex items-start gap-3">
                            <span class="text-lg">📍</span>
                            <p class="text-sm text-white/90">Vamos a tu domicilio en Arica</p>
                        </div>
                        <div class="flex items-start gap-3">
                            <span class="text-lg">📏</span>
                            <p class="text-sm text-white/90">Medimos y confirmamos factibilidad</p>
                        </div>
                        <div class="flex items-start gap-3">
                            <span class="text-lg">💬</span>
                            <p class="text-sm text-white/90">Te enviamos la cotización exacta</p>
                        </div>
                    </div>
                </div>

                <div>
                    <a
                        href="https://wa.me/56986455205"
                        target="_blank"
                        rel="noopener"
                        class="bg-brand-red-ui hover:bg-brand-red-dark flex items-center justify-center gap-2 rounded-xl px-6 py-3.5 text-sm font-semibold text-white transition-colors"
                    >
                        Conversar por WhatsApp
                    </a>
                    <p class="text-cream-deep/60 mt-3 text-center text-xs">
                        Al hacer clic se abre WhatsApp con tus datos ya escritos.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>
