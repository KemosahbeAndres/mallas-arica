@php
    $preguntas = [
        [
            'pregunta' => '¿Cuánto cuesta la instalación en una ventana o balcón?',
            'respuesta' => 'Cotizamos por metro lineal, y el valor varía según la altura del espacio y si es ventana o balcón. Usa el cotizador de arriba para una estimación referencial al instante, o pídenos una cotización exacta tras la visita de medición.',
        ],
        [
            'pregunta' => '¿Tienen distintos tipos de malla?',
            'respuesta' => 'Sí, contamos con malla estándar transparente y una malla reforzada especial para mascotas, con rombo más pequeño para evitar que perros y gatos saquen la cabeza.',
        ],
        [
            'pregunta' => '¿Cómo puedo pedir una cotización?',
            'respuesta' => 'Puedes usar el cotizador en esta página, escribirnos por WhatsApp o llamarnos directamente. Coordinamos una visita técnica gratuita para confirmar la medida exacta.',
        ],
        [
            'pregunta' => '¿Cuánto se demoran en hacer el trabajo?',
            'respuesta' => 'La mayoría de las instalaciones se realizan el mismo día de la visita, dependiendo de la complejidad y el número de espacios a cubrir.',
        ],
        [
            'pregunta' => '¿El material es seguro?',
            'respuesta' => 'Sí, trabajamos con malla de monofilamento de poliamida certificada por el fabricante, con resistencia comprobada de más de 200 kg/m² y filtro UV.',
        ],
        [
            'pregunta' => '¿Dónde puedo obtener más información?',
            'respuesta' => 'Escríbenos por WhatsApp al +56 9 8645 5205 o visítanos en Av. Diego Portales #1333, Arica.',
        ],
    ];
@endphp

<section id="faq" class="scroll-mt-24 bg-cream">
    <div class="mx-auto max-w-3xl px-6 py-20 lg:px-8">
        <div class="text-center">
            <p class="text-brand-red-ui text-sm font-bold tracking-wide uppercase">Preguntas frecuentes</p>
            <h2 class="text-ink mt-3 text-3xl font-extrabold tracking-[-0.02em] sm:text-4xl">
                Resolvemos tus dudas
            </h2>
        </div>

        <div class="border-line mt-12 divide-y divide-[var(--color-line)] rounded-2xl border bg-white">
            @foreach ($preguntas as $index => $item)
                <div x-data="{ open: false }">
                    <button
                        type="button"
                        @click="open = !open"
                        class="flex w-full items-center justify-between gap-4 px-6 py-5 text-left"
                        :aria-expanded="open"
                    >
                        <span class="text-ink font-semibold">{{ $item['pregunta'] }}</span>
                        <span
                            class="text-ink-soft shrink-0 text-xl transition-transform"
                            :class="{ 'rotate-45': open }"
                        >+</span>
                    </button>
                    <div
                        x-show="open"
                        x-cloak
                        x-transition
                        class="px-6 pb-5"
                    >
                        <p class="text-ink-soft text-sm leading-relaxed">{{ $item['respuesta'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>
