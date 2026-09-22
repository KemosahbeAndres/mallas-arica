@php
    $opciones = [
        'perfil' => 'Perfil',
        'apariencia' => 'Apariencia',
    ];

    if (auth()->user()->esSuperAdmin()) {
        $opciones['google'] = 'Google SSO';
    }
@endphp

<div class="grid gap-6 lg:grid-cols-[12rem_1fr]">
    <nav class="flex flex-row gap-1 overflow-x-auto lg:flex-col lg:overflow-visible">
        @foreach ($opciones as $valor => $etiqueta)
            <button
                type="button"
                wire:click="$set('tab', '{{ $valor }}')"
                class="shrink-0 rounded-lg px-3 py-2 text-left text-sm font-semibold transition-colors {{ $tab === $valor ? 'bg-brand-red-ui text-white' : 'text-ink-soft hover:bg-cream-deep' }}"
            >
                {{ $etiqueta }}
            </button>
        @endforeach
    </nav>

    <div class="min-w-0">
        @if ($tab === 'perfil')
            <livewire:admin.perfil.perfil-form :key="'perfil-'.auth()->id()" />
        @elseif ($tab === 'apariencia')
            <div class="border-line rounded-lg border bg-white px-4 py-10 text-center">
                <p class="text-ink text-sm font-semibold">Próximamente</p>
                <p class="text-ink-soft mt-1 text-sm">Personalización visual del panel — tema y colores.</p>
            </div>
        @elseif ($tab === 'google' && auth()->user()->esSuperAdmin())
            <livewire:admin.ajustes.google-login-settings />
        @endif
    </div>
</div>
