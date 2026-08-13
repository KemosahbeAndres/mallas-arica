<?php

namespace Database\Seeders;

use App\Models\TramoAltura;
use Illuminate\Database\Seeder;

class TramoAlturaSeeder extends Seeder
{
    public function run(): void
    {
        $tramos = [
            ['etiqueta' => 'Hasta 1,5 m', 'altura_min' => 0, 'altura_max' => 1.5, 'requiere_visita' => false, 'orden' => 1],
            ['etiqueta' => '1,5 – 2 m', 'altura_min' => 1.5, 'altura_max' => 2, 'requiere_visita' => false, 'orden' => 2],
            ['etiqueta' => '2 – 3 m', 'altura_min' => 2, 'altura_max' => 3, 'requiere_visita' => false, 'orden' => 3],
            ['etiqueta' => '+3 m', 'altura_min' => 3, 'altura_max' => null, 'requiere_visita' => true, 'orden' => 4],
        ];

        foreach ($tramos as $tramo) {
            TramoAltura::updateOrCreate(['etiqueta' => $tramo['etiqueta']], $tramo);
        }
    }
}
