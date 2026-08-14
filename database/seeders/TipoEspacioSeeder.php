<?php

namespace Database\Seeders;

use App\Models\TipoEspacio;
use Illuminate\Database\Seeder;

class TipoEspacioSeeder extends Seeder
{
    public function run(): void
    {
        $tipos = [
            [
                'slug' => 'ventana',
                'nombre' => 'Ventanas',
                'descripcion' => 'Malla transparente ajustada al marco que deja pasar la luz y el aire, sin obstruir la vista.',
                'icono' => '🪟',
                'permite_calculo' => true,
                'orden' => 1,
            ],
            [
                'slug' => 'balcon',
                'nombre' => 'Balcones',
                'descripcion' => 'Cierre perimetral seguro para departamentos en altura. Adiós al riesgo de caídas.',
                'icono' => '🏙',
                'permite_calculo' => true,
                'orden' => 2,
            ],
            [
                'slug' => 'terraza',
                'nombre' => 'Terrazas',
                'descripcion' => 'Protege grandes superficies abiertas manteniendo tu espacio ventilado.',
                'icono' => '🌿',
                'permite_calculo' => true,
                'orden' => 3,
            ],
            [
                'slug' => 'escalera',
                'nombre' => 'Escaleras',
                'descripcion' => 'Barreras de seguridad para escaleras interiores y pasillos con desnivel.',
                'icono' => '🪜',
                'permite_calculo' => true,
                'orden' => 4,
            ],
            [
                'slug' => 'mascotas',
                'nombre' => 'Mascotas',
                'descripcion' => 'Malla reforzada de 1 mm con rombo de 1,5 cm: el orificio es tan pequeño que perros y gatos no pueden sacar la cabeza.',
                'icono' => '🐾',
                'permite_calculo' => true,
                'orden' => 5,
            ],
            [
                'slug' => 'piscina',
                'nombre' => 'Piscinas',
                'descripcion' => 'Delimita y protege el acceso a piscinas para el cuidado de los más pequeños.',
                'icono' => '🏊',
                'permite_calculo' => false,
                'orden' => 6,
            ],
        ];

        foreach ($tipos as $tipo) {
            TipoEspacio::updateOrCreate(['slug' => $tipo['slug']], $tipo);
        }
    }
}
