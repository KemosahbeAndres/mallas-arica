<div>
    <h2 class="text-sm font-bold uppercase tracking-wide text-brand-red-ui">
        {{ $itemId ? 'Editar foto' : 'Nueva foto' }}
    </h2>

    <form wire:submit="guardar" class="mt-4 space-y-4">
        <div>
            <label for="titulo" class="block text-sm font-medium text-ink">Título</label>
            <input
                type="text"
                id="titulo"
                wire:model="titulo"
                class="mt-1 w-full rounded-lg border border-line px-3 py-2 text-sm"
            >
            @error('titulo') <p class="mt-1 text-sm text-brand-red-dark">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="foto" class="block text-sm font-medium text-ink">Foto</label>
            <input type="file" id="foto" wire:model="foto" class="mt-1 w-full text-sm">
            @error('foto') <p class="mt-1 text-sm text-brand-red-dark">{{ $message }}</p> @enderror
            @if ($foto && method_exists($foto, 'isPreviewable') && $foto->isPreviewable())
                <img src="{{ $foto->temporaryUrl() }}" class="mt-2 h-24 w-24 rounded-lg object-cover">
            @endif
        </div>

        <label class="flex items-center gap-2 text-sm text-ink-soft">
            <input type="checkbox" wire:model="publicado">
            Publicado
        </label>

        <button type="submit" class="rounded-lg bg-brand-red-ui px-4 py-2 text-sm font-semibold text-white hover:bg-brand-red-dark">
            Guardar
        </button>
    </form>
</div>
