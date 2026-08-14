@props(['item', 'index', 'tiposEspacio', 'tramosAltura', 'tiposMalla', 'puedeQuitar' => false])

<div class="border-line grid grid-cols-1 gap-4 rounded-2xl border bg-white p-5 sm:grid-cols-2" wire:key="item-{{ $index }}">
    <div class="sm:col-span-2">
        <label class="text-ink-soft mb-1.5 block text-xs font-semibold uppercase" for="item-{{ $index }}-tipo">
            ¿Qué quieres proteger?
        </label>
        <select
            id="item-{{ $index }}-tipo"
            wire:model.live="items.{{ $index }}.tipo_espacio_id"
            class="border-line text-ink w-full rounded-xl border bg-white px-4 py-2.5 text-sm focus:border-brand-red-ui focus:ring-brand-red-ui/20 focus:ring-4 focus:outline-none"
        >
            <option value="">Selecciona un espacio</option>
            @foreach ($tiposEspacio as $tipo)
                <option value="{{ $tipo->id }}">{{ $tipo->icono }} {{ $tipo->nombre }}</option>
            @endforeach
        </select>
    </div>

    <div>
        <label class="text-ink-soft mb-1.5 block text-xs font-semibold uppercase" for="item-{{ $index }}-ml">
            Metros lineales a cubrir
        </label>
        <input
            id="item-{{ $index }}-ml"
            type="number"
            min="0"
            step="0.1"
            inputmode="decimal"
            wire:model.live.debounce.400ms="items.{{ $index }}.metros_lineales"
            placeholder="Ej: 3.5"
            class="border-line text-ink w-full rounded-xl border bg-white px-4 py-2.5 text-sm focus:border-brand-red-ui focus:ring-brand-red-ui/20 focus:ring-4 focus:outline-none"
        >
    </div>

    <div>
        <label class="text-ink-soft mb-1.5 block text-xs font-semibold uppercase" for="item-{{ $index }}-altura">
            Altura del espacio
        </label>
        <select
            id="item-{{ $index }}-altura"
            wire:model.live="items.{{ $index }}.tramo_altura_id"
            class="border-line text-ink w-full rounded-xl border bg-white px-4 py-2.5 text-sm focus:border-brand-red-ui focus:ring-brand-red-ui/20 focus:ring-4 focus:outline-none"
        >
            <option value="">Selecciona la altura</option>
            @foreach ($tramosAltura as $tramo)
                <option value="{{ $tramo->id }}">{{ $tramo->etiqueta }}</option>
            @endforeach
        </select>
    </div>

    <div class="sm:col-span-2">
        <label class="text-ink-soft mb-1.5 block text-xs font-semibold uppercase">
            Tipo de malla
        </label>
        <div class="flex flex-wrap gap-3">
            @foreach ($tiposMalla as $malla)
                <label class="border-line has-checked:border-brand-red-ui has-checked:bg-brand-red-ui/5 flex cursor-pointer items-center gap-2 rounded-xl border px-4 py-2 text-sm">
                    <input
                        type="radio"
                        wire:model.live="items.{{ $index }}.tipo_malla_id"
                        value="{{ $malla->id }}"
                        class="text-brand-red-ui focus:ring-brand-red-ui"
                    >
                    {{ $malla->nombre }}
                </label>
            @endforeach
        </div>
    </div>

    @if ($puedeQuitar)
        <div class="sm:col-span-2 flex justify-end">
            <button
                type="button"
                wire:click="quitarItem({{ $index }})"
                class="text-ink-soft text-xs font-semibold hover:text-brand-red-ui"
            >
                Quitar este espacio
            </button>
        </div>
    @endif
</div>
