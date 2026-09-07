<div>
    <div class="flex flex-wrap gap-2">
        @foreach (['contenido' => 'Contenido', 'imagenes' => 'Imágenes', 'faq' => 'FAQ'] as $valor => $etiqueta)
            <button
                type="button"
                wire:click="$set('tab', '{{ $valor }}')"
                class="rounded-lg px-4 py-2 text-sm font-semibold transition-colors {{ $tab === $valor ? 'bg-brand-red-ui text-white' : 'border-line bg-white text-ink-soft hover:bg-cream-deep border' }}"
            >
                {{ $etiqueta }}
            </button>
        @endforeach
    </div>

    <div class="mt-6">
        @if ($tab === 'contenido')
            <livewire:admin.sitio-web.contenido-form />
        @elseif ($tab === 'imagenes')
            <livewire:admin.galeria.galeria-index />
        @else
            <livewire:admin.sitio-web.faq-manager />
        @endif
    </div>
</div>
