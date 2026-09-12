<div class="flex flex-col gap-4">
    @if ($guardado)
        <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-2.5 text-sm font-medium text-green-800">
            {{ $guardado }}
        </div>
    @endif

    <div>
        <button
            type="button"
            wire:click="agregar"
            class="border-brand-red-ui/40 text-brand-red-ui hover:bg-brand-red-ui/5 rounded-lg border px-4 py-2 text-sm font-semibold transition-colors"
        >
            + Agregar pregunta
        </button>
    </div>

    <form wire:submit="guardar" class="flex flex-col gap-4">
        @forelse ($filas as $indice => $fila)
            <div class="border-line rounded-lg border bg-white px-4 py-3" wire:key="faq-fila-{{ $indice }}">
                <div class="flex items-start gap-3">
                    <span class="bg-brand-red-ui/10 text-brand-red-ui mt-1 flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-xs font-bold">
                        {{ $indice + 1 }}
                    </span>

                    <div class="flex-1">
                        <input
                            type="text"
                            wire:model="filas.{{ $indice }}.pregunta"
                            placeholder="Pregunta"
                            class="border-line focus:border-brand-red-ui focus:ring-brand-red-ui/20 w-full rounded-lg border px-3 py-2 text-sm font-semibold focus:ring"
                        >
                        @error("filas.{$indice}.pregunta")
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror

                        <textarea
                            wire:model="filas.{{ $indice }}.respuesta"
                            rows="3"
                            placeholder="Respuesta"
                            class="border-line focus:border-brand-red-ui focus:ring-brand-red-ui/20 mt-2 w-full rounded-lg border px-3 py-2 text-sm focus:ring"
                        ></textarea>
                        @error("filas.{$indice}.respuesta")
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror

                        <label class="text-ink-soft mt-3 flex items-center gap-2 text-sm">
                            <input type="checkbox" wire:model="filas.{{ $indice }}.publicada" class="text-brand-red-ui rounded">
                            Publicada en el sitio
                        </label>
                    </div>

                    <button
                        type="button"
                        wire:click="eliminar({{ $indice }})"
                        wire:confirm="¿Eliminar esta pregunta?"
                        class="text-ink-soft shrink-0 rounded-lg p-2 text-sm hover:bg-red-50 hover:text-red-600"
                        title="Eliminar"
                    >
                        🗑
                    </button>
                </div>
            </div>
        @empty
            <p class="text-ink-soft border-line rounded-lg border border-dashed bg-white px-4 py-6 text-center text-sm">
                No hay preguntas todavía. Agrega la primera con el botón de arriba.
            </p>
        @endforelse

        @if (count($filas) > 0)
            <div>
                <button
                    type="submit"
                    class="bg-brand-red-ui hover:bg-brand-red-dark rounded-lg px-5 py-2.5 text-sm font-semibold text-white transition-colors"
                    wire:loading.attr="disabled"
                >
                    <span wire:loading.remove wire:target="guardar">Guardar cambios</span>
                    <span wire:loading wire:target="guardar">Guardando…</span>
                </button>
            </div>
        @endif
    </form>
</div>
