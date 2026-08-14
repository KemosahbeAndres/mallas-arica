@php
    $pasos = [
        ['numero' => 1, 'titulo' => 'Escríbenos', 'detalle' => 'Cuéntanos qué necesitas por WhatsApp, teléfono o el cotizador.'],
        ['numero' => 2, 'titulo' => 'Visita y medición', 'detalle' => 'Vamos a tu domicilio, medimos y verificamos que sea factible instalar.'],
        ['numero' => 3, 'titulo' => 'Instalación', 'detalle' => 'Instalamos con malla certificada, normalmente el mismo día.'],
        ['numero' => 4, 'titulo' => 'Tranquilidad', 'detalle' => 'Tu familia queda protegida con una solución segura y duradera.'],
    ];
@endphp

<section class="bg-cream-deep">
    <div class="mx-auto max-w-7xl px-6 py-20 lg:px-8">
        <div class="max-w-2xl">
            <p class="text-brand-red-ui text-sm font-bold tracking-wide uppercase">Cómo trabajamos</p>
            <h2 class="text-ink mt-3 text-3xl font-extrabold tracking-[-0.02em] sm:text-4xl">
                Del mensaje a la tranquilidad, en 4 pasos
            </h2>
        </div>

        <div class="mt-12 grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($pasos as $paso)
                <div>
                    <span class="bg-ink flex h-10 w-10 items-center justify-center rounded-full text-sm font-bold text-white">
                        {{ $paso['numero'] }}
                    </span>
                    <h3 class="text-ink mt-4 text-lg font-bold">{{ $paso['titulo'] }}</h3>
                    <p class="text-ink-soft mt-2 text-sm leading-relaxed">{{ $paso['detalle'] }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>
