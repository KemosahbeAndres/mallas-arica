<?php

namespace Database\Factories;

use App\Models\TrabajoFoto;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TrabajoFoto>
 */
class TrabajoFotoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'foto_path' => 'trabajos/'.fake()->uuid().'.jpg',
        ];
    }
}
