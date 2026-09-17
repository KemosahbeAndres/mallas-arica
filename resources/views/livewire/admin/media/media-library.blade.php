<div class="border-line rounded-xl border bg-white p-5">
    <h3 class="text-ink font-semibold">Librería de medios</h3>
    <p class="text-ink-soft mt-1 text-sm">
        Sube imágenes aquí una sola vez. Luego se agrupan en álbumes o se asignan directo a una sección de la landing.
    </p>

    <form wire:submit="subir" class="border-line mt-4 flex flex-col gap-3 rounded-lg border border-dashed p-4 sm:flex-row sm:items-end">
        <div class="flex-1">
            <label class="text-ink-soft text-xs font-semibold uppercase">Imagen</label>
            <input type="file" wire:model="foto" class="mt-1 block w-full text-sm">
            @error('foto')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
            <div wire:loading wire:target="foto" class="text-ink-soft mt-1 text-xs">Subiendo…</div>
        </div>

        <div class="flex-1">
            <label class="text-ink-soft text-xs font-semibold uppercase">Título (opcional)</label>
            <input
                type="text"
                wire:model="titulo"
                placeholder="Ej. Ventana con malla"
                class="border-line focus:border-brand-red-ui focus:ring-brand-red-ui/20 mt-1 w-full rounded-lg border px-3 py-2 text-sm focus:ring"
            >
        </div>

        <button
            type="submit"
            class="bg-brand-red-ui hover:bg-brand-red-dark shrink-0 rounded-lg px-5 py-2.5 text-sm font-semibold text-white transition-colors"
        >
            Subir imagen
        </button>
    </form>

    <div class="mt-5 grid grid-cols-2 gap-3 sm:grid-cols-4 lg:grid-cols-6">
        @forelse ($this->items as $item)
            <div wire:key="media-item-{{ $item->id }}" class="group relative">
                <div class="border-line aspect-square overflow-hidden rounded-lg border bg-cream-deep">
                    <img src="{{ $item->url }}" alt="{{ $item->titulo }}" class="h-full w-full object-cover">
                </div>
                <p class="text-ink-soft mt-1 truncate text-xs">{{ $item->titulo ?: '(sin título)' }}</p>
                @if ($item->media_album_id)
                    <p class="text-brand-red-ui truncate text-xs font-semibold">{{ $item->album?->nombre }}</p>
                @endif
                <button
                    type="button"
                    wire:click="eliminar({{ $item->id }})"
                    wire:confirm="¿Eliminar esta imagen de la librería? Deja de mostrarse en cualquier sección o álbum que la use."
                    class="absolute top-1 right-1 rounded-full bg-black/60 p-1 text-xs text-white opacity-0 transition-opacity group-hover:opacity-100"
                    title="Eliminar"
                >
                    🗑
                </button>
            </div>
        @empty
            <p class="text-ink-soft col-span-full text-sm">Todavía no hay imágenes en la librería.</p>
        @endforelse
    </div>
</div>
