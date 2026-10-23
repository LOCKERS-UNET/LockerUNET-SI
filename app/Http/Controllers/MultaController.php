<?php

namespace App\Http\Controllers;

use App\Models\Multa;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class MultaController extends Controller
{
    /**
     * Muestra el formulario para crear una multa a un usuario específico.
     */
    public function index(User $user)
    {
        return Inertia::render('Admin/CrearMulta', [
            'user' => $user
        ]);
    }

    /**
     * Guarda una nueva multa en la tabla 'fines'.
     */
    public function store(Request $request)
    {
        $request->validate([
            'assignment_id' => ['required', 'integer', 'exists:assignments,assignment_id'],
            'user_id'       => ['required', 'integer', 'exists:users,id'],
            'amount'        => ['required', 'numeric', 'min:1'],
            'reason'        => ['required', 'string', 'max:255']
        ]);

        Multa::create([
            'assignment_id' => $request->assignment_id,
            'user_id'       => $request->user_id,
            'amount'        => $request->amount,
            'reason'        => $request->reason,
            'status'        => 'pendiente'
        ]);

        return back()->with('message', 'Multa creada con éxito');
    }

    /**
     * Lista las multas del usuario autenticado.
     */
    public function mis_multas()
    {
        // Traemos todas las multas del usuario para mostrarlas en la vista
        $mis_multas = Multa::where('user_id', Auth::id())->get();

        return Inertia::render('User/MultasUser', [
            'multas' => $mis_multas
        ]);
    }

    /**
     * Elimina una multa específica.
     */
    public function destroy(Multa $multa)
    {
        // Gracias al Route Model Binding y a que definimos $primaryKey en el modelo,
        // $multa ya contiene la instancia correcta usando 'fine_id'.
        $multa->delete();
        
        return back()->with('message', 'Multa eliminada');
    }
}