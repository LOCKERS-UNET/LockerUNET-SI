<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\FeeRate;
use App\Models\User;

class FeeRateSeeder extends Seeder
{
    public function run(): void
    {
        // Necesitamos un admin para el campo "created_by"
        $admin = User::where('is_admin', 1)->first();

        if (!$admin) return;

        // Creamos las tarifas por tipo de locker
        $tarifas = [
            [
                'locker_type'    => 'small',
                'monthly_amount' => 3500.00,
                'effective_from' => '2026-01-01',
                'reason'         => 'Tarifa inicial semestre 2026-1',
                'created_by'     => $admin->id,
            ],
            [
                'locker_type'    => 'mid',
                'monthly_amount' => 5000.00,
                'effective_from' => '2026-01-01',
                'reason'         => 'Tarifa inicial semestre 2026-1',
                'created_by'     => $admin->id,
            ],
            [
                'locker_type'    => 'large',
                'monthly_amount' => 7500.00,
                'effective_from' => '2026-01-01',
                'reason'         => 'Tarifa inicial semestre 2026-1',
                'created_by'     => $admin->id,
            ],
        ];

        foreach ($tarifas as $tarifa) {
            FeeRate::create($tarifa);
        }
    }
}
