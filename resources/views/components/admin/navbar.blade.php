@php
    $links = [
        ['route' => 'admin.resumen', 'label' => 'Resumen', 'pattern' => 'admin.resumen'],
        ['route' => 'admin.cotizaciones', 'label' => 'Cotizaciones', 'pattern' => 'admin.cotizaciones'],
        ['route' => 'admin.clientes', 'label' => 'Clientes', 'pattern' => 'admin.clientes'],
        ['route' => 'admin.calendario', 'label' => 'Calendario', 'pattern' => 'admin.calendario'],
        ['route' => 'admin.sitio-web', 'label' => 'Sitio web', 'pattern' => 'admin.sitio-web'],
    ];
@endphp

<header class="bg-ink">
    <div class="mx-auto flex max-w-7xl items-center justify-between gap-6 px-6 py-3">
        <div class="flex items-center gap-8">
            <div class="flex items-center gap-2.5">
                <span class="relative inline-block h-6 w-6 shrink-0">
                    <span class="absolute left-0 top-0 h-4 w-4 rounded-[5px] bg-white"></span>
                    <span class="bg-brand-red absolute bottom-0 right-0 h-4 w-4 rounded-[5px]"></span>
                </span>
                <span class="leading-none">
                    <span class="block text-sm font-bold tracking-tight text-white">Mallas Arica</span>
                    <span class="text-cream-deep/60 block text-[10px] font-medium tracking-[0.2em]">JACOB</span>
                </span>
            </div>

            <nav class="hidden items-center gap-1 md:flex">
                @foreach ($links as $link)
                    <a
                        href="{{ route($link['route']) }}"
                        wire:navigate
                        class="rounded-lg px-3 py-1.5 text-sm font-medium transition-colors {{ request()->routeIs($link['pattern']) ? 'bg-brand-red-ui text-white' : 'text-cream-deep/80 hover:bg-white/10 hover:text-white' }}"
                    >
                        {{ $link['label'] }}
                    </a>
                @endforeach
            </nav>
        </div>

        <div class="flex items-center gap-4">
            <a
                href="{{ route('home') }}"
                target="_blank"
                rel="noopener"
                class="hidden rounded-lg border border-white/15 px-3 py-1.5 text-xs font-medium text-white transition-colors hover:bg-white/10 sm:inline-block"
            >
                Ver sitio ↗
            </a>

            <div class="flex items-center gap-2.5">
                <span class="bg-brand-red-ui flex h-8 w-8 items-center justify-center rounded-full text-sm font-bold text-white">
                    {{ mb_substr(auth()->user()->name, 0, 1) }}
                </span>
                <span class="hidden leading-tight sm:block">
                    <span class="block text-xs font-semibold text-white">{{ auth()->user()->name }}</span>
                    <span class="text-cream-deep/60 block text-[10px]">Administrador</span>
                </span>
                <form method="POST" action="{{ route('admin.logout') }}" class="ml-1">
                    @csrf
                    <button type="submit" class="text-cream-deep/60 text-xs hover:text-white">Salir</button>
                </form>
            </div>
        </div>
    </div>

    {{-- Nav móvil --}}
    <nav class="flex items-center gap-1 overflow-x-auto border-t border-white/10 px-4 py-2 md:hidden">
        @foreach ($links as $link)
            <a
                href="{{ route($link['route']) }}"
                wire:navigate
                class="shrink-0 rounded-lg px-3 py-1.5 text-sm font-medium transition-colors {{ request()->routeIs($link['pattern']) ? 'bg-brand-red-ui text-white' : 'text-cream-deep/80 hover:bg-white/10' }}"
            >
                {{ $link['label'] }}
            </a>
        @endforeach
    </nav>
</header>
