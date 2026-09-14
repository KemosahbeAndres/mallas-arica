<?php

namespace App\Livewire\Admin\SitioWeb;

use Livewire\Attributes\Url;
use Livewire\Component;

class SitioWebPanel extends Component
{
    #[Url]
    public string $tab = 'contenido';

    public function updatedTab(string $value): void
    {
        if (! in_array($value, ['contenido', 'imagenes', 'faq'], true)) {
            $this->tab = 'contenido';
        }
    }

    public function render()
    {
        return view('livewire.admin.sitio-web.sitio-web-panel')
            ->layout('components.layouts.admin', [
                'title' => 'Sitio web',
                'subtitle' => 'Contenido, imágenes y preguntas frecuentes',
            ]);
    }
}
