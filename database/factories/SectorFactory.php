<?php

namespace Database\Factories;

use App\Models\Building;
use App\Models\Sector;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Sector>
 */
class SectorFactory extends Factory
{
    public function definition(): array
    {
        return [
            'building_id' => Building::factory(),
            'sector_name' => fake()->randomElement(['Planta Baja', 'Piso 1', 'Piso 2']),
        ];
    }
}
