@props(['class' => 'w-8 h-8'])

{{-- Motivo de marca: dos cuadrados redondeados superpuestos (isologo), §2 CLAUDE.md --}}
<svg viewBox="0 0 44 48" fill="none" xmlns="http://www.w3.org/2000/svg" {{ $attributes->merge(['class' => $class]) }}>
    <rect x="2" y="17" width="26" height="26" rx="5" transform="rotate(-8 15 30)" fill="var(--color-ink)"/>
    <rect x="17" y="3" width="26" height="26" rx="5" transform="rotate(-8 30 16)" fill="var(--color-brand-red)"/>
</svg>
