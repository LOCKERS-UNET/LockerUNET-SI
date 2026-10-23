<?php

namespace App\Http\Controllers;

use App\Models\Building;
use App\Models\Locker;
use Inertia\Inertia;

class HomeController extends Controller
{
    /**
     * GET / (Home del usuario)
     */
    public function index()
    {
        // Disponibles = lo que se puede pedir (mismo criterio que Buscar Locker: sin solicitud pendiente).
        // Antes cargaba todos los lockers en memoria y contaba solo status = 0, y no cuadraba con la búsqueda.
        $buildings = Building::withCount([
            'lockers as available_lockers' => fn ($q) => $q->solicitables(),
            'lockers as total_lockers',
        ])->get(['building_id', 'building_code', 'building_name']);

        return Inertia::render('Home', [
            'buildings' => $buildings,
        ]);
    }
}
