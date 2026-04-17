<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Locker;
use App\Models\Sector;
use Illuminate\Support\Facades\DB;

class ImportLockers extends Command
{
    protected $signature = 'import:lockers {file}';
    protected $description = 'Importar Lockers de manera simple e ignorar los que tengan sectores fantasmas';

    public function handle()
    {
        $file = $this->argument('file');

        if (!file_exists($file)) {
            $this->error("¡Error! Archivo no encontrado.");
            return Command::FAILURE;
        }

        $header = null;
        $count = 0;
        $erroresSector = 0;
        
        // Obtener la lista real de todos los ID de sectores que EXISTEN en la Base de Datos ahora mismo
        $sectoresValidos = Sector::pluck('sector_id')->toArray();

        try {
            if (($handle = fopen($file, 'r')) !== false) {
                while (($row = fgetcsv($handle, 1000, ',')) !== false) {
                    
                    if (!$header) {
                        $header = array_map(function($val) {
                            return trim(preg_replace('/[\xef\xbb\xbf]/', '', strtolower($val)));
                        }, $row);
                        continue;
                    }

                    if(count($row) !== count($header)) continue;

                    $rowData = array_combine($header, $row);

                    $code = trim($rowData['code']);
                    if (empty($code)) continue;

                    $sectorId = (int) trim($rowData['sector_id']);

                    // ¡AQUÍ ESTÁ LA MAGIA!:
                    // Si el locker pertenece a un sector que NO existe, me lo salto y lo aviso en consola en vez de crashear
                    if (!in_array($sectorId, $sectoresValidos)) {
                        $this->warn("⚠️  Locker omitido: '". $code ."' intenta unirse al sector fantasma #". $sectorId);
                        $erroresSector++;
                        continue;
                    }

                    Locker::updateOrCreate(
                        ['locker_code' => $code],
                        [
                            'locker_type' => trim($rowData['type']),
                            'status' => (int) trim($rowData['status']), 
                            'plate_number' => empty(trim($rowData['plate'])) ? null : trim($rowData['plate']),
                            'sector_id' => $sectorId,
                        ]
                    );

                    $count++;
                }
                fclose($handle);
            }

            $this->info("\n¡Terminado! {$count} Lockers guardados exitosamente.");
            
            if ($erroresSector > 0) {
                $this->error("Se saltaron {$erroresSector} lockers porque el Excel les asignó Sectores que no existen.");
            }

        } catch (\Throwable $e) {
            $this->error("Error Fatal: " . $e->getMessage() . " Línea: " . $e->getLine());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
