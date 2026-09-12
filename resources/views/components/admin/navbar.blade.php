@php
    $links = [
        ['route' => 'admin.resumen', 'label' => 'Resumen', 'pattern' => 'admin.resumen'],
        [
            'label' => 'Cotizar',
            'pattern' => 'admin.cotizaciones*|admin.clientes',
            'children' => [
                ['route' => 'admin.cotizaciones', 'label' => 'Cotizaciones', 'pattern' => 'admin.cotizaciones*', 'params' => []],
                ['route' => 'admin.clientes', 'label' => 'Clientes', 'pattern' => 'admin.clientes', 'params' => []],
            ],
        ],
        ['route' => 'admin.calendario', 'label' => 'Calendario', 'pattern' => 'admin.calendario'],
        ['route' => 'admin.trabajos', 'label' => 'Trabajos', 'pattern' => 'admin.trabajos'],
        [
            'label' => 'Sitio web',
            'pattern' => 'admin.sitio-web',
            'children' => [
                ['route' => 'admin.sitio-web', 'params' => ['tab' => 'contenido'], 'label' => 'Contenido', 'tab' => 'contenido'],
                ['route' => 'admin.sitio-web', 'params' => ['tab' => 'imagenes'], 'label' => 'Imágenes', 'tab' => 'imagenes'],
                ['route' => 'admin.sitio-web', 'params' => ['tab' => 'faq'], 'label' => 'FAQ', 'tab' => 'faq'],
            ],
        ],
    ];

    $activo = fn (?string $pattern) => $pattern && request()->routeIs(...explode('|', $pattern));

    $childActivo = function (array $child) use ($activo) {
        if (isset($child['tab'])) {
            return request()->routeIs('admin.sitio-web') && request()->query('tab', 'contenido') === $child['tab'];
        }

        return $activo($child['pattern']);
    };
@endphp

<header class="bg-ink relative">
    <div class="flex items-center justify-between gap-6 px-6 py-3">
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
                    @if (isset($link['children']))
                        <div x-data="{ open: false }" class="relative" x-on:click.outside="open = false">
                            <button
                                type="button"
                                x-on:click="open = !open"
                                class="flex items-center gap-1 rounded-lg px-3 py-1.5 text-sm font-medium transition-colors {{ $activo($link['pattern']) ? 'bg-brand-red-ui text-white' : 'text-cream-deep/80 hover:bg-white/10 hover:text-white' }}"
                            >
                                {{ $link['label'] }}
                                <svg x-bind:class="open ? 'rotate-180' : ''" class="h-3 w-3 shrink-0 transition-transform" viewBox="0 0 12 12" fill="none">
                                    <path d="M2.5 4.5L6 8l3.5-3.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </button>

                            <div
                                x-show="open"
                                x-cloak
                                x-transition:enter="transition ease-out duration-150"
                                x-transition:enter-start="opacity-0 -translate-y-1"
                                x-transition:enter-end="opacity-100 translate-y-0"
                                x-on:click="open = false"
                                class="border-line absolute left-0 top-full z-20 mt-1 min-w-[10rem] rounded-lg border bg-white p-1.5 shadow-lg"
                            >
                                @foreach ($link['children'] as $child)
                                    <a
                                        href="{{ route($child['route'], $child['params'] ?? []) }}"
                                        wire:navigate
                                        class="block rounded-md px-3 py-2 text-sm font-medium transition-colors {{ $childActivo($child) ? 'bg-brand-red-ui/10 text-brand-red-ui' : 'text-ink-soft hover:bg-cream-deep' }}"
                                    >
                                        {{ $child['label'] }}
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @else
                        <a
                            href="{{ route($link['route']) }}"
                            wire:navigate
                            class="rounded-lg px-3 py-1.5 text-sm font-medium transition-colors {{ $activo($link['pattern']) ? 'bg-brand-red-ui text-white' : 'text-cream-deep/80 hover:bg-white/10 hover:text-white' }}"
                        >
                            {{ $link['label'] }}
                        </a>
                    @endif
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

    {{-- Nav móvil: los grupos se aplanan (sin dropdown, todo en línea con scroll horizontal) --}}
    <nav class="flex items-center gap-1 overflow-x-auto border-t border-white/10 px-4 py-2 md:hidden">
        @foreach ($links as $link)
            @if (isset($link['children']))
                @foreach ($link['children'] as $child)
                    <a
                        href="{{ route($child['route'], $child['params'] ?? []) }}"
                        wire:navigate
                        class="shrink-0 rounded-lg px-3 py-1.5 text-sm font-medium transition-colors {{ $childActivo($child) ? 'bg-brand-red-ui text-white' : 'text-cream-deep/80 hover:bg-white/10' }}"
                    >
                        {{ $child['label'] }}
                    </a>
                @endforeach
            @else
                <a
                    href="{{ route($link['route']) }}"
                    wire:navigate
                    class="shrink-0 rounded-lg px-3 py-1.5 text-sm font-medium transition-colors {{ $activo($link['pattern']) ? 'bg-brand-red-ui text-white' : 'text-cream-deep/80 hover:bg-white/10' }}"
                >
                    {{ $link['label'] }}
                </a>
            @endif
        @endforeach
    </nav>
</header>
