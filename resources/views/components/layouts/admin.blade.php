@props(['title' => null, 'subtitle' => null, 'googleCalendar' => false, 'fillHeight' => false])
<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="robots" content="noindex, nofollow">

        <title>{{ $title ? "$title · Admin Mallas Arica Jacob" : 'Admin · Mallas Arica Jacob' }}</title>

        <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
        <style>[x-cloak] { display: none !important; }</style>
    </head>
    <body class="bg-cream text-ink font-sans antialiased">
        @auth
            <div class="flex h-screen flex-col">
                <x-admin.navbar />

                <div class="bg-cream-deep border-line shrink-0 border-b">
                    <div class="flex flex-wrap items-center justify-between gap-3 px-6 py-4">
                        <div>
                            <h1 class="text-ink text-lg font-bold tracking-tight">{{ $title ?? 'Panel' }}</h1>
                            @if ($subtitle)
                                <p class="text-ink-soft mt-0.5 text-sm">{{ $subtitle }}</p>
                            @endif
                        </div>

                        @if ($googleCalendar)
                            <button
                                type="button"
                                disabled
                                class="shrink-0 cursor-not-allowed rounded-lg bg-gray-200 px-4 py-2 text-sm font-semibold text-gray-500"
                                title="Disponible en una próxima entrega"
                            >
                                Conectar con Google Calendar · próximamente
                            </button>
                        @endif
                    </div>
                </div>

                <main class="{{ $fillHeight ? 'flex min-h-0 flex-1 flex-col overflow-hidden' : 'w-full flex-1 overflow-y-auto' }} px-6 py-5">
                    {{ $slot }}
                </main>
            </div>
        @else
            <main class="flex min-h-screen items-center justify-center p-6">
                {{ $slot }}
            </main>
        @endauth

        @livewireScripts
    </body>
</html>
