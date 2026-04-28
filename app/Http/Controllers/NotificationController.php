<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class NotificationController extends Controller
{
    /**
     * GET /notifications
     * Visor de notificaciones con paginación (5 por página)
     */
    public function index(Request $request)
    {
        $query = Notification::where('user_id', Auth::id());

        // Búsqueda LIKE opcional
        if ($request->has('search') && $request->search) {
            $searchTerm = '%' . $request->search . '%';
            $query->where(function ($q) use ($searchTerm) {
                $q->where('title', 'LIKE', $searchTerm)
                  ->orWhere('message', 'LIKE', $searchTerm);
            });
        }

        // 👇🏼 PAGINACIÓN: 5 notificaciones por página
        $notifications = $query->orderBy('created_at', 'desc')
            ->paginate(5)
            ->withQueryString(); // Mantiene los filtros en la paginación

        return Inertia::render('User/Notificaciones', [
            'notificaciones' => $notifications->items(), // Los datos de la página actual
            'pagination' => [
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'total' => $notifications->total(),
                'per_page' => $notifications->perPage(),
                'has_more_pages' => $notifications->hasMorePages(),
            ]
        ]);
    }

    /**
     * PATCH /notifications/{id}/read
     */
    public function markRead($id)
    {
        $notification = Notification::findOrFail($id);

        if ($notification->user_id !== Auth::id()) {
            abort(403);
        }

        $notification->update(['is_read' => true]);

        // 👇🏼 Retornar JSON para que el frontend pueda actualizar sin recargar
        return redirect()->back();
    }

    /**
     * PATCH /notifications/read-all
     */
    public function readAll()
    {
        Notification::where('user_id', Auth::id())
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return redirect()->back();
    }

    /**
     * GET /notifications/unread-count
     * Endpoint para el indicador de la campanita
     */
    public function unreadCount()
    {
        return response()->json([
            'count' => Notification::where('user_id', Auth::id())
                ->where('is_read', false)
                ->count()
        ]);
    }
}