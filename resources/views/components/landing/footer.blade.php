<footer class="bg-ink">
    <div class="mx-auto max-w-7xl px-6 py-10 lg:px-8">
        <div class="flex flex-col items-center gap-4 sm:flex-row sm:justify-between">
            <a href="{{ url('/') }}" class="flex items-center gap-3">
                <x-icon.mark variant="inverted" class="h-8 w-8 shrink-0" />
                <span class="flex flex-col leading-none font-extrabold tracking-tight uppercase">
                    <span class="text-base">
                        <span class="text-brand-red-ui">Mallas</span>
                        <span class="text-white">Arica</span>
                    </span>
                    <span class="text-white/60 mt-0.5 text-[9px] font-bold tracking-[0.2em]">Jacob</span>
                </span>
            </a>

            <p class="text-center text-sm text-white/60 sm:text-right">
                Av. Diego Portales #1333, Arica · +56 9 8645 5205
                <br class="hidden sm:inline">
                <a href="mailto:contacto@mallasarica.cl" class="hover:text-white">contacto@mallasarica.cl</a>
                ·
                <a href="mailto:ventas@mallasarica.cl" class="hover:text-white">ventas@mallasarica.cl</a>
                <br class="hidden sm:inline">
                © {{ now()->year }} Mallas Arica Jacob. Todos los derechos reservados.
            </p>
        </div>
    </div>
</footer>
