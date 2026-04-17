<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Sector;
use Illuminate\Support\Facades\DB;

class ImportSectors extends Command
{
    protected $signature = 'import:sectors {file}';
    protected $description = 'Importar Sectores modo simple';

    public function handle()
    {
        $file = $this->argument('file');

        if (!file_exists($file)) {
            $this->error("¡Error! Archivo no encontrado.");
            return Command::FAILURE;
        }
        
        $header = null;
        $count = 0;

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

                    $sectorId = (int) trim($rowData['sector_id']);
                    $bId = (int) trim($rowData['building_id']);
                    $name = trim($rowData['sector_name']);

                    Sector::updateOrCreate(
                        ['sector_id' => $sectorId],
                        [
                            'building_id' => $bId,
                            'sector_name' => $name,
                        ]
                    );

                    $count++;
                    $this->info("Sector importado: " . $name);
                }
                fclose($handle);
            }

            $this->info("¡Éxito! {$count} sectores listos.");

        } catch (\Throwable $e) {
            $this->error("Error Fatal PHP: " . $e->getMessage() . " Línea: " . $e->getLine());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
