<?php

namespace App\Http\Controllers;

use App\Models\LockerRequest;
use App\Models\Locker;
use App\Models\LockerAssignment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

// Este controlador maneja las solicitudes de lockers que hacen los estudiantes
class LockerRequestController extends Controller
{
    // INDEX (Admin): Lista todas las solicitudes pendientes
    // Ruta: GET /admin/solicitudes
    public function index()
    {
        // Traemos todas las solicitudes con el usuario y el locker relacionados
        $requests = LockerRequest::with(['user', 'locker.sector.building'])->get();

        return Inertia::render('Admin/Asignaciones', [
            'solicitudes' => $requests,
        ]);
    }

    // STORE: El estudiante hace una solicitud de locker
    // Ruta: POST /solicitud-locker
    public function store(Request $request)
    {
        // Validamos los datos
        $request->validate([
            'locker_id' => 'required|exists:lockers,locker_id',
        ]);

        // Verificamos que el locker esté disponible (status = 0)
        $locker = Locker::findOrFail($request->locker_id);
        if ($locker->status !== 0) {
            return redirect()->back()->with('error', 'Este locker no está disponible.');
        }

        // Verificamos que el estudiante no tenga ya una solicitud pendiente
        $solicitudExistente = LockerRequest::where('user_id', Auth::id())
            ->where('request_status', 'pending')
            ->first();

        if ($solicitudExistente) {
            return redirect()->back()->with('error', 'Ya tienes una solicitud pendiente.');
        }

        // Creamos la solicitud
        LockerRequest::create([
            'user_id'        => Auth::id(), // el estudiante logueado
            'locker_id'      => $request->locker_id,
            'request_status' => 'pending',
        ]);

        return redirect()->route('mis-solicitudes')->with('success', 'Solicitud enviada correctamente.');
    }

    // APPROVE: El admin aprueba una solicitud y crea la asignación
    // Ruta: POST /admin/solicitudes/{id}/aprobar
    public function approve($id)
    {
        // Buscamos la solicitud
        $solicitud = LockerRequest::findOrFail($id);

        // Cambiamos el estado de la solicitud a "approved"
        $solicitud->update([
            'request_status' => 'approved',
            'reviewed_by'    => Auth::id(),
            'reviewed_at'    => now(),
        ]);

        // Creamos la asignación del locker
        LockerAssignment::create([
            'user_id'           => $solicitud->user_id,
            'locker_id'         => $solicitud->locker_id,
            'request_id'        => $solicitud->request_id,
            'start_date'        => today(),
            'assignment_status' => 'active',
            'created_by'        => Auth::id(),
        ]);

        // Marcamos el locker como ocupado (status = 1)
        Locker::where('locker_id', $solicitud->locker_id)->update(['status' => 1]);

        return redirect()->back()->with('success', 'Solicitud aprobada y locker asignado.');
    }

    // REJECT: El admin rechaza una solicitud
    // Ruta: POST /admin/solicitudes/{id}/rechazar
    public function reject($id)
    {
        $solicitud = LockerRequest::findOrFail($id);

        $solicitud->update([
            'request_status' => 'rejected',
            'reviewed_by'    => Auth::id(),
            'reviewed_at'    => now(),
        ]);

        return redirect()->back()->with('success', 'Solicitud rechazada.');
    }

    // MIS SOLICITUDES: El estudiante ve sus propias solicitudes
    // Ruta: GET /mis-solicitudes
    public function misSolicitudes()
    {
        $solicitudes = LockerRequest::with(['locker.sector.building'])
            ->where('user_id', Auth::id())
            ->get();

        return Inertia::render('User/MisSolicitudes', [
            'solicitudes' => $solicitudes,
        ]);
    }
}
