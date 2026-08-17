<?php

namespace Database\Seeders;

use App\Models\GaleriaItem;
use App\Models\TipoEspacio;
use Illuminate\Database\Seeder;

class GaleriaItemSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            ['slug' => 'ventana', 'titulo' => 'Malla en ventana de departamento'],
            ['slug' => 'balcon', 'titulo' => 'Cierre perimetral de balcón'],
            ['slug' => 'terraza', 'titulo' => 'Protección de terraza abierta'],
            ['slug' => 'escalera', 'titulo' => 'Barrera de seguridad en escalera'],
            ['slug' => 'mascotas', 'titulo' => 'Malla reforzada para mascotas'],
            ['slug' => 'piscina', 'titulo' => 'Cerco de protección para piscina'],
        ];

        foreach ($items as $index => $item) {
            $tipoEspacio = TipoEspacio::query()->where('slug', $item['slug'])->first();

            GaleriaItem::create([
                'foto_path' => "galeria/{$item['slug']}-1.svg",
                'titulo' => $item['titulo'],
                'tipo_espacio_id' => $tipoEspacio?->id,
                'orden' => $index + 1,
                'publicado' => true,
            ]);
        }
    }
}
