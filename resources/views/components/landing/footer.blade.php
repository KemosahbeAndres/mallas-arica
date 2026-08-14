<footer class="bg-ink">
    <div class="mx-auto max-w-7xl px-6 py-10 lg:px-8">
        <div class="flex flex-col items-center gap-4 sm:flex-row sm:justify-between">
            <a href="{{ url('/') }}" class="flex items-center gap-3">
                <x-icon.mark class="h-8 w-8 shrink-0" />
                <span class="flex flex-col leading-none font-extrabold tracking-tight uppercase">
                    <span class="text-brand-red-ui text-base">Mallas</span>
                    <span class="text-base text-white">Arica</span>
                </span>
            </a>

            <p class="text-center text-sm text-white/60 sm:text-right">
                Av. Diego Portales #1333, Arica · +56 9 8645 5205
                <br class="hidden sm:inline">
                © {{ now()->year }} Mallas Arica. Todos los derechos reservados.
            </p>
        </div>
    </div>
</footer>
