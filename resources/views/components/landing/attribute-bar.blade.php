@php
    $atributos = [
        ['icono' => '👁', 'titulo' => 'Transparente', 'detalle' => 'Poliamida, casi invisible'],
        ['icono' => '💪', 'titulo' => '200 kg/m²', 'detalle' => 'Resistencia comprobada'],
        ['icono' => '☀️', 'titulo' => 'Filtro UV', 'detalle' => 'Resiste el sol de Arica'],
        ['icono' => '⚡', 'titulo' => 'Mismo día', 'detalle' => 'Instalación rápida'],
    ];
@endphp

<section class="bg-brand-red-ui">
    <div class="mx-auto grid max-w-7xl grid-cols-2 gap-6 px-6 py-8 lg:grid-cols-4 lg:gap-8 lg:px-8">
        @foreach ($atributos as $atributo)
            <div class="flex items-center gap-3 text-white">
                <span class="text-2xl" aria-hidden="true">{{ $atributo['icono'] }}</span>
                <div class="leading-tight">
                    <p class="font-bold">{{ $atributo['titulo'] }}</p>
                    <p class="text-xs text-white/80">{{ $atributo['detalle'] }}</p>
                </div>
            </div>
        @endforeach
    </div>
</section>
