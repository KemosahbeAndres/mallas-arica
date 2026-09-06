<div>
    <h1 class="text-xl font-bold text-ink tracking-tight">Leads</h1>

    <div class="mt-4">
        <select wire:model.live="estado" class="rounded-lg border border-line px-3 py-2 text-sm">
            <option value="">Todos los estados</option>
            @foreach (\App\Models\Cotizacion::ESTADOS as $valor)
                <option value="{{ $valor }}">{{ ucfirst($valor) }}</option>
            @endforeach
        </select>
    </div>

    <div class="mt-6 overflow-x-auto rounded-xl border border-line bg-white">
        <table class="w-full border-collapse text-sm">
            <thead>
                <tr class="bg-ink text-white">
                    <th class="px-4 py-3 text-left">N°</th>
                    <th class="px-4 py-3 text-left">Nombre</th>
                    <th class="px-4 py-3 text-left">Teléfono</th>
                    <th class="px-4 py-3 text-left">Estado</th>
                    <th class="px-4 py-3 text-left">Fecha</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($this->cotizaciones as $cotizacion)
                    <tr class="border-b border-line" wire:key="lead-{{ $cotizacion->id }}">
                        <td class="px-4 py-3 font-mono text-ink-soft">{{ $cotizacion->numero }}</td>
                        <td class="px-4 py-3 text-ink">{{ $cotizacion->nombre }}</td>
                        <td class="px-4 py-3 text-ink-soft">{{ $cotizacion->telefono }}</td>
                        <td class="px-4 py-3 text-ink-soft">{{ ucfirst($cotizacion->estado) }}</td>
                        <td class="px-4 py-3 text-ink-soft">{{ $cotizacion->created_at->format('d-m-Y') }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('admin.leads.show', $cotizacion) }}" class="text-brand-red-ui hover:text-brand-red-dark">
                                Ver
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-6 text-center text-ink-soft">Sin leads todavía.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $this->cotizaciones->links() }}
    </div>
</div>
