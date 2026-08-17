@props(['title' => null])
<!DOCTYPE html>
<html lang="es" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="Instalación de mallas de seguridad certificadas en ventanas, balcones y terrazas en Arica. Cotiza en 30 segundos.">

        <title>{{ $title ? "$title · Mallas Arica" : 'Mallas Arica · Mallas de seguridad certificadas' }}</title>

        <link rel="canonical" href="{{ url()->current() }}">
        <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">

        <meta property="og:type" content="website">
        <meta property="og:site_name" content="Mallas Arica">
        <meta property="og:title" content="{{ $title ? "$title · Mallas Arica" : 'Mallas Arica · Mallas de seguridad certificadas' }}">
        <meta property="og:description" content="Instalación de mallas de seguridad certificadas en ventanas, balcones y terrazas en Arica. Cotiza en 30 segundos.">
        <meta property="og:url" content="{{ url()->current() }}">
        <meta property="og:image" content="{{ asset('images/isologo.png') }}">
        <meta property="og:locale" content="es_CL">
        <meta name="twitter:card" content="summary_large_image">

        <script type="application/ld+json">
            {!! json_encode([
                '@context' => 'https://schema.org',
                '@type' => 'LocalBusiness',
                'name' => 'Mallas Arica',
                'image' => asset('images/isologo.png'),
                'telephone' => '+56986455205',
                'priceRange' => '$$',
                'address' => [
                    '@type' => 'PostalAddress',
                    'streetAddress' => 'Av. Diego Portales #1333',
                    'addressLocality' => 'Arica',
                    'addressRegion' => 'Arica y Parinacota',
                    'addressCountry' => 'CL',
                ],
                'areaServed' => 'Arica',
                'url' => url('/'),
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
        </script>

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
