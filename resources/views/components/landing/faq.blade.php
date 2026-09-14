@php
    $preguntas = app(\App\Services\SiteContentService::class)->faqs();
@endphp

<section id="faq" class="scroll-mt-24 bg-cream">
    <div class="mx-auto max-w-3xl px-6 py-20 lg:px-8">
        <div class="text-center">
            <p class="text-brand-red-ui text-sm font-bold tracking-wide uppercase">Preguntas frecuentes</p>
            <h2 class="text-ink mt-3 text-3xl font-extrabold tracking-[-0.02em] sm:text-4xl">
                Resolvemos tus dudas
            </h2>
        </div>

        <div class="mt-12 flex flex-col gap-4">
            @foreach ($preguntas as $item)
                <div x-data="{ open: false }" class="border-line overflow-hidden rounded-2xl border bg-white">
                    <button
                        type="button"
                        @click="open = !open"
                        class="flex w-full items-center justify-between gap-4 px-6 py-5 text-left"
                        :aria-expanded="open"
                    >
                        <span class="text-ink font-semibold">{{ $item->pregunta }}</span>
                        <span
                            class="text-brand-red-ui shrink-0 text-xl transition-transform"
                            :class="{ 'rotate-45': open }"
                        >+</span>
                    </button>
                    <div
                        x-show="open"
                        x-cloak
                        x-transition
                        class="px-6 pb-5"
                    >
                        <p class="text-ink-soft text-sm leading-relaxed">{{ $item->respuesta }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    @if ($preguntas->isNotEmpty())
        <script type="application/ld+json">
            {!! json_encode([
                '@context' => 'https://schema.org',
                '@type' => 'FAQPage',
                'mainEntity' => $preguntas->map(fn ($item) => [
                    '@type' => 'Question',
                    'name' => $item->pregunta,
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text' => $item->respuesta,
                    ],
                ])->all(),
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
        </script>
    @endif
</section>
