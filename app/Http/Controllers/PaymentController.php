<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\FeeRate;
use App\Models\LockerAssignment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

// Este controlador maneja los pagos de aranceles
class PaymentController extends Controller
{
    // INDEX: El estudiante ve sus pagos
    // Ruta: GET /pago-arancel
    public function index()
    {
        // Buscamos los pagos del estudiante logueado
        $pagos = Payment::with(['assignment.locker'])
            ->where('user_id', Auth::id())
            ->get();

        // También traemos las tarifas actuales para mostrarlas
        $tarifas = FeeRate::orderBy('effective_from', 'desc')->get();

        return Inertia::render('User/PagoArancel', [
            'pagos'   => $pagos,
            'tarifas' => $tarifas,
        ]);
    }

    // STORE (Admin): El admin registra un pago para el estudiante
    // Ruta: POST /admin/pagos
    public function store(Request $request)
    {
        $request->validate([
            'assignment_id'  => 'required|exists:locker_assignments,assignment_id',
            'user_id'        => 'required|exists:users,id',
            'amount'         => 'required|numeric|min:0',
            'due_date'       => 'required|date',
            'semester'       => 'nullable|string',
        ]);

        Payment::create([
            'assignment_id'  => $request->assignment_id,
            'user_id'        => $request->user_id,
            'amount'         => $request->amount,
            'due_date'       => $request->due_date,
            'payment_status' => 'pending',
            'semester'       => $request->semester,
        ]);

        return redirect()->back()->with('success', 'Pago generado correctamente.');
    }

    // MARK AS PAID: El admin marca un pago como pagado
    // Ruta: POST /admin/pagos/{id}/pagar
    public function markAsPaid($id)
    {
        $pago = Payment::findOrFail($id);
        $pago->update(['payment_status' => 'paid']);

        return redirect()->back()->with('success', 'Pago marcado como pagado.');
    }
}
