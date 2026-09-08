@php
    // Contenido estático — el catálogo `tipos_espacio` se eliminó en el Sprint 12
    // junto con el motor de cotización automática. Mismo criterio que mesh-types.
    $tiposEspacio = [
        ['icono' => '🪟', 'nombre' => 'Ventanas', 'descripcion' => 'Malla transparente ajustada al marco que deja pasar la luz y el aire, sin obstruir la vista.'],
        ['icono' => '🏙', 'nombre' => 'Balcones', 'descripcion' => 'Cierre perimetral seguro para departamentos en altura. Adiós al riesgo de caídas.'],
        ['icono' => '🌿', 'nombre' => 'Terrazas', 'descripcion' => 'Protege grandes superficies abiertas manteniendo tu espacio ventilado.'],
        ['icono' => '🪜', 'nombre' => 'Escaleras', 'descripcion' => 'Barreras de seguridad para escaleras interiores y pasillos con desnivel.'],
        ['icono' => '🐾', 'nombre' => 'Mascotas', 'descripcion' => 'Malla reforzada de 1 mm con rombo de 1,5 cm: el orificio es tan pequeño que perros y gatos no pueden sacar la cabeza.'],
        ['icono' => '🏊', 'nombre' => 'Piscinas', 'descripcion' => 'Delimita y protege el acceso a piscinas para el cuidado de los más pequeños.'],
    ];
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
                        {{ $tipo['icono'] }}
                    </span>
                    <h3 class="text-ink mt-4 text-lg font-bold">{{ $tipo['nombre'] }}</h3>
                    <p class="text-ink-soft mt-2 text-sm leading-relaxed">{{ $tipo['descripcion'] }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>
