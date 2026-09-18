<?php

namespace App\Livewire;

use App\Services\LandingMediaService;
use Livewire\Component;

class GaleriaMosaico extends Component
{
    public function render()
    {
        $slot = app(LandingMediaService::class)->slot('galeria-publica');

        return view('livewire.galeria-mosaico', [
            'items' => $slot?->mediaAlbum?->items ?? collect(),
            'estiloImagen' => $slot?->estiloImagen ?? 'object-fit: cover;',
        ]);
    }
}
