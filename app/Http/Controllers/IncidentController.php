<?php

namespace App\Http\Controllers;

use App\Models\Incident;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

// Este controlador maneja las incidencias reportadas por los estudiantes
class IncidentController extends Controller
{
    // INDEX (Admin): Lista todas las incidencias
    // Ruta: GET /admin/incidencias
    public function index()
    {
        $incidencias = Incident::with(['user', 'locker.sector.building', 'reviewer'])->get();

        return Inertia::render('Admin/Incidencias', [
            'incidencias' => $incidencias,
        ]);
    }

    // STORE: El estudiante reporta una incidencia sobre su locker
    // Ruta: POST /reportes-user
    public function store(Request $request)
    {
        $request->validate([
            'locker_id'   => 'required|exists:lockers,locker_id',
            'description' => 'required|string',
        ]);

        Incident::create([
            'user_id'     => Auth::id(),
            'locker_id'   => $request->locker_id,
            'description' => $request->description,
            'status'      => 'pending',
        ]);

        return redirect()->back()->with('success', 'Incidencia reportada correctamente.');
    }

    // REVIEW: El admin marca una incidencia como revisada
    // Ruta: POST /admin/incidencias/{id}/revisar
    public function review($id)
    {
        $incidencia = Incident::findOrFail($id);

        $incidencia->update([
            'status'      => 'reviewed',
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Incidencia marcada como revisada.');
    }
}
