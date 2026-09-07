<?php

namespace App\Livewire\Admin;

use Livewire\Component;

/**
 * Página placeholder para las secciones del CRM aún no construidas
 * (Resumen, Cotizaciones, Clientes, Calendario — Sprints 9+). El chrome
 * del admin ya trae sus entradas de navegación desde el Sprint 8 para no
 * rehacerlo en cada sprint; cada una apunta aquí hasta que se implemente.
 */
class Proximamente extends Component
{
    public string $seccion = 'Esta sección';

    public string $detalle = '';

    public function mount(string $seccion = 'Esta sección', string $detalle = ''): void
    {
        $this->seccion = $seccion;
        $this->detalle = $detalle;
    }

    public function render()
    {
        return view('livewire.admin.proximamente')
            ->layout('components.layouts.admin', ['title' => $this->seccion]);
    }
}
