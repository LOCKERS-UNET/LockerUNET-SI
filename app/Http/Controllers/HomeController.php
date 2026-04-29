<?php

namespace App\Http\Controllers;

use App\Models\Building;
use App\Models\Locker;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class HomeController extends Controller
{
    /**
     * GET / (Home del usuario)
     */
    public function index()
    {
        // Obtener todos los edificios con conteo de lockers disponibles
        $buildings = Building::with(['sectors.lockers' => function($query) {
            $query->where('status', 0); // Solo lockers disponibles
        }])->get()->map(function($building) {
            // Contar lockers disponibles en todos los sectores del edificio
            $availableCount = $building->sectors->sum(function($sector) {
                return $sector->lockers->count();
            });
            
            return [
                'building_id' => $building->building_id,
                'building_code' => $building->building_code,
                'building_name' => $building->building_name,
                'available_lockers' => $availableCount,
                'total_lockers' => $building->sectors->sum(function($sector) {
                    return $sector->lockers()->count();
                }),
            ];
        });

        return Inertia::render('Home', [
            'buildings' => $buildings,
        ]);
    }
}