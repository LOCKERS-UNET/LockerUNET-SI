<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

// Este controlador maneja las notificaciones de los usuarios
class NotificationController extends Controller
{
    // INDEX: El estudiante ve sus notificaciones
    // Ruta: GET /notificaciones
    public function index()
    {
        // Traemos las notificaciones del estudiante, más recientes primero
        $notificaciones = Notification::where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('User/Notificaciones', [
            'notificaciones' => $notificaciones,
        ]);
    }

    // MARK AS READ: Marca una notificación como leída
    // Ruta: POST /notificaciones/{id}/leer
    public function markAsRead($id)
    {
        $notificacion = Notification::findOrFail($id);

        // Solo el dueño puede marcar su notificación
        if ($notificacion->user_id !== Auth::id()) {
            return redirect()->back()->with('error', 'No tienes permiso.');
        }

        $notificacion->update(['is_read' => true]);

        return redirect()->back()->with('success', 'Notificación leída.');
    }
}
