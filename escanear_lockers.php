<?php
$file = 'C:\\Users\\epinz\\Downloads\\Lockers.csv';
$h = fopen($file, 'r');
if (!$h) die("No se puede abrir el archivo");
fgetcsv($h, 1000, ','); // header

$sectorsValidos = [1=>1, 2=>1, 3=>1, 4=>1, 5=>1, 6=>1, 10=>1, 11=>1, 12=>1, 13=>1, 14=>1, 15=>1];
$malos = [];

while (($row = fgetcsv($h, 1000, ',')) !== false) {
    if (count($row) < 5) continue;
    $sid = (int)trim($row[4]);
    if (!isset($sectorsValidos[$sid])) {
        $malos[$sid] = ($malos[$sid] ?? 0) + 1;
    }
}
fclose($h);

if (empty($malos)) {
    echo "Todos los sectores existen.\n";
} else {
    echo "¡ERROR! Hay Lockers apuntando a estos sectores que no existen en tu BD:\n";
    foreach ($malos as $idStr => $cantidad) {
        if ($idStr == 0) continue; // Por si hay vacíos
        echo "=> Sector #$idStr: $cantidad lockers huerfanos.\n";
    }
}
