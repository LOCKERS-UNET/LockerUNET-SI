<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Helpers\NotificationHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use App\Models\Semester;

class PaymentController extends Controller
{
    // ==========================================
    // USUARIO
    // ==========================================

    /**
     * GET /payments/my
     * Todos los pagos del usuario (historial)
     */
public function myPayments()
{
    // Obtener semestre activo más reciente
    $activeSemester = Semester::where('is_active', true)
        ->orderBy('start_year', 'desc')
        ->orderBy('start_month', 'desc')
        ->first();

    $query = Payment::with(['assignment.locker'])
        ->where('user_id', Auth::id());

    // Si hay semestre activo, mostrar solo esos pagos
    if ($activeSemester) {
        $query->where('semester', $activeSemester->name);
    }

    $payments = $query->orderBy('due_date', 'desc')->get();

    return Inertia::render('User/PagoArancel', [
        'pagos' => $payments,
        'activeSemester' => $activeSemester,
    ]);
}

    /**
     * GET /payments/my/pending
     * Solo los pendientes o vencidos (Para la pantalla de "Pago Arancel" actual)
     */
    public function myPending()
    {
        $payments = Payment::with(['assignment.locker'])
            ->where('user_id', Auth::id())
            ->whereIn('payment_status', ['pending', 'overdue'])
            ->orderBy('due_date', 'asc')
            ->get();

        return Inertia::render('User/PagosPendientes', [
            'pagos' => $payments
        ]);
    }

    // ==========================================
    // ADMIN
    // ==========================================

    /**
     * GET /admin/payments
     */
    public function index(Request $request)
    {
        $query = Payment::with(['user', 'assignment.locker']);

        if ($request->has('user_id') && $request->user_id !== null) {
            $query->where('user_id', $request->user_id);
        }
        if ($request->has('payment_status') && $request->payment_status !== null) {
            $query->where('payment_status', $request->payment_status);
        }
        if ($request->has('semester') && $request->semester !== null) {
            $query->where('semester', $request->semester);
        }

        $payments = $query->orderBy('created_at', 'desc')->get();

        return Inertia::render('Admin/GestionPagos', [
            'payments' => $payments
        ]);
    }

    /**
     * PATCH /admin/payments/{id}/paid
     * Confirma manualmente un pago en la caja del Decanato
     */
    public function markPaid($id)
    {
        $payment = Payment::findOrFail($id);

        if ($payment->payment_status === 'paid') {
            return redirect()->back()->withErrors(['payment' => 'Este pago ya fue marcado como pagado anteriormente.']);
        }

        $payment->update([
            'payment_status' => 'paid',
        ]);

        NotificationHelper::send(
            $payment->user_id, 
            'payment_confirmed', 
            'Pago confirmado',
            "Tu pago de Bs.{$payment->amount} fue registrado exitosamente."
        );

        return redirect()->back()->with('success', 'El pago ha sido marcado como pagado y el usuario notificado.');
    }
}
