<?php

namespace Database\Factories;

use App\Models\Locker;
use App\Models\Sector;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Locker>
 */
class LockerFactory extends Factory
{
    public function definition(): array
    {
        return [
            'locker_code' => 'L-'.fake()->unique()->numberBetween(1, 999999),
            'sector_id' => Sector::factory(),
            'locker_type' => 'small',
            'plate_number' => null,
            'status' => 0,
        ];
    }

    // 0=disponible, 1=ocupado, 2=mantenimiento
    public function ocupado(): static
    {
        return $this->state(['status' => 1]);
    }

    public function enMantenimiento(): static
    {
        return $this->state(['status' => 2]);
    }
}
