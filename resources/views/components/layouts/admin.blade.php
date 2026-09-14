@props(['title' => null, 'subtitle' => null])
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
            <div class="flex min-h-screen flex-col">
                <x-admin.navbar />

                <div class="bg-cream-deep border-line border-b">
                    <div class="flex flex-wrap items-center justify-between gap-3 px-6 py-4">
                        <div>
                            <h1 class="text-ink text-lg font-bold tracking-tight">{{ $title ?? 'Panel' }}</h1>
                            @if ($subtitle)
                                <p class="text-ink-soft mt-0.5 text-sm">{{ $subtitle }}</p>
                            @endif
                        </div>
                    </div>
                </div>

                <main class="w-full flex-1 px-6 py-5">
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
