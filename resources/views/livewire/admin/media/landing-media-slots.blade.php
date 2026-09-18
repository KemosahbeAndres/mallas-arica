<div class="border-line rounded-xl border bg-white p-5" x-data="{ guardado: false }" x-on:slots-actualizados.window="guardado = true; setTimeout(() => guardado = false, 2500)">
    <h3 class="text-ink font-semibold">Imágenes de la landing</h3>
    <p class="text-ink-soft mt-1 text-sm">
        Elige qué imagen o álbum de la librería usa cada sección del sitio.
    </p>

    <div x-show="guardado" x-cloak class="mt-3 rounded-lg border border-green-200 bg-green-50 px-4 py-2.5 text-sm font-medium text-green-800">
        Cambios guardados. Ya están en vivo en el sitio.
    </div>

    <form wire:submit="guardar" class="mt-4 flex flex-col gap-5">
        <div>
            <label class="text-ink-soft text-xs font-semibold uppercase">Hero (imagen principal)</label>
            <select wire:model="heroMediaItemId" class="border-line focus:border-brand-red-ui focus:ring-brand-red-ui/20 mt-1 block w-full max-w-sm rounded-lg border px-3 py-2 text-sm focus:ring">
                <option value="">— Sin asignar (usa el placeholder) —</option>
                @foreach ($this->items as $item)
                    <option value="{{ $item->id }}">{{ $item->titulo ?: "Imagen #{$item->id}" }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="text-ink-soft text-xs font-semibold uppercase">Nosotros</label>
            <select wire:model="nosotrosMediaItemId" class="border-line focus:border-brand-red-ui focus:ring-brand-red-ui/20 mt-1 block w-full max-w-sm rounded-lg border px-3 py-2 text-sm focus:ring">
                <option value="">— Sin asignar (usa el placeholder) —</option>
                @foreach ($this->items as $item)
                    <option value="{{ $item->id }}">{{ $item->titulo ?: "Imagen #{$item->id}" }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="text-ink-soft text-xs font-semibold uppercase">Galería pública (álbum)</label>
            <select wire:model="galeriaMediaAlbumId" class="border-line focus:border-brand-red-ui focus:ring-brand-red-ui/20 mt-1 block w-full max-w-sm rounded-lg border px-3 py-2 text-sm focus:ring">
                <option value="">— Sin asignar (muestra el mensaje "muy pronto") —</option>
                @foreach ($this->albumes as $album)
                    <option value="{{ $album->id }}">{{ $album->nombre }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <button type="submit" class="bg-brand-red-ui hover:bg-brand-red-dark rounded-lg px-5 py-2.5 text-sm font-semibold text-white transition-colors">
                Guardar asignaciones
            </button>
        </div>
    </form>
</div>
