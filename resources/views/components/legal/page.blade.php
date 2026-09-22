@props(['title', 'actualizado'])

<x-layouts.app :title="$title">
    <section class="bg-cream">
        <div class="mx-auto max-w-3xl px-6 py-16 lg:px-8 lg:py-24">
            <p class="text-brand-red-ui text-sm font-bold tracking-wide uppercase">Mallas Arica Jacob</p>
            <h1 class="text-ink mt-3 text-3xl font-extrabold tracking-[-0.02em] sm:text-4xl">{{ $title }}</h1>
            <p class="text-ink-soft mt-2 text-sm">Última actualización: {{ $actualizado }}</p>

            <div class="text-ink-soft [&_a]:text-brand-red-ui mt-10 space-y-6 text-base leading-relaxed [&_a]:font-semibold [&_a]:hover:underline [&_h2]:text-ink [&_h2]:mt-10 [&_h2]:text-xl [&_h2]:font-extrabold [&_h2]:tracking-[-0.02em] [&_li]:ml-5 [&_li]:list-disc [&_strong]:text-ink [&_strong]:font-semibold [&_ul]:space-y-2">
                {{ $slot }}
            </div>
        </div>
    </section>
</x-layouts.app>
