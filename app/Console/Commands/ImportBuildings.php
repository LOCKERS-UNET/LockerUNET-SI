<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Building;
use Illuminate\Support\Facades\DB;

class ImportBuildings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:buildings {file : La ruta absoluta al archivo CSV}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Importar Edificios de un archivo CSV (Formato: building_id,building_code,building_name)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $file = $this->argument('file');

        if (!file_exists($file)) {
            $this->error("¡Error! No se encontró el archivo en la ruta: {$file}");
            return Command::FAILURE;
        }

        $this->info("Leyendo archivo de edificios: {$file}");
        
        $header = null;
        $count = 0;

        DB::beginTransaction();

        try {
            if (($handle = fopen($file, 'r')) !== false) {
                while (($row = fgetcsv($handle, 1000, ',')) !== false) {
                    
                    if (!$header) {
                        $header = array_map(function($val) {
                            return trim(preg_replace('/[\xef\xbb\xbf]/', '', strtolower($val)));
                        }, $row);
                        continue;
                    }

                    if(count($row) !== count($header)) {
                        continue;
                    }

                    $rowData = array_combine($header, $row);

                    $buildingName = trim($rowData['building_name']);
                    
                    // Decodificar caracteres especiales por si acabo si viene de un Excel malo
                    if (!mb_detect_encoding($buildingName, 'UTF-8', true)) {
                        $buildingName = utf8_encode($buildingName);
                    }

                    Building::updateOrCreate(
                        ['building_id' => (int) trim($rowData['building_id'])],
                        [
                            'building_code' => trim($rowData['building_code']),
                            'building_name' => $buildingName,
                        ]
                    );

                    $count++;
                }
                fclose($handle);
            }

            DB::commit();
            $this->info("¡Éxito! Se han importado {$count} edificios a la base de datos.");

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Hubo un error al importar: " . $e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
