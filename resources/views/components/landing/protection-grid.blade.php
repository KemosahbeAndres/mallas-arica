@php
    $tiposEspacio = \App\Models\TipoEspacio::query()
        ->where('activo', true)
        ->orderBy('orden')
        ->get();
@endphp

<section id="servicios" class="scroll-mt-24 bg-cream">
    <div class="mx-auto max-w-7xl px-6 py-20 lg:px-8">
        <div class="max-w-2xl">
            <p class="text-brand-red-ui text-sm font-bold tracking-wide uppercase">Qué protegemos</p>
            <h2 class="text-ink mt-3 text-3xl font-extrabold tracking-[-0.02em] sm:text-4xl">
                Una malla para cada espacio de tu hogar
            </h2>
            <p class="text-ink-soft mt-4 text-lg">
                Cada casa y departamento es distinto. Adaptamos la instalación a la medida exacta de tu espacio,
                sin perforar de más y sin arruinar la vista.
            </p>
        </div>

        <div class="mt-12 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($tiposEspacio as $tipo)
                <div class="border-line rounded-2xl border bg-white p-6 shadow-sm">
                    <span class="bg-cream-deep flex h-12 w-12 items-center justify-center rounded-xl text-2xl" aria-hidden="true">
                        {{ $tipo->icono }}
                    </span>
                    <h3 class="text-ink mt-4 text-lg font-bold">{{ $tipo->nombre }}</h3>
                    <p class="text-ink-soft mt-2 text-sm leading-relaxed">{{ $tipo->descripcion }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>
