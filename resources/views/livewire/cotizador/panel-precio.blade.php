@php
    $formatear = fn (int $valor) => number_format($valor, 0, ',', '.');
@endphp

<div class="bg-ink sticky top-24 rounded-3xl p-8 text-white">
    <p class="text-xs font-bold tracking-wide text-white/50 uppercase">Tu cotización referencial</p>

    @if (! $resultado)
        <p class="mt-4 text-lg text-white/70">
            Completa al menos un espacio para ver tu rango de precio estimado.
        </p>
    @elseif ($resultado->requiereVisita)
        <h3 class="mt-4 text-2xl font-extrabold tracking-tight">
            Requiere evaluación en terreno
        </h3>
        <p class="mt-3 text-sm leading-relaxed text-white/70">
            Uno o más espacios necesitan que un técnico confirme la medida exacta (altura sobre 3 m, más de 40 m
            lineales, o piscinas). Coordina una visita gratuita y te enviamos una cotización exacta.
        </p>
    @else
        <p class="mt-4 text-4xl font-extrabold tracking-tight">
            ${{ $formatear($resultado->totalMin) }} – ${{ $formatear($resultado->totalMax) }}
        </p>
        <p class="mt-1 text-xs text-white/50">Valores incluyen IVA</p>

        <div class="mt-6 space-y-3 border-t border-white/10 pt-6">
            @foreach ($resultado->items as $item)
                <div class="flex items-center justify-between text-sm">
                    <span class="text-white/70">
                        {{ number_format($item->metrosLineales, 1) }} m
                        @if ($item->metrosLineales < \App\Services\CotizacionCalculatorService::METRAJE_MINIMO)
                            <span class="text-white/40">(mín. 2 m)</span>
                        @endif
                    </span>
                    <span class="font-semibold">
                        ${{ $formatear($item->subtotalMin) }} – ${{ $formatear($item->subtotalMax) }}
                    </span>
                </div>
            @endforeach
        </div>

        @if (collect($resultado->items)->contains(fn ($i) => $i->metrosLineales < \App\Services\CotizacionCalculatorService::METRAJE_MINIMO))
            <p class="mt-4 text-xs text-white/50">Cobro mínimo de 2 m lineales por espacio.</p>
        @endif
    @endif

    <button
        type="button"
        wire:click="crearPorWhatsapp"
        @disabled(! $puedeEnviar)
        class="bg-brand-red-ui hover:bg-brand-red-dark mt-8 flex w-full items-center justify-center gap-2 rounded-full px-6 py-3.5 text-sm font-semibold text-white transition-colors disabled:cursor-not-allowed disabled:opacity-40"
    >
        Crear por WhatsApp
    </button>

    <button
        type="button"
        wire:click="descargarPdf"
        wire:loading.attr="disabled"
        wire:target="descargarPdf"
        @disabled(! $puedeEnviar)
        class="border-white/20 text-white hover:bg-white/10 mt-3 flex w-full items-center justify-center gap-2 rounded-full border px-6 py-3.5 text-sm font-semibold transition-colors disabled:cursor-not-allowed disabled:opacity-40"
    >
        Descargar cotización en PDF
    </button>

    <p class="mt-3 text-center text-[11px] text-white/40">
        Al continuar guardamos tu cotización. Puedes coordinar por WhatsApp o descargar el detalle en PDF.
    </p>
</div>
