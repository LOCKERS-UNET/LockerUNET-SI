<?php

namespace App\Http\Controllers;

use App\Models\Locker;
use App\Models\Building;
use App\Models\Sector;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class LockerController extends Controller
{
    // =========================================================
    // USUARIO (Estudiantes)
    // =========================================================

    /**
     * GET /lockers
     * Muestra la búsqueda de lockers con filtros y disponibilidad agrupada.
     */
    public function index(Request $request)
    {
        $query = Locker::with(['sector.building']);

        // Filtros opcionales
        if ($request->has('sector_id') && $request->sector_id !== null && $request->sector_id !== '') {
            $query->where('sector_id', $request->sector_id);
        }
        if ($request->has('locker_type') && $request->locker_type !== null && $request->locker_type !== '') {
            $query->where('locker_type', $request->locker_type);
        }
        if ($request->has('status') && $request->status !== null && $request->status !== '') {
            $query->where('status', (int) $request->status);
        }

        $lockers = $query->get();

        // Disponibilidad agrupada por edificio
        // Solo contamos los lockers disponibles (status = 0)
        $buildings = Building::with(['sectors.lockers' => function($query) {
            $query->where('status', 0);
        }])->get()->map(function($building) {
            $availableCount = $building->sectors->sum(function($sector) {
                return $sector->lockers->count();
            });
            return [
                'building_id' => $building->building_id,
                'building_code' => $building->building_code,
                'building_name' => $building->building_name,
                'available_count' => $availableCount,
            ];
        });

        $sectors = Sector::with('building')->get();

        // Renderizamos la vista que ya existe para buscar
        return Inertia::render('User/BuscarLocker', [
            'lockers' => $lockers,
            'buildings' => $buildings,
            'sectors' => $sectors,
        ]);
    }

    /**
     * GET /admin/lockers/available
     * Devuelve lockers disponibles en formato JSON para el panel de asignaciones.
     */
    public function availableJson(Request $request)
    {
        $query = Locker::with(['sector.building'])
            ->where('status', 0);

        if ($request->has('sector_id') && $request->sector_id !== null && $request->sector_id !== '') {
            $query->where('sector_id', $request->sector_id);
        }
        if ($request->has('locker_type') && $request->locker_type !== null && $request->locker_type !== '') {
            $query->where('locker_type', $request->locker_type);
        }

        $lockers = $query->get();

        return response()->json([
            'lockers' => $lockers,
        ]);
    }

    // =========================================================
    // ADMIN
    // =========================================================

    /**
     * GET /admin/lockers
     * Lista completa con filtros para administración
     */
    public function adminIndex(Request $request)
    {
        $query = Locker::with(['sector.building']);

        // Filtros opcionales (mismos que index)
        if ($request->has('sector_id') && $request->sector_id !== null && $request->sector_id !== '') {
            $query->where('sector_id', $request->sector_id);
        }
        if ($request->has('locker_type') && $request->locker_type !== null && $request->locker_type !== '') {
            $query->where('locker_type', $request->locker_type);
        }
        if ($request->has('status') && $request->status !== null && $request->status !== '') {
            $query->where('status', (int) $request->status);
        }

        $lockers = $query->get();
        $buildings = Building::all();
        $sectors = Sector::with('building')->get();

        return Inertia::render('Admin/GestionLockers', [
            'lockers' => $lockers,
            'buildings' => $buildings,
            'sectors' => $sectors,
        ]);
    }

    /**
     * GET /admin/lockers/create
     * Formulario para crear nuevo locker
     */
    public function create()
    {
        return Inertia::render('Admin/AgregarLocker', [
            'sectors' => Sector::with('building')->get(),
            'buildings' => Building::all(),
        ]);
    }

    /**
     * POST /admin/lockers
     * Guardar el nuevo locker
     */
    public function store(Request $request)
    {
        $request->validate([
            'locker_code' => 'required|string|unique:lockers,locker_code',
            'locker_type' => 'required|in:small,mid,large',
            'status'      => 'required|integer|in:0,1,2',
            'sector_id'   => 'required|exists:sectors,sector_id',
            'plate_number'=> 'nullable|string|max:20',
        ]);

        Locker::create($request->all());

        return redirect()->back()->with('success', 'Locker creado con éxito.');
    }

    /**
     * GET /admin/lockers/{id}/edit
     * Formulario para editar un locker
     */
    public function edit($id)
    {
        $locker = Locker::with(['sector.building'])->findOrFail($id);
        
        return Inertia::render('Admin/ModificarLocker', [
            'locker' => $locker,
            'sectors' => Sector::with('building')->get(),
            'buildings' => Building::all(),
        ]);
    }

    /**
     * PUT /admin/lockers/{id}
     * Actualiza la información
     */
    public function update(Request $request, $id)
    {
        $locker = Locker::findOrFail($id);

        $request->validate([
            'locker_code' => 'required|string|unique:lockers,locker_code,'.$id.',locker_id',
            'locker_type' => 'required|in:small,mid,large',
            'status'      => 'required|integer|in:0,1,2',
            'sector_id'   => 'required|exists:sectors,sector_id',
            'plate_number'=> 'nullable|string|max:20',
        ]);

        // REGLA: Si el status nuevo es 2 (mantenimiento)
        if ($request->status == 2) {
            // Revisamos si tiene asignaciones activas
            $hasActive = $locker->assignments()
                                ->where('assignment_status', 'active')
                                ->exists();
                                
            if ($hasActive) {
                return redirect()->back()->withErrors([
                    'status' => 'No puedes poner en mantenimiento un locker con asignación activa.'
                ]);
            }
        }

        $locker->update($request->all());

        return redirect()->back()->with('success', 'Locker modificado con éxito.');
    }

    /**
     * DELETE /admin/lockers/{id}
     * Elimina permanentemente el registro si no rompe las reglas
     */
    public function destroy($id)
    {
        $locker = Locker::findOrFail($id);

        // REGLA: No eliminar si tiene historial en locker_assignments
        $hasHistory = $locker->assignments()->exists();
        if ($hasHistory) {
            return redirect()->back()->withErrors([
                'locker' => 'No se puede eliminar: el locker tiene historial de asignaciones.'
            ]);
        }

        // REGLA: Solo se pueden eliminar si status = 0 (disponible)
        if ($locker->status != 0) {
            return redirect()->back()->withErrors([
                'locker' => 'Solo se pueden eliminar lockers disponibles.'
            ]);
        }

        $locker->delete();

        return redirect()->back()->with('success', 'Locker eliminado exitosamente.');
    }
    
}
