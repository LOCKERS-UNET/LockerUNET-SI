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
     * Visor de notificaciones in-app
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

        $notifications = $query->orderBy('created_at', 'desc')->get();

        return Inertia::render('User/Notificaciones', [ // Ajuste de la vista
            'notificaciones' => $notifications
        ]);
    }

    /**
     * PATCH /notifications/{id}/read
     */
    public function markRead($id)
    {
        $notification = Notification::findOrFail($id);

        // Seguridad estricta
        if ($notification->user_id !== Auth::id()) {
            abort(403);
        }

        $notification->update(['is_read' => true]);

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
     * Endpoint ligero (Ajax) para la burbuja de la campana en frontend
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
