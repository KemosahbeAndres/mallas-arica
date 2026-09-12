<div>
    <div class="flex items-center justify-between">
        <h1 class="text-xl font-bold text-ink tracking-tight">Galería</h1>
        <button
            type="button"
            wire:click="nuevo"
            class="rounded-lg bg-brand-red-ui px-4 py-2 text-sm font-semibold text-white hover:bg-brand-red-dark"
        >
            Agregar foto
        </button>
    </div>

    @if ($mostrandoFormulario)
        <div class="mt-3 rounded-lg border border-line bg-white p-4">
            @livewire('admin.galeria.galeria-form', ['editandoId' => $editandoId], key('galeria-form-'.($editandoId ?? 'nuevo')))
        </div>
    @endif

    <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4">
        @forelse ($this->items as $item)
            <div class="rounded-lg border border-line bg-white p-2.5" wire:key="item-{{ $item->id }}">
                <img src="{{ $item->url }}" alt="{{ $item->titulo }}" class="aspect-square w-full rounded-lg object-cover">
                <p class="mt-2 truncate text-sm font-medium text-ink">{{ $item->titulo }}</p>
                <p class="text-xs text-ink-soft">{{ $item->publicado ? 'Publicado' : 'Oculto' }}</p>

                <div class="mt-2 flex flex-wrap gap-2 text-xs">
                    <button type="button" wire:click="moverArriba({{ $item->id }})" class="text-ink-soft hover:text-ink">↑</button>
                    <button type="button" wire:click="moverAbajo({{ $item->id }})" class="text-ink-soft hover:text-ink">↓</button>
                    <button type="button" wire:click="togglePublicado({{ $item->id }})" class="text-brand-red-ui hover:text-brand-red-dark">
                        {{ $item->publicado ? 'Ocultar' : 'Publicar' }}
                    </button>
                    <button type="button" wire:click="editar({{ $item->id }})" class="text-ink-soft hover:text-ink">Editar</button>
                    <button
                        type="button"
                        wire:click="eliminar({{ $item->id }})"
                        wire:confirm="¿Eliminar esta foto?"
                        class="text-brand-red-ui hover:text-brand-red-dark"
                    >
                        Eliminar
                    </button>
                </div>
            </div>
        @empty
            <p class="col-span-full text-center text-ink-soft">Sin fotos todavía.</p>
        @endforelse
    </div>
</div>
