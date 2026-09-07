@php
    $titulosGrupo = [
        'hero' => ['Sección principal (Hero)', 'Lo primero que ve el visitante al entrar al sitio.'],
        'nosotros' => ['Sección "Nosotros"', 'Descripción de la empresa y datos de contacto.'],
        'cotizaciones' => ['Cotizaciones', 'Texto que aparece al pie del PDF de cotización.'],
    ];
@endphp

<form wire:submit="guardar" class="flex flex-col gap-6">
    @if ($guardado)
        <div class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-800">
            {{ $guardado }}
        </div>
    @endif

    @foreach ($this->grupos as $grupo => $campos)
        <section class="border-line rounded-2xl border bg-white p-6">
            <h2 class="text-ink text-base font-bold tracking-tight">
                {{ $titulosGrupo[$grupo][0] ?? ucfirst($grupo) }}
            </h2>
            @if (isset($titulosGrupo[$grupo][1]))
                <p class="text-ink-soft mt-1 text-sm">{{ $titulosGrupo[$grupo][1] }}</p>
            @endif

            <div class="mt-5 grid gap-5 sm:grid-cols-2">
                @foreach ($campos as $campo)
                    <div class="{{ $campo->tipo === 'textarea' ? 'sm:col-span-2' : '' }}">
                        <label for="c-{{ $campo->id }}" class="text-ink-soft block text-sm font-semibold">
                            {{ $campo->label }}
                        </label>

                        @if ($campo->tipo === 'textarea')
                            <textarea
                                id="c-{{ $campo->id }}"
                                wire:model="valores.{{ $campo->id }}"
                                rows="3"
                                class="border-line focus:border-brand-red-ui focus:ring-brand-red-ui/20 mt-1.5 w-full rounded-lg border px-3 py-2 text-sm focus:ring"
                            ></textarea>
                        @else
                            <input
                                id="c-{{ $campo->id }}"
                                type="text"
                                wire:model="valores.{{ $campo->id }}"
                                class="border-line focus:border-brand-red-ui focus:ring-brand-red-ui/20 mt-1.5 w-full rounded-lg border px-3 py-2 text-sm focus:ring"
                            >
                        @endif

                        @error("valores.{$campo->id}")
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                @endforeach
            </div>
        </section>
    @endforeach

    <div class="flex items-center gap-3">
        <button
            type="submit"
            class="bg-brand-red-ui hover:bg-brand-red-dark rounded-lg px-5 py-2.5 text-sm font-semibold text-white transition-colors"
            wire:loading.attr="disabled"
        >
            <span wire:loading.remove wire:target="guardar">Guardar cambios</span>
            <span wire:loading wire:target="guardar">Guardando…</span>
        </button>
    </div>
</form>
