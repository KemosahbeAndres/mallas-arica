@props(['title' => null])
<!DOCTYPE html>
<html lang="es" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="Instalación de mallas de seguridad certificadas en ventanas, balcones y terrazas en Arica. Cotiza en 30 segundos.">

        <title>{{ $title ? "$title · Mallas Arica" : 'Mallas Arica · Mallas de seguridad certificadas' }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800|plus-jakarta-sans:700,800" rel="stylesheet">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
        <style>[x-cloak] { display: none !important; }</style>
    </head>
    <body class="bg-cream text-ink font-sans antialiased">
        <x-landing.navbar />

        <main>
            {{ $slot }}
        </main>

        <x-landing.footer />

        @livewireScripts
    </body>
</html>
