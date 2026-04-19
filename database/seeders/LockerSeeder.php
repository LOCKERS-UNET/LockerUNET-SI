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
        // Rutas a los archivos CSV
        $buildingsCsv = database_path('seeders/data/Buildings.csv');
        $sectorsCsv = database_path('seeders/data/sectors.csv');
        $lockersCsv = database_path('seeders/data/Lockers.csv');

        // 1. CARGAR EDIFICIOS
        if (file_exists($buildingsCsv)) {
            $buildingsData = [];
            $handle = fopen($buildingsCsv, 'r');
            $header = fgetcsv($handle); // Leer cabecera
            while (($row = fgetcsv($handle)) !== false) {
                if (count($row) === 3) {
                    $buildingsData[] = [
                        'building_id' => $row[0],
                        'building_code' => $row[1],
                        'building_name' => $row[2],
                    ];
                }
            }
            fclose($handle);

            if (!empty($buildingsData)) {
                \Illuminate\Support\Facades\DB::table('buildings')->upsert(
                    $buildingsData, 
                    ['building_id'], 
                    ['building_code', 'building_name']
                );
            }
        }

        // 2. CARGAR SECTORES
        if (file_exists($sectorsCsv)) {
            $sectorsData = [];
            $handle = fopen($sectorsCsv, 'r');
            $header = fgetcsv($handle);
            while (($row = fgetcsv($handle)) !== false) {
                if (count($row) === 3) {
                    $sectorsData[] = [
                        'sector_id' => $row[0],
                        'building_id' => $row[1],
                        'sector_name' => $row[2],
                    ];
                }
            }
            fclose($handle);

            if (!empty($sectorsData)) {
                \Illuminate\Support\Facades\DB::table('sectors')->upsert(
                    $sectorsData, 
                    ['sector_id'], 
                    ['building_id', 'sector_name']
                );
            }
        }

        // 3. CARGAR LOCKERS
        if (file_exists($lockersCsv)) {
            $lockersData = [];
            $handle = fopen($lockersCsv, 'r');
            $header = fgetcsv($handle);
            while (($row = fgetcsv($handle)) !== false) {
                // type, code, status, plate, sector_id
                if (count($row) >= 5) {
                    $lockersData[] = [
                        'locker_type' => $row[0],
                        'locker_code' => $row[1],
                        'status' => (int) $row[2],
                        'plate_number' => $row[3] === 'not' ? null : $row[3],
                        'sector_id' => $row[4],
                    ];
                }
            }
            fclose($handle);

            // Insertar en lotes si el archivo es grande
            if (!empty($lockersData)) {
                $chunks = array_chunk($lockersData, 500);
                foreach ($chunks as $chunk) {
                    \Illuminate\Support\Facades\DB::table('lockers')->upsert(
                        $chunk, 
                        ['locker_code'], 
                        ['locker_type', 'status', 'plate_number', 'sector_id']
                    );
                }
            }
        }
    }
}
