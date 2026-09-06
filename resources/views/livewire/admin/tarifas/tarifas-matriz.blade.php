<div>
    <h1 class="text-xl font-bold text-ink tracking-tight">Tarifas</h1>
    <p class="mt-1 text-sm text-ink-soft">Precio por metro lineal según tipo de espacio y tramo de altura. Cada celda guarda la tarifa vigente a partir de hoy.</p>

    <div class="mt-6 overflow-x-auto rounded-xl border border-line bg-white">
        <table class="w-full border-collapse text-sm">
            <thead>
                <tr class="bg-ink text-white">
                    <th class="px-4 py-3 text-left">Tipo de espacio</th>
                    @foreach ($tramosAltura as $tramoAltura)
                        <th class="px-4 py-3 text-left">{{ $tramoAltura->etiqueta }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($tiposEspacio as $tipoEspacio)
                    <tr class="border-b border-line">
                        <td class="px-4 py-3 font-medium text-ink">{{ $tipoEspacio->nombre }}</td>

                        @foreach ($tramosAltura as $tramoAltura)
                            <td class="px-4 py-3">
                                @if ($tramoAltura->requiere_visita)
                                    <span class="text-xs italic text-ink-soft">Requiere visita</span>
                                @else
                                    <div class="flex flex-col gap-1" wire:key="celda-{{ $tipoEspacio->id }}-{{ $tramoAltura->id }}">
                                        <div class="flex items-center gap-1">
                                            <span class="text-xs text-ink-soft">Min</span>
                                            <input
                                                type="number"
                                                wire:model="precios.{{ $tipoEspacio->id }}.{{ $tramoAltura->id }}.min"
                                                class="w-24 rounded border border-line px-2 py-1 text-sm"
                                            >
                                        </div>
                                        <div class="flex items-center gap-1">
                                            <span class="text-xs text-ink-soft">Max</span>
                                            <input
                                                type="number"
                                                wire:model="precios.{{ $tipoEspacio->id }}.{{ $tramoAltura->id }}.max"
                                                class="w-24 rounded border border-line px-2 py-1 text-sm"
                                            >
                                        </div>
                                        <button
                                            type="button"
                                            wire:click="guardarCelda({{ $tipoEspacio->id }}, {{ $tramoAltura->id }})"
                                            class="mt-1 rounded bg-brand-red-ui px-2 py-1 text-xs font-semibold text-white hover:bg-brand-red-dark"
                                        >
                                            Guardar
                                        </button>
                                        @error("precios.{$tipoEspacio->id}.{$tramoAltura->id}.min")
                                            <span class="text-xs text-brand-red-dark">{{ $message }}</span>
                                        @enderror
                                        @error("precios.{$tipoEspacio->id}.{$tramoAltura->id}.max")
                                            <span class="text-xs text-brand-red-dark">{{ $message }}</span>
                                        @enderror
                                    </div>
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
