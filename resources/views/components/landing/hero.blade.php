<section class="bg-ink relative overflow-hidden">
    <div
        class="pointer-events-none absolute inset-0"
        style="background: radial-gradient(60% 50% at 50% 0%, rgba(229,51,41,0.35) 0%, rgba(229,51,41,0) 70%);"
    ></div>

    <div class="relative mx-auto grid max-w-7xl gap-12 px-6 py-20 lg:grid-cols-2 lg:items-center lg:gap-16 lg:px-8 lg:py-28">
        <div>
            <span class="border-brand-red-ui/40 text-brand-red-ui inline-flex items-center gap-2 rounded-full border bg-white/5 px-4 py-1.5 text-xs font-semibold tracking-wide uppercase">
                🛡 Mallas de seguridad certificadas · Arica
            </span>

            <h1 class="mt-6 text-4xl leading-[1.05] font-extrabold tracking-[-0.02em] text-white sm:text-5xl lg:text-6xl">
                Seguridad para tus hijos,
                <span class="text-brand-red-ui">tranquilidad</span>
                para tu familia.
            </h1>

            <p class="mt-6 max-w-xl text-lg text-white/70">
                Instalamos mallas de seguridad en ventanas, balcones y terrazas. Monofilamento de poliamida
                transparente que soporta más de 200 kg por m² y con filtro UV para el sol de Arica.
            </p>

            <div class="mt-8 flex flex-col gap-4 sm:flex-row">
                <a
                    href="#cotizador"
                    class="bg-brand-red-ui hover:bg-brand-red-dark inline-flex items-center justify-center rounded-full px-7 py-3.5 text-base font-semibold text-white transition-colors"
                >
                    Cotizar en 30 segundos
                </a>
                <a
                    href="https://wa.me/56986455205"
                    target="_blank"
                    rel="noopener"
                    class="inline-flex items-center justify-center rounded-full border border-white/20 px-7 py-3.5 text-base font-semibold text-white transition-colors hover:bg-white/10"
                >
                    Agendar visita
                </a>
            </div>

            <ul class="mt-8 flex flex-col gap-3 text-sm text-white/70 sm:flex-row sm:gap-8">
                <li class="flex items-center gap-2">
                    <span class="text-brand-red-ui">✓</span> Instalación el mismo día
                </li>
                <li class="flex items-center gap-2">
                    <span class="text-brand-red-ui">✓</span> Material certificado
                </li>
                <li class="flex items-center gap-2">
                    <span class="text-brand-red-ui">✓</span> Pago en terreno
                </li>
            </ul>
        </div>

        <div class="relative">
            <div class="border-line/10 aspect-[4/5] w-full rounded-3xl border bg-white/5 lg:aspect-[5/6]">
                <img
                    src="{{ asset('images/hero-placeholder.jpg') }}"
                    alt="Malla de seguridad instalada en balcón, Arica"
                    class="h-full w-full rounded-3xl object-cover"
                    onerror="this.style.display='none'"
                    loading="eager"
                >
            </div>
            <div class="bg-brand-red-ui absolute -bottom-6 -left-6 rounded-2xl px-6 py-4 text-white shadow-xl">
                <p class="text-2xl font-extrabold tracking-tight">+200</p>
                <p class="text-xs font-medium text-white/85">kg/m² de resistencia</p>
            </div>
        </div>
    </div>
</section>
