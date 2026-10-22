<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class PaginasInertiaExistenTest extends TestCase
{
    public function test_cada_pagina_renderizada_tiene_su_archivo_vue(): void
    {
        $raiz = dirname(__DIR__, 2);
        $archivos = array_merge(
            glob($raiz.'/routes/*.php'),
            iterator_to_array(new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($raiz.'/app', \FilesystemIterator::SKIP_DOTS)))
        );

        $encontradas = 0;
        $faltantes = [];
        foreach ($archivos as $archivo) {
            $codigo = file_get_contents((string) $archivo);
            // Inertia::render('X'), inertia('X') y Route::inertia('/url', 'X')
            preg_match_all("/(?:Inertia::render|(?<!::)\binertia)\(\s*'([^']+)'|Route::inertia\(\s*'[^']*'\s*,\s*'([^']+)'/", $codigo, $m);
            foreach (array_filter(array_merge($m[1], $m[2])) as $pagina) {
                $encontradas++;
                if (! file_exists("{$raiz}/resources/js/pages/{$pagina}.vue")) {
                    $faltantes[] = $pagina.' ('.str_replace($raiz.'/', '', (string) $archivo).')';
                }
            }
        }

        $this->assertGreaterThan(20, $encontradas, 'El patron no encontro las llamadas a Inertia');
        $this->assertSame([], array_values(array_unique($faltantes)), 'Paginas Inertia sin archivo .vue');
    }
}
