@php
    use App\Support\FechaEsp;
    $estadoBadge = [
        'pendiente' => 'bg-cream-deep text-ink-soft',
        'en_curso' => 'bg-amber-100 text-amber-800',
        'ejecutada' => 'bg-green-100 text-green-800',
        'cancelada' => 'bg-gray-100 text-gray-400',
    ];
    $esColaborador = auth()->user()->esColaborador();
    $estadosDisponibles = $esColaborador ? ['en_curso', 'ejecutada'] : \App\Models\Trabajo::ESTADOS;
    $minimo = $this->minimoFotos;
    $total = $this->totalFotos;
@endphp

<div class="mx-auto flex max-w-2xl flex-col gap-4">
    @if ($flash)
        <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-2.5 text-sm font-medium text-green-800">
            {{ $flash }}
        </div>
    @endif

    <div class="border-line rounded-lg border bg-white px-4 py-4">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <div>
                <p class="text-ink text-base font-semibold">{{ $trabajo->titulo }}</p>
                <p class="text-ink-soft text-sm">
                    {{ $trabajo->cliente?->nombre ?? 'Sin cliente' }}
                    @if ($trabajo->clienteDireccion) · {{ $trabajo->clienteDireccion->direccion }} @endif
                </p>
                @if ($trabajo->evento)
                    <p class="text-ink-soft text-xs">Agendado · {{ FechaEsp::corto($trabajo->evento->inicio) }}</p>
                @endif
            </div>
            <span class="rounded-full px-2 py-0.5 text-xs font-semibold {{ $estadoBadge[$trabajo->estado] ?? '' }}">
                {{ str_replace('_', ' ', ucfirst($trabajo->estado)) }}
            </span>
        </div>

        @if ($trabajo->colaboradores->isNotEmpty())
            <p class="text-ink-soft mt-3 text-xs">
                Colaboradores asignados: {{ $trabajo->colaboradores->pluck('name')->join(', ') }}
            </p>
        @endif

        <div class="border-line mt-4 flex flex-wrap gap-2 border-t pt-4">
            @foreach ($estadosDisponibles as $estado)
                <button type="button" wire:click="cambiarEstado('{{ $estado }}')"
                    @disabled($trabajo->estado === $estado)
                    class="rounded-lg border px-3 py-1.5 text-xs font-semibold transition-colors disabled:cursor-not-allowed disabled:opacity-40 {{ $trabajo->estado === $estado ? 'border-brand-red-ui bg-brand-red-ui/10 text-brand-red-ui' : 'border-line hover:bg-cream-deep' }}">
                    {{ str_replace('_', ' ', ucfirst($estado)) }}
                </button>
            @endforeach
        </div>
        @error('estado') <p class="mt-2 text-xs text-red-600">{{ $message }}</p> @enderror
    </div>

    <div class="border-line rounded-lg border bg-white px-4 py-4">
        <div class="flex items-center justify-between">
            <h3 class="text-ink-soft text-sm font-bold tracking-wide uppercase">Fotos</h3>
            <span class="text-ink-soft text-xs font-semibold">{{ $total }}/{{ $minimo }} fotos mínimas</span>
        </div>

        <form wire:submit="subirFotos" class="mt-3 flex flex-col gap-2">
            <input type="file" wire:model="fotosNuevas" multiple accept="image/*"
                class="border-line w-full rounded-lg border px-3 py-2 text-sm">
            @error('fotosNuevas') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
            @error('fotosNuevas.*') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
            <button type="submit"
                class="bg-brand-red-ui hover:bg-brand-red-dark self-start rounded-lg px-4 py-2 text-xs font-semibold text-white"
                wire:loading.attr="disabled" wire:target="subirFotos,fotosNuevas">
                <span wire:loading.remove wire:target="subirFotos">Subir fotos</span>
                <span wire:loading wire:target="subirFotos">Subiendo…</span>
            </button>
        </form>

        <div class="mt-4 grid grid-cols-3 gap-2 sm:grid-cols-4">
            @forelse ($trabajo->fotos as $foto)
                <div class="group relative" wire:key="foto-{{ $foto->id }}">
                    <img src="{{ $foto->url }}" alt="" class="aspect-square w-full rounded-lg object-cover">
                    @if (auth()->user()->puedeEliminar())
                        <button type="button" wire:click="eliminarFoto({{ $foto->id }})" wire:confirm="¿Eliminar esta foto?"
                            class="absolute right-1 top-1 rounded-full bg-black/60 px-1.5 py-0.5 text-xs text-white opacity-0 transition-opacity group-hover:opacity-100">
                            ✕
                        </button>
                    @endif
                </div>
            @empty
                <p class="text-ink-soft col-span-full text-sm">Todavía no hay fotos.</p>
            @endforelse
        </div>
    </div>
</div>
