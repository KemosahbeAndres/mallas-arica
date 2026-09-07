<div>
    <a href="{{ route('admin.leads.index') }}" class="text-sm text-brand-red-ui hover:text-brand-red-dark">← Volver a leads</a>

    <h1 class="mt-2 text-xl font-bold text-ink tracking-tight">Cotización {{ $cotizacion->numero }}</h1>

    <div class="mt-6 grid grid-cols-1 gap-6 md:grid-cols-2">
        <div class="rounded-xl border border-line bg-white p-5">
            <p class="text-xs font-bold uppercase tracking-wide text-brand-red-ui">Cliente</p>
            <p class="mt-2 font-medium text-ink">{{ $cotizacion->nombre }}</p>
            <p class="text-sm text-ink-soft">{{ $cotizacion->telefono }}</p>
            @if ($cotizacion->email)
                <p class="text-sm text-ink-soft">{{ $cotizacion->email }}</p>
            @endif
            @if ($cotizacion->direccion)
                <p class="text-sm text-ink-soft">{{ $cotizacion->direccion }}</p>
            @endif
            @if ($cotizacion->requiere_visita)
                <p class="mt-3 inline-block rounded bg-cream-deep px-2 py-1 text-xs text-brand-red-dark">Requiere visita técnica</p>
            @endif
        </div>

        <div class="rounded-xl border border-line bg-white p-5">
            <p class="text-xs font-bold uppercase tracking-wide text-brand-red-ui">Estado</p>
            <select
                wire:change="cambiarEstado($event.target.value)"
                class="mt-2 w-full rounded-lg border border-line px-3 py-2 text-sm"
            >
                @foreach (\App\Models\Cotizacion::ESTADOS as $valor)
                    <option value="{{ $valor }}" @selected($cotizacion->estado === $valor)>{{ ucfirst($valor) }}</option>
                @endforeach
            </select>

            @if ($cotizacion->total_min || $cotizacion->total_max)
                <p class="mt-4 text-sm text-ink-soft">
                    Rango: <span class="font-semibold text-ink">${{ number_format($cotizacion->total_min, 0, ',', '.') }} – ${{ number_format($cotizacion->total_max, 0, ',', '.') }}</span>
                </p>
            @endif
        </div>
    </div>

    @if ($cotizacion->items->isNotEmpty())
        <div class="mt-6 overflow-x-auto rounded-xl border border-line bg-white">
            <table class="w-full border-collapse text-sm">
                <thead>
                    <tr class="bg-ink text-white">
                        <th class="px-4 py-3 text-left">Espacio</th>
                        <th class="px-4 py-3 text-left">Malla</th>
                        <th class="px-4 py-3 text-left">Tramo</th>
                        <th class="px-4 py-3 text-right">Metros</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($cotizacion->items as $item)
                        <tr class="border-b border-line">
                            <td class="px-4 py-3 text-ink">{{ $item->tipoEspacio?->nombre }}</td>
                            <td class="px-4 py-3 text-ink-soft">{{ $item->tipoMalla?->nombre ?? '—' }}</td>
                            <td class="px-4 py-3 text-ink-soft">{{ $item->tramoAltura?->etiqueta ?? '—' }}</td>
                            <td class="px-4 py-3 text-right text-ink-soft">{{ number_format((float) $item->metros_lineales, 1, ',', '.') }} ml</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
