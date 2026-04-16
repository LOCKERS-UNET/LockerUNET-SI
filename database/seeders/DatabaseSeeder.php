<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // El orden importa: primero usuarios, luego lockers (que dependen de nada),
        // luego tarifas (que dependen del usuario admin)
        $this->call([
            UsersSeeder::class,   // Crea usuarios de prueba (admin + estudiantes)
            LockerSeeder::class,  // Crea edificios, sectores y lockers
            FeeRateSeeder::class, // Crea las tarifas de aranceles
        ]);
    }
}
