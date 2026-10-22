<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cifra los datos de cada página que Inertia guarda en el historial del navegador, para que
 * tras cerrar sesión (Inertia::clearHistory) "Atrás" no vuelva a mostrar datos personales.
 *
 * El navegador solo puede cifrar en un contexto seguro (HTTPS o localhost): en http:// simple
 * las páginas fallarían, así que ahí no se activa. Detrás de un proxy con HTTPS hay que
 * configurar los trusted proxies para que $request->secure() sea true.
 */
class CifrarHistorial
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->secure() || in_array($request->getHost(), ['localhost', '127.0.0.1', '[::1]'], true)) {
            Inertia::encryptHistory();
        }

        return $next($request);
    }
}
