<?php

namespace App\Http\Controllers;

use App\Models\Incident;
use App\Helpers\NotificationHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class IncidentController extends Controller
{
    // ==========================================
    // USUARIOS
    // ==========================================

    /**
     * POST /incidents
     * Permite reportar problemas físicos de candados o instalaciones
     */
    public function store(Request $request)
    {
        $request->validate([
            'locker_id'   => 'required|exists:lockers,locker_id',
            'description' => 'required|string|max:1000',
        ]);

        Incident::create([
            'user_id'     => Auth::id(),
            'locker_id'   => $request->locker_id,
            'description' => $request->description,
            'status'      => 'pending',
            'created_at'  => now()
        ]);

        return redirect()->back()->with('success', 'Reporte enviado correctamente.');
    }

    // ==========================================
    // ADMINISTRADORES
    // ==========================================

    /**
     * GET /admin/incidents
     * Lista completa con filtros
     */
    public function index(Request $request)
    {
        $query = Incident::with(['user', 'locker.sector.building']);

        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        $incidents = $query->orderBy('created_at', 'desc')->get();

        return Inertia::render('Admin/Incidencias', [
            'incidents' => $incidents
        ]);
    }

    /**
     * PUT /admin/incidents/{id}/review
     * Marca el problema como atendido
     */
    public function review($id)
    {
        $incident = Incident::findOrFail($id);

        if ($incident->status === 'reviewed') {
            return redirect()->back()->withErrors(['incident' => 'La incidencia ya fue clasificada como revisada previamente.']);
        }

        $incident->update([
            'status'      => 'reviewed',
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
        ]);

        NotificationHelper::send(
            $incident->user_id, 
            'incident_reviewed', 
            'Incidencia revisada',
            'Tu reporte de incidencia ha sido revisado por el administrador.'
        );

        return redirect()->back()->with('success', 'Incidencia marcada como revisada. El usuario ha sido notificado.');
    }
}
