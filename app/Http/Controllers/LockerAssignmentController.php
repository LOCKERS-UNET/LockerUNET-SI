<?php

namespace App\Http\Controllers;

use App\Models\LockerAssignment;
use App\Models\Locker;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

// Este controlador maneja las asignaciones de lockers
class LockerAssignmentController extends Controller
{
    // INDEX (Admin): Lista todas las asignaciones activas
    // Ruta: GET /admin/asignaciones
    public function index()
    {
        $asignaciones = LockerAssignment::with(['user', 'locker.sector.building', 'admin'])
            ->get();

        return Inertia::render('Admin/Asignaciones', [
            'asignaciones' => $asignaciones,
        ]);
    }

    // MI LOCKER: El estudiante ve el locker que tiene asignado actualmente
    // Ruta: GET /mi-locker  
    public function miLocker()
    {
        // Buscamos la asignación activa del estudiante logueado
        $asignacion = LockerAssignment::with(['locker.sector.building', 'payments', 'fines'])
            ->where('user_id', Auth::id())
            ->where('assignment_status', 'active')
            ->first(); // devuelve solo 1 (o null si no tiene)

        return Inertia::render('User/MiLocker', [
            'asignacion' => $asignacion,
        ]);
    }

    // DEVOLUCION VIEW: Muestra la página de devolución con datos reales
    // Ruta: GET /devolucion-locker
    public function devolucion()
    {
        // Buscamos la asignación activa del estudiante para pasarla a la vista
        $asignacion = LockerAssignment::with(['locker.sector'])
            ->where('user_id', Auth::id())
            ->where('assignment_status', 'active')
            ->first();

        return Inertia::render('User/DevolucionLocker', [
            'asignacion' => $asignacion,
        ]);
    }

    // RELEASE: El estudiante devuelve su locker (devolución)
    // Ruta: POST /devolucion-locker/{id}
    public function release($id)
    {
        $asignacion = LockerAssignment::findOrFail($id);

        // Solo el dueño de la asignación puede devolverlo
        if ($asignacion->user_id !== Auth::id()) {
            return redirect()->back()->with('error', 'No tienes permiso para hacer esto.');
        }

        // Marcamos la asignación como finalizada
        $asignacion->update([
            'assignment_status' => 'released',
            'end_date'          => today(),
        ]);

        // Marcamos el locker como disponible de nuevo (status = 0)
        Locker::where('locker_id', $asignacion->locker_id)->update(['status' => 0]);

        return redirect()->route('home')->with('success', 'Locker devuelto correctamente.');
    }
}
