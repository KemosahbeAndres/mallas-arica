@php
    $clp = fn ($v) => '$'.number_format((int) $v, 0, ',', '.');
    $badge = [
        'borrador' => 'bg-cream-deep text-ink-soft',
        'generada' => 'bg-amber-100 text-amber-800',
        'aceptada' => 'bg-green-100 text-green-800',
        'rechazada' => 'bg-red-100 text-red-700',
    ];
@endphp

<div class="flex flex-col gap-5">
    @if ($flash)
        <div class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-800">
            {{ $flash }}
        </div>
    @endif

    <div class="flex flex-wrap items-center justify-between gap-3">
        <a href="{{ route('admin.cotizaciones.nueva') }}" wire:navigate
            class="bg-brand-red-ui hover:bg-brand-red-dark rounded-lg px-4 py-2 text-sm font-semibold text-white transition-colors">
            + Nueva cotización
        </a>

        <select wire:model.live="estadoFiltro" class="border-line rounded-lg border px-3 py-2 text-sm">
            <option value="">Todos los estados</option>
            @foreach (\App\Models\Cotizacion::ESTADOS as $e)
                <option value="{{ $e }}">{{ ucfirst($e) }}</option>
            @endforeach
        </select>
    </div>

    <div class="grid gap-6 lg:grid-cols-[1fr_22rem]">
        <div class="border-line overflow-hidden rounded-xl border bg-white">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-ink text-white">
                        <th class="px-4 py-3 text-left font-semibold">Folio</th>
                        <th class="px-4 py-3 text-left font-semibold">Cliente</th>
                        <th class="px-4 py-3 text-right font-semibold">Total</th>
                        <th class="px-4 py-3 text-left font-semibold">Fecha</th>
                        <th class="px-4 py-3 text-left font-semibold">Estado</th>
                    </tr>
                </thead>
                <tbody class="divide-line divide-y">
                    @forelse ($this->cotizaciones as $cot)
                        <tr wire:key="cot-{{ $cot->id }}"
                            wire:click="seleccionar({{ $cot->id }})"
                            class="cursor-pointer transition-colors {{ $seleccionada === $cot->id ? 'bg-cream-deep' : 'hover:bg-cream-deep/60' }}">
                            <td class="text-ink-soft px-4 py-3 font-mono">#{{ $cot->folio }}</td>
                            <td class="text-ink px-4 py-3 font-medium">{{ $cot->cliente?->nombre ?? $cot->nombre ?? '—' }}</td>
                            <td class="text-ink px-4 py-3 text-right">{{ $clp($cot->total) }}</td>
                            <td class="text-ink-soft px-4 py-3">{{ $cot->created_at->diffForHumans(['short' => true]) }}</td>
                            <td class="px-4 py-3">
                                <span class="rounded-full px-2 py-0.5 text-xs font-semibold {{ $badge[$cot->estado] ?? 'bg-cream-deep text-ink-soft' }}">
                                    {{ ucfirst($cot->estado) }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-ink-soft px-4 py-8 text-center">
                            {{ $estadoFiltro ? 'Sin cotizaciones en ese estado.' : 'Todavía no hay cotizaciones.' }}
                        </td></tr>
                    @endforelse
                </tbody>
            </table>

            <div class="border-line border-t px-4 py-3">
                {{ $this->cotizaciones->links() }}
            </div>
        </div>

        {{-- Vista previa --}}
        <div class="border-line rounded-2xl border bg-white p-5">
            @if ($this->detalle)
                @php $d = $this->detalle; @endphp
                <div class="flex items-start justify-between gap-2">
                    <div>
                        <p class="text-ink-soft text-xs font-semibold tracking-wide uppercase">Cotización #{{ $d->folio }}</p>
                        <p class="text-ink mt-1 text-lg font-bold">{{ $d->cliente?->nombre ?? $d->nombre ?? '—' }}</p>
                        @if ($d->cliente?->telefono ?? $d->telefono)
                            <p class="text-ink-soft text-sm">{{ $d->cliente?->telefono ?? $d->telefono }}</p>
                        @endif
                    </div>
                    <span class="rounded-full px-2 py-0.5 text-xs font-semibold {{ $badge[$d->estado] ?? '' }}">{{ ucfirst($d->estado) }}</span>
                </div>

                @if ($d->clienteDireccion)
                    <p class="text-ink-soft mt-3 text-sm">📍 {{ $d->clienteDireccion->direccion }}</p>
                @endif

                <div class="border-line divide-line mt-4 divide-y border-t border-b py-2">
                    @forelse ($d->items as $item)
                        <div class="flex items-center justify-between gap-3 py-2 text-sm">
                            <span class="text-ink-soft">{{ $item->descripcion }} <span class="text-ink-soft/60">×{{ rtrim(rtrim(number_format($item->cantidad, 2), '0'), '.') }}</span></span>
                            <span class="text-ink shrink-0">{{ $clp($item->subtotal) }}</span>
                        </div>
                    @empty
                        <p class="text-ink-soft py-2 text-sm">Sin ítems.</p>
                    @endforelse
                </div>

                <div class="mt-3 flex items-center justify-between">
                    <span class="text-ink font-bold">Total</span>
                    <span class="text-brand-red-ui text-lg font-bold">{{ $clp($d->total) }}</span>
                </div>

                <p class="text-ink-soft mt-5 text-xs font-semibold tracking-wide uppercase">Cambiar estado</p>
                <div class="mt-2 grid grid-cols-2 gap-2">
                    @foreach (\App\Models\Cotizacion::ESTADOS as $e)
                        <button type="button" wire:click="cambiarEstado('{{ $e }}')"
                            class="rounded-lg px-3 py-2 text-sm font-semibold transition-colors
                                {{ $d->estado === $e ? 'bg-brand-red-dark text-white' : 'border-line bg-white text-ink-soft hover:bg-cream-deep border' }}">
                            {{ ucfirst($e) }}
                        </button>
                    @endforeach
                </div>

                @if ($d->trabajo)
                    <p class="mt-3 rounded-lg bg-green-50 px-3 py-2 text-xs text-green-800">
                        OT creada · estado {{ $d->trabajo->estado }}
                    </p>
                @endif

                <div class="mt-4 flex flex-col gap-2">
                    <a href="{{ route('admin.cotizaciones.pdf', $d) }}"
                        class="border-line hover:bg-cream-deep rounded-lg border px-3 py-2 text-center text-sm font-semibold">
                        Descargar PDF
                    </a>
                    <div class="flex gap-2">
                        <a href="{{ route('admin.cotizaciones.editar', $d) }}" wire:navigate
                            class="border-line hover:bg-cream-deep flex-1 rounded-lg border px-3 py-2 text-center text-sm font-semibold">
                            Editar
                        </a>
                        <button type="button" wire:click="eliminar" wire:confirm="¿Eliminar esta cotización?"
                            class="text-ink-soft rounded-lg px-3 py-2 text-sm hover:bg-red-50 hover:text-red-600">
                            Eliminar
                        </button>
                    </div>
                </div>
            @else
                <p class="text-ink-soft py-8 text-center text-sm">Selecciona una cotización para ver su vista previa.</p>
            @endif
        </div>
    </div>
</div>
