<footer class="bg-ink">
    <div class="mx-auto max-w-7xl px-6 py-10 lg:px-8">
        <div class="flex flex-col items-center gap-6 sm:flex-row sm:justify-between">
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

            <div class="flex items-center gap-3">
                <a
                    href="https://www.facebook.com/mallas.arica/"
                    target="_blank"
                    rel="noopener"
                    aria-label="Facebook de Mallas Arica Jacob"
                    class="flex h-9 w-9 items-center justify-center rounded-full bg-white/10 text-white transition-colors hover:bg-white/20"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-4 w-4">
                        <path d="M22 12.06C22 6.5 17.52 2 12 2S2 6.5 2 12.06c0 5.02 3.66 9.18 8.44 9.94v-7.03H7.9v-2.91h2.54V9.85c0-2.51 1.49-3.9 3.77-3.9 1.09 0 2.24.2 2.24.2v2.46h-1.26c-1.24 0-1.63.77-1.63 1.56v1.89h2.78l-.44 2.91h-2.34V22c4.78-.76 8.44-4.92 8.44-9.94Z" />
                    </svg>
                </a>
                <a
                    href="https://www.instagram.com/mallas_arica_jacob"
                    target="_blank"
                    rel="noopener"
                    aria-label="Instagram de Mallas Arica Jacob"
                    class="flex h-9 w-9 items-center justify-center rounded-full bg-white/10 text-white transition-colors hover:bg-white/20"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-4 w-4">
                        <path d="M12 2c-2.72 0-3.06.01-4.13.06-1.06.05-1.79.22-2.43.47a4.9 4.9 0 0 0-1.77 1.15A4.9 4.9 0 0 0 2.53 5.44c-.25.64-.42 1.37-.47 2.43C2.01 8.94 2 9.28 2 12s.01 3.06.06 4.13c.05 1.06.22 1.79.47 2.43a4.9 4.9 0 0 0 1.15 1.77 4.9 4.9 0 0 0 1.77 1.15c.64.25 1.37.42 2.43.47C8.94 21.99 9.28 22 12 22s3.06-.01 4.13-.06c1.06-.05 1.79-.22 2.43-.47a4.9 4.9 0 0 0 1.77-1.15 4.9 4.9 0 0 0 1.15-1.77c.25-.64.42-1.37.47-2.43.05-1.07.06-1.41.06-4.13s-.01-3.06-.06-4.13c-.05-1.06-.22-1.79-.47-2.43a4.9 4.9 0 0 0-1.15-1.77A4.9 4.9 0 0 0 18.56.53c-.64-.25-1.37-.42-2.43-.47C15.06.01 14.72 0 12 0Zm0 4.32c-4.24 0-7.68 3.44-7.68 7.68s3.44 7.68 7.68 7.68 7.68-3.44 7.68-7.68S16.24 4.32 12 4.32Zm0 12.68c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5Zm7.85-12.94a1.8 1.8 0 1 1-3.6 0 1.8 1.8 0 0 1 3.6 0Z" />
                    </svg>
                </a>
            </div>

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
