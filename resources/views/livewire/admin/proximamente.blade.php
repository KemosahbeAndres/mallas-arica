<div class="border-line flex flex-col items-center rounded-2xl border border-dashed bg-white px-6 py-16 text-center">
    <span class="bg-cream-deep flex h-14 w-14 items-center justify-center rounded-full text-2xl">🚧</span>
    <h2 class="text-ink mt-4 text-lg font-bold tracking-tight">{{ $seccion }} — próximamente</h2>
    <p class="text-ink-soft mt-2 max-w-md text-sm">
        {{ $detalle ?: 'Esta parte del panel todavía no está disponible. Se habilita en una próxima entrega del CRM.' }}
    </p>

    <div class="mt-6 flex flex-wrap justify-center gap-3">
        <a href="{{ route('admin.sitio-web') }}" wire:navigate class="bg-brand-red-ui hover:bg-brand-red-dark rounded-lg px-4 py-2 text-sm font-semibold text-white transition-colors">
            Ir a Sitio web
        </a>
        <a href="{{ route('admin.tarifas') }}" wire:navigate class="border-line text-ink-soft hover:bg-cream-deep rounded-lg border px-4 py-2 text-sm font-semibold transition-colors">
            Ir a Tarifas
        </a>
    </div>
</div>
