<?php

namespace Database\Factories;

use App\Models\Locker;
use App\Models\LockerAssignment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Asignación activa de un estudiante sobre un locker ocupado.
 *
 * @extends Factory<LockerAssignment>
 */
class LockerAssignmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->state(['is_admin' => false]),
            'locker_id' => Locker::factory()->ocupado(),
            'request_id' => null,
            'start_date' => today(),
            'end_date' => null,
            'assignment_status' => 'active',
            'created_by' => User::factory()->state(['is_admin' => true]),
        ];
    }

    public function devolucionSolicitada(): static
    {
        return $this->state(['assignment_status' => 'return_requested']);
    }

    public function liberada(): static
    {
        return $this->state(['assignment_status' => 'released', 'end_date' => today()]);
    }
}
