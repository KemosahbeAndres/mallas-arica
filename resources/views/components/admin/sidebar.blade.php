@php
    $links = [
        ['route' => 'admin.tarifas', 'active' => request()->routeIs('admin.tarifas'), 'label' => 'Tarifas'],
        ['route' => 'admin.leads.index', 'active' => request()->routeIs('admin.leads.*'), 'label' => 'Leads'],
        ['route' => 'admin.galeria', 'active' => request()->routeIs('admin.galeria'), 'label' => 'Galería'],
    ];
@endphp

<aside class="w-56 shrink-0 border-r border-line bg-ink">
    <div class="px-5 py-5">
        <p class="text-lg font-bold text-white tracking-tight">Mallas Arica</p>
        <p class="text-xs text-cream-deep/70">Panel admin</p>
    </div>

    <nav class="mt-2 flex flex-col gap-1 px-3">
        @foreach ($links as $link)
            <a
                href="{{ route($link['route']) }}"
                class="rounded-lg px-3 py-2 text-sm font-medium transition-colors {{ $link['active'] ? 'bg-brand-red-ui text-white' : 'text-cream-deep hover:bg-white/10' }}"
            >
                {{ $link['label'] }}
            </a>
        @endforeach
    </nav>
</aside>
