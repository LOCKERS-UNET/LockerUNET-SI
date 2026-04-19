<?php

namespace App\Http\Controllers;

use App\Models\Fine;
use App\Models\User;
use App\Models\LockerAssignment;
use App\Helpers\NotificationHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class FineController extends Controller
{
    // ==========================================
    // USUARIO
    // ==========================================

    /**
     * GET /fines/my
     */
    public function myFines()
    {
        $fines = Fine::with(['assignment.locker'])
            ->where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('User/MultasUser', [
            'multa' => $fines
        ]);
    }

    // ==========================================
    // ADMIN
    // ==========================================

    /**
     * GET /admin/users/{userId}/fines
     * Lista de multas aplicadas a un usuario en específico
     */
    public function userFines($userId)
    {
        $user = User::findOrFail($userId);
        
        $fines = Fine::with(['assignment.locker'])
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('Admin/UsuarioMultas', [
            'user' => $user,
            'fines' => $fines
        ]);
    }

    /**
     * GET /admin/multas/{card_code}
     * Muestra el formulario para crear una multa
     */
    public function create(User $user)
    {
        return Inertia::render('Admin/CrearMulta', [
            'user' => $user
        ]);
    }

    /**
     * POST /admin/fines
     * Crear una sanción pecuniaria
     */
    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'amount'  => 'required|numeric|min:0.01',
            'reason'  => 'required|string|max:500',
        ]);

        // Buscamos la assignment activa del usuario a quien multan
        $activeAssignment = LockerAssignment::where('user_id', $request->user_id)
            ->where('assignment_status', 'active')
            ->first();

        if (!$activeAssignment) {
            return redirect()->back()->withErrors([
                'user' => 'El usuario no tiene locker asignado actualmente. Las multas se asocian a un locker activo.'
            ]);
        }

        Fine::create([
            'assignment_id' => $activeAssignment->assignment_id,
            'user_id'       => $request->user_id,
            'amount'        => $request->amount,
            'reason'        => $request->reason,
            'created_by'    => Auth::id(),
        ]);

        NotificationHelper::send(
            $request->user_id, 
            'fine_added', 
            'Multa registrada',
            "Se te ha aplicado una multa de Bs.{$request->amount}. Dirígete al Decanato de Desarrollo Estudiantil."
        );

        return redirect()->back()->with('success', 'Multa registrada y usuario notificado.');
    }

    /**
     * DELETE /admin/fines/{id}
     */
    public function destroy($id)
    {
        $fine = Fine::findOrFail($id);
        $userId = $fine->user_id;

        $fine->delete();

        NotificationHelper::send(
            $userId, 
            'fine_removed', 
            'Multa eliminada',
            'Una multa ha sido eliminada de tu cuenta.'
        );

        return redirect()->back()->with('success', 'Multa eliminada exitosamente.');
    }
}
