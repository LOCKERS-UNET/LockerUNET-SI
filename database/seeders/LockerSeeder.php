<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Building;
use App\Models\Sector;
use App\Models\Locker;

class LockerSeeder extends Seeder
{
    public function run(): void
    {
        // ─── EDIFICIOS ────────────────────────────────────────────────
        // Creamos 3 edificios de prueba como si fuera la UNET
        $edificioA = Building::create([
            'building_code' => 'E-A',
            'building_name' => 'Edificio A',
        ]);

        $edificioB = Building::create([
            'building_code' => 'E-B',
            'building_name' => 'Edificio B',
        ]);

        $edificioC = Building::create([
            'building_code' => 'E-C',
            'building_name' => 'Edificio C',
        ]);

        // ─── SECTORES ─────────────────────────────────────────────────
        // Cada edificio tiene plantas/pisos
        $pbA = Sector::create(['building_id' => $edificioA->building_id, 'sector_name' => 'Planta Baja']);
        $p1A = Sector::create(['building_id' => $edificioA->building_id, 'sector_name' => 'Piso 1']);

        $pbB = Sector::create(['building_id' => $edificioB->building_id, 'sector_name' => 'Planta Baja']);
        $p1B = Sector::create(['building_id' => $edificioB->building_id, 'sector_name' => 'Piso 1']);

        $pbC = Sector::create(['building_id' => $edificioC->building_id, 'sector_name' => 'Planta Baja']);

        // ─── LOCKERS ──────────────────────────────────────────────────
        // status: 0 = disponible, 1 = ocupado, 2 = mantenimiento
        // locker_type: small, mid, large

        $lockers = [
            // Edificio A - Planta Baja
            ['locker_code' => 'A-PB-001', 'sector_id' => $pbA->sector_id, 'locker_type' => 'small', 'plate_number' => null, 'status' => 0],
            ['locker_code' => 'A-PB-002', 'sector_id' => $pbA->sector_id, 'locker_type' => 'mid',   'plate_number' => 'A01', 'status' => 0],
            ['locker_code' => 'A-PB-003', 'sector_id' => $pbA->sector_id, 'locker_type' => 'large', 'plate_number' => null, 'status' => 1], // ocupado

            // Edificio A - Piso 1
            ['locker_code' => 'A-P1-001', 'sector_id' => $p1A->sector_id, 'locker_type' => 'small', 'plate_number' => null, 'status' => 0],
            ['locker_code' => 'A-P1-002', 'sector_id' => $p1A->sector_id, 'locker_type' => 'mid',   'plate_number' => 'B02', 'status' => 0],
            ['locker_code' => 'A-P1-003', 'sector_id' => $p1A->sector_id, 'locker_type' => 'large', 'plate_number' => null, 'status' => 2], // mantenimiento

            // Edificio B - Planta Baja
            ['locker_code' => 'B-PB-001', 'sector_id' => $pbB->sector_id, 'locker_type' => 'small', 'plate_number' => null, 'status' => 0],
            ['locker_code' => 'B-PB-002', 'sector_id' => $pbB->sector_id, 'locker_type' => 'mid',   'plate_number' => null, 'status' => 0],
            ['locker_code' => 'B-PB-003', 'sector_id' => $pbB->sector_id, 'locker_type' => 'large', 'plate_number' => 'C03','status' => 0],

            // Edificio B - Piso 1
            ['locker_code' => 'B-P1-001', 'sector_id' => $p1B->sector_id, 'locker_type' => 'small', 'plate_number' => null, 'status' => 0],
            ['locker_code' => 'B-P1-002', 'sector_id' => $p1B->sector_id, 'locker_type' => 'mid',   'plate_number' => null, 'status' => 1], // ocupado

            // Edificio C - Planta Baja
            ['locker_code' => 'C-PB-001', 'sector_id' => $pbC->sector_id, 'locker_type' => 'small', 'plate_number' => null, 'status' => 0],
            ['locker_code' => 'C-PB-002', 'sector_id' => $pbC->sector_id, 'locker_type' => 'mid',   'plate_number' => null, 'status' => 0],
            ['locker_code' => 'C-PB-003', 'sector_id' => $pbC->sector_id, 'locker_type' => 'large', 'plate_number' => 'D04','status' => 0],
        ];

        // Insertamos todos los lockers
        foreach ($lockers as $locker) {
            Locker::create($locker);
        }
    }
}
