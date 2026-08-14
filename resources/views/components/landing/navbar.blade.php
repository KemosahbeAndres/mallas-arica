<header
    x-data="{ open: false }"
    class="sticky top-0 z-50 border-b border-line bg-cream/90 backdrop-blur"
>
    <nav class="mx-auto flex max-w-7xl items-center justify-between px-6 py-4 lg:px-8">
        <a href="{{ url('/') }}" class="flex items-center gap-3">
            <x-icon.mark class="h-9 w-9 shrink-0" />
            <span class="flex flex-col leading-none font-extrabold tracking-tight uppercase">
                <span class="text-brand-red-ui text-lg">Mallas</span>
                <span class="text-ink text-lg">Arica</span>
            </span>
        </a>

        <ul class="hidden items-center gap-8 text-sm font-medium text-ink-soft lg:flex">
            <li><a href="#servicios" class="transition-colors hover:text-ink">Servicios</a></li>
            <li><a href="#cotizador" class="transition-colors hover:text-ink">Cotizador</a></li>
            <li><a href="#galeria" class="transition-colors hover:text-ink">Galería</a></li>
            <li><a href="#faq" class="transition-colors hover:text-ink">FAQ</a></li>
        </ul>

        <div class="flex items-center gap-2">
            <a
                href="https://wa.me/56986455205"
                target="_blank"
                rel="noopener"
                class="bg-brand-red-ui hover:bg-brand-red-dark hidden rounded-full px-5 py-2.5 text-sm font-semibold text-white transition-colors sm:inline-block"
            >
                Escríbenos
            </a>

            <button
                type="button"
                @click="open = !open"
                class="text-ink -mr-2 inline-flex h-10 w-10 items-center justify-center rounded-full lg:hidden"
                aria-label="Abrir menú"
                :aria-expanded="open"
            >
                <svg x-show="!open" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5M3.75 17.25h16.5" />
                </svg>
                <svg x-show="open" x-cloak class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    </nav>

    <div
        x-show="open"
        x-cloak
        x-transition
        @click.outside="open = false"
        class="border-line bg-cream border-t px-6 py-4 lg:hidden"
    >
        <ul class="flex flex-col gap-4 text-sm font-medium text-ink-soft">
            <li><a href="#servicios" @click="open = false" class="hover:text-ink">Servicios</a></li>
            <li><a href="#cotizador" @click="open = false" class="hover:text-ink">Cotizador</a></li>
            <li><a href="#galeria" @click="open = false" class="hover:text-ink">Galería</a></li>
            <li><a href="#faq" @click="open = false" class="hover:text-ink">FAQ</a></li>
            <li>
                <a
                    href="https://wa.me/56986455205"
                    target="_blank"
                    rel="noopener"
                    class="bg-brand-red-ui inline-block rounded-full px-5 py-2.5 text-center font-semibold text-white"
                >
                    Escríbenos
                </a>
            </li>
        </ul>
    </div>
</header>
