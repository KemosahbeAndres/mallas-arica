@php
    $espesores = [
        [
            'grosor' => '0,80 mm',
            'rombo' => 'Rombo 5×5 cm',
            'destacado' => '$25.000 por m²',
            'nota' => 'Valor de referencia',
        ],
        [
            'grosor' => '0,90 mm',
            'rombo' => 'Rombo 3×3 cm · especial para gatitos pequeños',
            'destacado' => 'Soporta 250 kg/m²',
            'nota' => 'Valor según visita técnica',
        ],
        [
            'grosor' => '1,9 mm',
            'rombo' => 'Rombo 4×4 cm · especial para gatitos mordedores',
            'destacado' => 'Soporta 300 kg/m²',
            'nota' => 'Valor según visita técnica',
        ],
    ];

    $sistemas = [
        [
            'titulo' => 'Ángulos de aluminio',
            'detalle' => 'Instalación con ángulos de aluminio 20×20×1,2 mm y amarres de alambre galvanizado.',
            'badge' => null,
        ],
        [
            'titulo' => 'Sistema Netzen',
            'detalle' => 'Instalación con arpones de poliamida, un sistema de anclaje certificado internacionalmente.',
            'badge' => 'A pedido',
        ],
    ];
@endphp

<section id="tipos-de-malla" class="scroll-mt-24 bg-cream-deep">
    <div class="mx-auto max-w-7xl px-6 py-20 lg:px-8">
        <div class="max-w-2xl">
            <p class="text-brand-red-ui text-sm font-bold tracking-wide uppercase">Tipos de malla</p>
            <h2 class="text-ink mt-3 text-3xl font-extrabold tracking-[-0.02em] sm:text-4xl">
                Elige el espesor según tu necesidad
            </h2>
            <p class="text-ink-soft mt-4 text-lg">
                Trabajamos con distintos espesores de malla de poliamida transparente. Cada espesor tiene su propio valor.
            </p>
        </div>

        <div class="mt-12 grid grid-cols-1 gap-6 sm:grid-cols-3">
            @foreach ($espesores as $espesor)
                <div class="border-line rounded-2xl border bg-white p-6 shadow-sm">
                    <p class="text-ink text-lg font-bold">{{ $espesor['grosor'] }}</p>
                    <p class="text-ink-soft mt-1 text-sm">{{ $espesor['rombo'] }}</p>
                    <p class="text-brand-red-ui mt-4 text-xl font-extrabold">{{ $espesor['destacado'] }}</p>
                    <p class="text-ink-soft mt-1 text-xs">{{ $espesor['nota'] }}</p>
                </div>
            @endforeach
        </div>

        <div class="mt-6 grid grid-cols-1 gap-6 sm:grid-cols-2">
            @foreach ($sistemas as $sistema)
                @php
                    $esOscuro = filled($sistema['badge']);
                @endphp
                <div class="{{ $esOscuro ? 'bg-ink' : 'border-line border bg-white' }} rounded-2xl p-6">
                    <div class="flex items-center gap-3">
                        <h3 class="{{ $esOscuro ? 'text-white' : 'text-ink' }} text-lg font-bold">{{ $sistema['titulo'] }}</h3>
                        @if ($sistema['badge'])
                            <span class="bg-brand-red-ui rounded-full px-3 py-1 text-xs font-bold text-white">
                                {{ $sistema['badge'] }}
                            </span>
                        @endif
                    </div>
                    <p class="{{ $esOscuro ? 'text-white/70' : 'text-ink-soft' }} mt-2 text-sm leading-relaxed">
                        {{ $sistema['detalle'] }}
                    </p>
                </div>
            @endforeach
        </div>
    </div>
</section>
