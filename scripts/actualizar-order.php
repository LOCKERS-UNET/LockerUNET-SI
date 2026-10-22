<?php

/*
 * Regenera el arbol de archivos dentro de order.md (entre los marcadores
 * ARBOL:INICIO y ARBOL:FIN) a partir de los archivos que git conoce,
 * incluidos los nuevos que aun no estan commiteados pero no estan ignorados.
 *
 * Uso: php scripts/actualizar-order.php [--check]
 *   --check  no escribe nada; sale con codigo 1 si order.md esta desactualizado.
 *
 * Tambien avisa de los archivos de app/, resources/js/pages/, routes/ y
 * database/ que no se mencionan en la parte escrita a mano de order.md.
 */

$raiz = dirname(__DIR__);
$orderPath = $raiz.'/order.md';
$inicio = '<!-- ARBOL:INICIO (generado por scripts/actualizar-order.php, no editar a mano) -->';
$fin = '<!-- ARBOL:FIN -->';

// Carpetas que se muestran como una sola linea con el numero de archivos.
$colapsar = [
    'public/img',
    'database/seeders/data',
    'storage',
    'bootstrap/cache',
    '.github/workflows',
    'qa-reports',
];

// Archivos y carpetas que no aportan al mapa del proyecto.
$omitir = [
    'composer.phar',
    '.claude/',
];

// Carpetas cuyos archivos deben estar descritos en la parte manual.
$documentables = '#^(app/|resources/js/pages/|routes/|database/(migrations|seeders)/[^/]+\.php$)#';

exec('git -C '.escapeshellarg($raiz).' ls-files --cached --others --exclude-standard', $archivos, $codigo);
if ($codigo !== 0) {
    fwrite(STDERR, "No se pudo listar los archivos con git.\n");
    exit(2);
}

$archivos = array_values(array_unique(array_filter($archivos, function ($archivo) use ($raiz, $omitir) {
    // Ignora borrados pendientes y entradas que no son archivos normales.
    foreach ($omitir as $patron) {
        if ($archivo === $patron || (str_ends_with($patron, '/') && str_starts_with($archivo, $patron))) {
            return false;
        }
    }

    return is_file($raiz.'/'.$archivo);
})));

$conteoColapsado = [];
$arbol = [];
foreach ($archivos as $archivo) {
    foreach ($colapsar as $carpeta) {
        if (str_starts_with($archivo, $carpeta.'/')) {
            $conteoColapsado[$carpeta] = ($conteoColapsado[$carpeta] ?? 0) + 1;
            $archivo = $carpeta.'/'.chr(0);
            break;
        }
    }

    $nodo = &$arbol;
    foreach (explode('/', $archivo) as $parte) {
        $nodo[$parte] ??= [];
        $nodo = &$nodo[$parte];
    }
    unset($nodo);
}

function ordenar(array &$nodo): void
{
    uksort($nodo, function ($a, $b) use ($nodo) {
        $aEsCarpeta = $nodo[$a] !== [];
        $bEsCarpeta = $nodo[$b] !== [];
        if ($aEsCarpeta !== $bEsCarpeta) {
            return $aEsCarpeta ? -1 : 1;
        }

        return strcasecmp($a, $b);
    });
    foreach ($nodo as &$hijo) {
        ordenar($hijo);
    }
}

function pintar(array $nodo, string $prefijo, string $ruta, array $conteo): array
{
    $lineas = [];
    $nombres = array_keys($nodo);
    foreach ($nombres as $i => $nombre) {
        $ultimo = $i === count($nombres) - 1;
        $rama = $ultimo ? '└── ' : '├── ';
        $rutaHijo = ltrim($ruta.'/'.$nombre, '/');
        $hijos = $nodo[$nombre];

        if (isset($hijos[chr(0)])) {
            $n = $conteo[$rutaHijo];
            $lineas[] = $prefijo.$rama.$nombre.'/  ('.$n.' archivo'.($n === 1 ? '' : 's').')';
        } elseif ($hijos !== []) {
            $lineas[] = $prefijo.$rama.$nombre.'/';
            array_push($lineas, ...pintar($hijos, $prefijo.($ultimo ? '    ' : '│   '), $rutaHijo, $conteo));
        } else {
            $lineas[] = $prefijo.$rama.$nombre;
        }
    }

    return $lineas;
}

ordenar($arbol);
$bloque = $inicio."\n```text\nLockerUNET-SI/\n".implode("\n", pintar($arbol, '', '', $conteoColapsado))."\n```\n".$fin;

$contenido = file_exists($orderPath) ? file_get_contents($orderPath) : '';
$patron = '/'.preg_quote($inicio, '/').'.*?'.preg_quote($fin, '/').'/s';
if (! preg_match($patron, $contenido)) {
    fwrite(STDERR, "order.md no tiene los marcadores ARBOL:INICIO / ARBOL:FIN.\n");
    exit(2);
}
$nuevo = preg_replace_callback($patron, fn () => $bloque, $contenido);

// Avisa de archivos sin describir en la parte manual (todo menos el arbol).
$manual = preg_replace($patron, '', $nuevo);
$sinDescribir = array_filter($archivos, fn ($archivo) => preg_match($documentables, $archivo)
    && ! str_contains($manual, pathinfo($archivo, PATHINFO_FILENAME)));
foreach ($sinDescribir as $archivo) {
    fwrite(STDERR, "order.md: falta describir {$archivo}\n");
}

if (in_array('--check', $argv, true)) {
    if ($nuevo !== $contenido) {
        fwrite(STDERR, "order.md esta desactualizado. Ejecuta: composer order\n");
        exit(1);
    }
    exit(0);
}

if ($nuevo !== $contenido) {
    file_put_contents($orderPath, $nuevo);
    echo "order.md actualizado.\n";
}
