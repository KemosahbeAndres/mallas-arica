<?php

namespace App\Livewire;

use App\Models\GaleriaItem;
use Livewire\Component;

class GaleriaMosaico extends Component
{
    public function render()
    {
        return view('livewire.galeria-mosaico', [
            'items' => GaleriaItem::query()
                ->where('publicado', true)
                ->orderBy('orden')
                ->get(),
        ]);
    }
}
