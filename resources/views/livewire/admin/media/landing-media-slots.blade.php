<div class="border-line rounded-xl border bg-white p-5" x-data="{ guardado: false }" x-on:slots-actualizados.window="guardado = true; setTimeout(() => guardado = false, 2500)">
    <h3 class="text-ink font-semibold">Imágenes de la landing</h3>
    <p class="text-ink-soft mt-1 text-sm">
        Elige qué imagen o álbum usa cada sección, y cómo se recorta y posiciona dentro de su recuadro.
    </p>

    <div x-show="guardado" x-cloak class="mt-3 rounded-lg border border-green-200 bg-green-50 px-4 py-2.5 text-sm font-medium text-green-800">
        Cambios guardados. Ya están en vivo en el sitio.
    </div>

    <form wire:submit="guardar" class="mt-4 flex flex-col gap-8">
        @php
            $previews = [
                'hero' => ['label' => 'Hero (imagen principal)', 'tipo' => 'item', 'aspecto' => 'aspect-[4/5]'],
                'nosotros' => ['label' => 'Nosotros', 'tipo' => 'item', 'aspecto' => 'aspect-[4/3]'],
                'galeria-publica' => ['label' => 'Galería pública (álbum)', 'tipo' => 'album', 'aspecto' => 'aspect-square'],
            ];
        @endphp

        @foreach ($previews as $slug => $config)
            @php
                $urlPreview = $config['tipo'] === 'item'
                    ? $this->items->firstWhere('id', $slots[$slug]['media_item_id'])?->url
                    : $this->albumes->firstWhere('id', $slots[$slug]['media_album_id'])?->items->first()?->url;
            @endphp

            <div class="border-line rounded-lg border p-4" wire:key="slot-{{ $slug }}">
                <label class="text-ink-soft text-xs font-semibold uppercase">{{ $config['label'] }}</label>

                <div class="mt-3 grid gap-5 md:grid-cols-[minmax(0,1fr)_16rem]">
                    <div class="flex flex-col gap-4">
                        @if ($config['tipo'] === 'item')
                            <select wire:model.live="slots.{{ $slug }}.media_item_id" class="border-line focus:border-brand-red-ui focus:ring-brand-red-ui/20 block w-full rounded-lg border px-3 py-2 text-sm focus:ring">
                                <option value="">— Sin asignar (usa el placeholder) —</option>
                                @foreach ($this->items as $item)
                                    <option value="{{ $item->id }}">{{ $item->titulo ?: "Imagen #{$item->id}" }}</option>
                                @endforeach
                            </select>
                        @else
                            <select wire:model.live="slots.{{ $slug }}.media_album_id" class="border-line focus:border-brand-red-ui focus:ring-brand-red-ui/20 block w-full rounded-lg border px-3 py-2 text-sm focus:ring">
                                <option value="">— Sin asignar (muestra el mensaje "muy pronto") —</option>
                                @foreach ($this->albumes as $album)
                                    <option value="{{ $album->id }}">{{ $album->nombre }}</option>
                                @endforeach
                            </select>
                        @endif

                        <div>
                            <span class="text-ink-soft text-xs font-semibold uppercase">Encuadre</span>
                            <div class="mt-1.5 flex gap-2">
                                @foreach (['cover' => 'Cubrir', 'contain' => 'Contener', 'fill' => 'Estirar'] as $valor => $etiqueta)
                                    <label class="border-line has-checked:border-brand-red-ui has-checked:bg-brand-red-ui/10 has-checked:text-brand-red-ui text-ink-soft flex-1 cursor-pointer rounded-lg border px-3 py-2 text-center text-sm font-medium">
                                        <input type="radio" wire:model.live="slots.{{ $slug }}.encuadre" value="{{ $valor }}" class="sr-only">
                                        {{ $etiqueta }}
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <div>
                            <span class="text-ink-soft text-xs font-semibold uppercase">Posición</span>
                            <div class="mt-1.5 flex flex-wrap gap-2">
                                @foreach (['centro' => 'Centro', 'arriba' => 'Arriba', 'abajo' => 'Abajo', 'izquierda' => 'Izquierda', 'derecha' => 'Derecha'] as $preset => $etiqueta)
                                    <button
                                        type="button"
                                        wire:click="fijarPosicion('{{ $slug }}', '{{ $preset }}')"
                                        class="border-line text-ink-soft hover:border-brand-red-ui hover:text-brand-red-ui rounded-lg border px-3 py-1.5 text-xs font-medium transition-colors"
                                    >
                                        {{ $etiqueta }}
                                    </button>
                                @endforeach
                            </div>

                            <div class="mt-3 flex flex-col gap-2">
                                <label class="text-ink-soft flex items-center gap-2 text-xs">
                                    <span class="w-20 shrink-0">Horizontal</span>
                                    <input type="range" min="0" max="100" wire:model.live.debounce.200ms="slots.{{ $slug }}.posicion_x" class="w-full">
                                    <span class="w-10 shrink-0 text-right">{{ $slots[$slug]['posicion_x'] }}%</span>
                                </label>
                                <label class="text-ink-soft flex items-center gap-2 text-xs">
                                    <span class="w-20 shrink-0">Vertical</span>
                                    <input type="range" min="0" max="100" wire:model.live.debounce.200ms="slots.{{ $slug }}.posicion_y" class="w-full">
                                    <span class="w-10 shrink-0 text-right">{{ $slots[$slug]['posicion_y'] }}%</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="border-line {{ $config['aspecto'] }} bg-cream-deep w-full overflow-hidden rounded-lg border">
                        @if ($urlPreview)
                            <img
                                src="{{ $urlPreview }}"
                                alt="Vista previa"
                                class="h-full w-full"
                                style="object-fit: {{ $slots[$slug]['encuadre'] }}; object-position: {{ $slots[$slug]['posicion_x'] }}% {{ $slots[$slug]['posicion_y'] }}%;"
                            >
                        @else
                            <div class="text-ink-soft/60 flex h-full w-full items-center justify-center text-xs">Sin imagen</div>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach

        <div>
            <button type="submit" class="bg-brand-red-ui hover:bg-brand-red-dark rounded-lg px-5 py-2.5 text-sm font-semibold text-white transition-colors">
                Guardar asignaciones
            </button>
        </div>
    </form>
</div>
