<?php

namespace App\Http\Controllers;

use App\Models\Fine;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

// Este controlador maneja las multas de los estudiantes
class FineController extends Controller
{
    // INDEX (Admin): Lista las multas de un estudiante específico
    // Ruta: GET /admin/multas/{card_code}
    public function index(User $user)
    {
        // Buscamos las multas del estudiante con el card_code dado en la URL
        $multas = Fine::with(['assignment.locker', 'admin'])
            ->where('user_id', $user->id)
            ->get();

        return Inertia::render('Admin/VistaUsuario', [
            'usuario' => $user,
            'multas'  => $multas,
        ]);
    }

    // STORE (Admin): El admin crea una multa para un estudiante
    // Ruta: POST /admin/multas
    public function store(Request $request)
    {
        $request->validate([
            'assignment_id' => 'required|exists:locker_assignments,assignment_id',
            'user_id'       => 'required|exists:users,id',
            'amount'        => 'required|numeric|min:0',
            'reason'        => 'required|string',
        ]);

        Fine::create([
            'assignment_id' => $request->assignment_id,
            'user_id'       => $request->user_id,
            'amount'        => $request->amount,
            'reason'        => $request->reason,
            'created_by'    => Auth::id(), // el admin logueado
        ]);

        return redirect()->back()->with('success', 'Multa registrada correctamente.');
    }

    // DESTROY (Admin): El admin elimina una multa
    // Ruta: DELETE /admin/multas/{id}
    public function destroy($id)
    {
        $multa = Fine::findOrFail($id);
        $multa->delete();

        return redirect()->back()->with('success', 'Multa eliminada correctamente.');
    }

    // MIS MULTAS: El estudiante ve sus propias multas
    // Ruta: GET /multas/mis-multas
    public function misMultas()
    {
        $multas = Fine::with(['assignment.locker'])
            ->where('user_id', Auth::id())
            ->get();

        return Inertia::render('User/MisMultas', [
            'multas' => $multas,
        ]);
    }
}
