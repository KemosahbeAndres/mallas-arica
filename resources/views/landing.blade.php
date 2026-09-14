<x-layouts.app>
    <x-landing.hero />
    <x-landing.attribute-bar />
    <x-landing.protection-grid />
    <x-landing.mesh-types />
    <x-landing.how-we-work />

    {{-- Sin cotizador con precio: el negocio cotiza a mano tras la visita
         técnica (Sprint 12). Este formulario solo capta el contacto y crea
         un Cliente en el CRM. --}}
    <livewire:solicitud-contacto />

    <livewire:galeria-mosaico />

    <x-landing.about-us />
    <x-landing.faq />
    <x-landing.final-cta />
</x-layouts.app>
