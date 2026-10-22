<?php

namespace App\Http\Controllers;

use App\Models\LockerAssignment;
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
        // Solo disponibles, filtrados y paginados en el servidor: antes se mandaban los ~1.150
        // lockers y la página filtraba en el navegador (8-14 s en 3G, página de 44.000 px)
        // Solo se aceptan textos: con ?tamano[]=x llegaba un arreglo y el (string) daba un error 500
        $texto = fn ($valor) => is_string($valor) ? $valor : '';
        $filtros = [
            'edificio' => $texto($request->query('edificio')),
            'tamano' => $texto($request->query('tamano')),
        ];

        $lockers = Locker::with(['sector.building'])
            ->solicitables()
            ->when($filtros['edificio'] !== '', fn ($q) => $q->whereHas('sector.building', fn ($b) => $b->where('building_code', $filtros['edificio'])))
            ->when($filtros['tamano'] !== '', fn ($q) => $q->where('locker_type', $filtros['tamano']))
            ->orderBy('locker_code')
            ->paginate(15)
            ->withQueryString();

        // Edificios con al menos un locker disponible, para el filtro
        $edificios = Building::withCount(['lockers as disponibles' => fn ($q) => $q->solicitables()])
            ->orderBy('building_name')
            ->get(['building_id', 'building_code', 'building_name'])
            ->where('disponibles', '>', 0)
            ->values();

        return Inertia::render('User/BuscarLocker', [
            'lockers' => $lockers,
            'edificios' => $edificios,
            'filtros' => $filtros,
            // Si ya tiene un locker o una solicitud pendiente, se avisa aquí y no se deja solicitar
            'bloqueo' => LockerRequestController::bloqueoParaSolicitar($request->user()->id),
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

        if (is_string($request->search) && $request->search !== '') {
        $searchTerm = '%' . $request->search . '%';
        $query->where('locker_code', 'LIKE', $searchTerm);
    }
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
            'filters' => $request->only(['search', 'sector_id', 'locker_type', 'status']), // 👈🏼 Pasar filtros actuales
        ]);
    }

    /**
     * POST /admin/lockers
     * Guardar el nuevo locker
     */
    public function store(Request $request)
    {
        // Un locker nuevo no puede nacer "Ocupado": ocupado significa que tiene una asignación,
        // y esa se crea desde Asignaciones
        $request->validate([
            'locker_code' => 'required|string|max:50|unique:lockers,locker_code',
            'locker_type' => 'required|in:small,mid,large',
            'status'      => 'required|integer|in:0,2',
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
            'locker_code' => 'required|string|max:50|unique:lockers,locker_code,'.$id.',locker_id',
            'locker_type' => 'required|in:small,mid,large',
            'status'      => 'required|integer|in:0,1,2',
            'sector_id'   => 'required|exists:sectors,sector_id',
            'plate_number'=> 'nullable|string|max:20',
        ]);

        // REGLA: solo se pasa a "Ocupado" con una asignación (desde Asignaciones). Los que ya
        // estaban ocupados sin dueño (vienen así del inventario) se pueden seguir editando
        if ((int) $request->status === 1 && $locker->status !== 1) {
            return redirect()->back()->withErrors([
                'status' => 'Un locker queda "Ocupado" al asignarlo desde Asignaciones, no desde aquí.'
            ]);
        }

        // REGLA: un locker con asignación vigente sigue ocupado. Antes se podía marcar como
        // disponible y volvía a "Buscar Locker" aunque el estudiante lo seguía teniendo
        if ((int) $request->status !== 1) {
            $hasActive = $locker->assignments()
                                ->whereIn('assignment_status', LockerAssignment::VIGENTES)
                                ->exists();

            if ($hasActive) {
                return redirect()->back()->withErrors([
                    'status' => 'Este locker tiene una asignación vigente. Libéralo desde Asignaciones antes de cambiar su estado.'
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

        // REGLA: las solicitudes se borran en cascada con el locker; si hay pendientes,
        // el estudiante se quedaría sin su solicitud y sin aviso
        if ($locker->requests()->where('request_status', 'pending')->exists()) {
            return redirect()->back()->withErrors([
                'locker' => 'No se puede eliminar: tiene solicitudes pendientes. Recházalas primero desde Solicitudes.'
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
