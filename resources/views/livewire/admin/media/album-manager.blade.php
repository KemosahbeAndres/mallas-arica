<div class="border-line mt-6 rounded-xl border bg-white p-5">
    <h3 class="text-ink font-semibold">Álbumes</h3>
    <p class="text-ink-soft mt-1 text-sm">
        Agrupa imágenes de la librería en álbumes. Una sección de la landing con varias imágenes (como la Galería pública) muestra el álbum que le asignes abajo.
    </p>

    <form wire:submit="crear" class="mt-4 flex gap-3">
        <input
            type="text"
            wire:model="nombreNuevoAlbum"
            placeholder="Nombre del álbum nuevo"
            class="border-line focus:border-brand-red-ui focus:ring-brand-red-ui/20 flex-1 rounded-lg border px-3 py-2 text-sm focus:ring"
        >
        <button type="submit" class="bg-brand-red-ui hover:bg-brand-red-dark shrink-0 rounded-lg px-5 py-2.5 text-sm font-semibold text-white transition-colors">
            Crear álbum
        </button>
    </form>
    @error('nombreNuevoAlbum')
        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
    @enderror

    <div class="mt-5 flex flex-col gap-3">
        @forelse ($this->albumes as $album)
            <div wire:key="album-{{ $album->id }}" class="border-line rounded-lg border">
                <div class="flex items-center justify-between px-4 py-3">
                    <button type="button" wire:click="abrir({{ $album->id }})" class="text-ink text-left font-semibold">
                        {{ $album->nombre }}
                        <span class="text-ink-soft font-normal">({{ $album->items->count() }} imágenes)</span>
                    </button>

                    <button
                        type="button"
                        wire:click="eliminar({{ $album->id }})"
                        wire:confirm="¿Eliminar el álbum '{{ $album->nombre }}'? Las imágenes no se borran, quedan sueltas en la librería."
                        class="text-brand-red-ui hover:text-brand-red-dark text-xs font-semibold"
                    >
                        Eliminar álbum
                    </button>
                </div>

                @if ($albumAbiertoId === $album->id)
                    <div class="border-line border-t p-4">
                        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4 lg:grid-cols-6">
                            @forelse ($album->items as $item)
                                <div wire:key="album-item-{{ $item->id }}">
                                    <div class="border-line aspect-square overflow-hidden rounded-lg border bg-cream-deep">
                                        <img src="{{ $item->url }}" alt="{{ $item->titulo }}" class="h-full w-full object-cover">
                                    </div>
                                    <p class="text-ink-soft mt-1 truncate text-xs">{{ $item->titulo ?: '(sin título)' }}</p>
                                    <div class="mt-1 flex flex-wrap gap-2 text-xs">
                                        <button type="button" wire:click="moverArriba({{ $item->id }})" class="text-ink-soft hover:text-ink">↑</button>
                                        <button type="button" wire:click="moverAbajo({{ $item->id }})" class="text-ink-soft hover:text-ink">↓</button>
                                        <button type="button" wire:click="quitarItem({{ $item->id }})" class="text-brand-red-ui hover:text-brand-red-dark">
                                            Quitar
                                        </button>
                                    </div>
                                </div>
                            @empty
                                <p class="text-ink-soft col-span-full text-sm">Este álbum no tiene imágenes todavía.</p>
                            @endforelse
                        </div>

                        @if ($this->itemsSinAlbum->isNotEmpty())
                            <div class="border-line mt-4 border-t pt-4">
                                <p class="text-ink-soft text-xs font-semibold uppercase">Agregar desde la librería</p>
                                <div class="mt-2 grid grid-cols-2 gap-3 sm:grid-cols-4 lg:grid-cols-6">
                                    @foreach ($this->itemsSinAlbum as $item)
                                        <button
                                            type="button"
                                            wire:key="disponible-{{ $item->id }}"
                                            wire:click="agregarItem({{ $album->id }}, {{ $item->id }})"
                                            class="group text-left"
                                        >
                                            <div class="border-line aspect-square overflow-hidden rounded-lg border bg-cream-deep transition-opacity group-hover:opacity-75">
                                                <img src="{{ $item->url }}" alt="{{ $item->titulo }}" class="h-full w-full object-cover">
                                            </div>
                                            <p class="text-ink-soft mt-1 truncate text-xs">{{ $item->titulo ?: '(sin título)' }}</p>
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        @empty
            <p class="text-ink-soft text-sm">Todavía no hay álbumes. Crea el primero arriba.</p>
        @endforelse
    </div>
</div>
