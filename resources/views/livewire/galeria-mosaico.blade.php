<section
    id="galeria"
    class="scroll-mt-24 bg-cream-deep"
    x-data="{ open: false, activo: 0, total: {{ $items->count() }} }"
    @keydown.escape.window="open = false"
    @keydown.arrow-right.window="if (open) activo = (activo + 1) % total"
    @keydown.arrow-left.window="if (open) activo = (activo - 1 + total) % total"
>
    <div class="mx-auto max-w-7xl px-6 py-20 lg:px-8">
        <div class="max-w-2xl">
            <p class="text-brand-red-ui text-sm font-bold tracking-wide uppercase">Galería</p>
            <h2 class="text-ink mt-3 text-3xl font-extrabold tracking-[-0.02em] sm:text-4xl">
                Trabajos que hablan por sí solos
            </h2>
            <p class="text-ink-soft mt-4 text-lg">
                Una muestra de instalaciones reales en Arica. Tenemos más ejemplos disponibles por WhatsApp.
            </p>
        </div>

        @if ($items->isEmpty())
            <p class="text-ink-soft mt-12">Muy pronto vamos a publicar fotos de nuestros trabajos aquí.</p>
        @else
            <div class="mt-12 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
                @foreach ($items as $index => $item)
                    <button
                        type="button"
                        @click="activo = {{ $index }}; open = true"
                        class="group border-line relative aspect-square overflow-hidden rounded-2xl border bg-white {{ $index === 0 ? 'col-span-2 row-span-2' : '' }}"
                    >
                        <img
                            src="{{ $item->url }}"
                            alt="{{ $item->titulo }}"
                            loading="lazy"
                            class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105"
                        >
                        <span class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/60 to-transparent p-3 text-left text-sm font-medium text-white opacity-0 transition-opacity group-hover:opacity-100">
                            {{ $item->titulo }}
                        </span>
                    </button>
                @endforeach
            </div>

            {{-- Lightbox --}}
            <div
                x-show="open"
                x-cloak
                x-transition.opacity
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/90 p-4"
                @click.self="open = false"
            >
                <button
                    type="button"
                    @click="open = false"
                    class="absolute top-6 right-6 text-3xl text-white/80 hover:text-white"
                    aria-label="Cerrar"
                >&times;</button>

                <button
                    type="button"
                    @click="activo = (activo - 1 + total) % total"
                    class="absolute left-4 text-3xl text-white/80 hover:text-white"
                    aria-label="Anterior"
                >&larr;</button>

                <template x-for="(itemUrl, i) in {{ Js::from($items->pluck('url')) }}" :key="i">
                    <img x-show="activo === i" :src="itemUrl" class="max-h-[85vh] max-w-full rounded-lg object-contain">
                </template>

                <button
                    type="button"
                    @click="activo = (activo + 1) % total"
                    class="absolute right-4 text-3xl text-white/80 hover:text-white"
                    aria-label="Siguiente"
                >&rarr;</button>
            </div>
        @endif

        <div class="mt-10 text-center">
            <a
                href="https://wa.me/56986455205"
                target="_blank"
                rel="noopener"
                class="text-brand-red-ui inline-flex items-center gap-2 text-base font-semibold hover:underline"
            >
                Ver más en WhatsApp →
            </a>
        </div>
    </div>
</section>
