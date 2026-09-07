@props(['title' => null])
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
            <div class="flex min-h-screen">
                <x-admin.sidebar />

                <div class="flex-1 flex flex-col">
                    <header class="flex items-center justify-between border-b border-line bg-white px-6 py-4">
                        <p class="text-sm text-ink-soft">{{ auth()->user()->name }}</p>

                        <form method="POST" action="{{ route('admin.logout') }}">
                            @csrf
                            <button type="submit" class="text-sm text-brand-red-ui hover:text-brand-red-dark">
                                Cerrar sesión
                            </button>
                        </form>
                    </header>

                    <main class="flex-1 p-6">
                        {{ $slot }}
                    </main>
                </div>
            </div>
        @else
            <main class="flex min-h-screen items-center justify-center p-6">
                {{ $slot }}
            </main>
        @endauth

        @livewireScripts
    </body>
</html>
