<?php

namespace App\Livewire;

use App\Services\LandingMediaService;
use Livewire\Component;

class GaleriaMosaico extends Component
{
    public function render()
    {
        $album = app(LandingMediaService::class)->slot('galeria-publica')?->mediaAlbum;

        return view('livewire.galeria-mosaico', [
            'items' => $album?->items ?? collect(),
        ]);
    }
}
