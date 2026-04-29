<?php

namespace App\Http\Controllers;

use App\Models\LockerAssignment;
use App\Models\Locker;
use App\Models\FeeRate;
use App\Models\Payment;
use App\Helpers\NotificationHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class LockerAssignmentController extends Controller
{
    // ==========================================
    // USUARIO (Auth)
    // ==========================================
    
    /**
     * GET /assignments/my
     * Devuelve la actual asgnación activa + el pago pendiente
     */
    public function myAssignment()
    {
        $userId = Auth::id();

        $assignment = LockerAssignment::with(['locker.sector.building', 'payments'])
            ->where('user_id', $userId)
            ->where('assignment_status', 'active')
            ->first();

        $nextPayment = null;

        if ($assignment) {
            $nextPayment = Payment::where('assignment_id', $assignment->assignment_id)
                ->where('payment_status', 'pending')
                ->orderBy('due_date', 'asc')
                ->first();
        }

        return Inertia::render('User/MiLocker', [
            'asignacion' => $assignment,
            'nextPayment' => $nextPayment
        ]);
    }

    // ==========================================
    // ADMIN
    // ==========================================

    /**
     * GET /admin/assignments
     */
    public function index(Request $request)
    {
        $query = LockerAssignment::with(['user', 'locker.sector.building']);

        if ($request->has('assignment_status') && $request->assignment_status) {
            $query->where('assignment_status', $request->assignment_status);
        }

        if ($request->has('search') && $request->search) {
            $searchTerm = $request->search;
            $query->whereHas('user', function ($q) use ($searchTerm) {
                // Asumiendo busqueda por nombre o apellido
                $q->where('name', 'LIKE', '%' . $searchTerm . '%')
                  ->orWhere('lastname', 'LIKE', '%' . $searchTerm . '%')
                  ->orWhere('card_code', 'LIKE', '%' . $searchTerm . '%');
            });
        }

        $assignments = $query->orderBy('start_date', 'desc')->get();

        return Inertia::render('Admin/Asignaciones', [
            'asignaciones' => $assignments
        ]);
    }

    /**
     * GET /admin/assignments/list
     * Devuelve el listado de asignaciones en JSON.
     */
    public function listJson(Request $request)
    {
        $query = LockerAssignment::with(['user', 'locker.sector.building', 'payments']);

        if ($request->has('assignment_status') && $request->assignment_status) {
            $query->where('assignment_status', $request->assignment_status);
        }

        if ($request->has('search') && $request->search) {
            $searchTerm = $request->search;
            $query->whereHas('user', function ($q) use ($searchTerm) {
                $q->where('name', 'LIKE', '%' . $searchTerm . '%')
                  ->orWhere('lastname', 'LIKE', '%' . $searchTerm . '%')
                  ->orWhere('card_code', 'LIKE', '%' . $searchTerm . '%');
            });
        }

        $assignments = $query->orderBy('start_date', 'desc')->get();

        return response()->json([
            'asignaciones' => $assignments
        ]);
    }

    /**
     * POST /admin/assignments
     * Asignación directa sin solicitud de por medio (request_id=null)
     */
    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'locker_id' => 'required|exists:lockers,locker_id',
        ]);

        $userId = $request->user_id;

        // Regla 1
        $hasActiveAssignment = LockerAssignment::where('user_id', $userId)
            ->where('assignment_status', 'active')
            ->exists();
        if ($hasActiveAssignment) {
            return redirect()->back()->withErrors(['user' => 'El usuario ya posee una asignación activa.']);
        }

        $locker = Locker::findOrFail($request->locker_id);

        // Regla 3
        if ($locker->status != 0) {
            return redirect()->back()->withErrors(['locker' => 'El locker seleccionado no está disponible (status != 0).']);
        }

        try {
            DB::beginTransaction();

            $assignment = LockerAssignment::create([
                'user_id' => $userId,
                'locker_id' => $locker->locker_id,
                'request_id' => null, 
                'start_date' => today(),
                'assignment_status' => 'active',
                'created_by' => Auth::id(),
            ]);

            $locker->update(['status' => 1]);

            $feeRate = FeeRate::where('locker_type', $locker->locker_type)
                ->where('effective_from', '<=', today())
                ->orderBy('effective_from', 'desc')
                ->first();

            if (!$feeRate) {
                DB::rollBack();
                return redirect()->back()->withErrors(['fee_rate' => 'No hay arancel definido para este tipo de locker.']);
            }

            $year = now()->year;
            $half = now()->month <= 6 ? '1' : '2';
            $semester = "{$year}-{$half}";

            Payment::create([
                'assignment_id' => $assignment->assignment_id,
                'user_id' => $userId,
                'amount' => $feeRate->monthly_amount,
                'due_date' => today()->addDays(30),
                'payment_status' => 'pending',
                'semester' => $semester,
            ]);

            NotificationHelper::send(
                $userId, 
                'locker_assigned', 
                'Locker asignado',
                'Se te ha asignado un locker. Dirígete al Decanato para completar el proceso.'
            );

            DB::commit();

            return redirect()->back()->with('success', 'Asignación directa exitosa.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withErrors(['error' => 'Error de BD: ' . $e->getMessage()]);
        }
    }

    /**
     * PUT /admin/assignments/{id}/release
     * Libera el locker
     */
    public function release($id)
    {
        // Usar assignment_id explícitamente
        $assignment = LockerAssignment::where('assignment_id', $id)->firstOrFail();
        
        if ($assignment->assignment_status === 'released') {
            return redirect()->back()->withErrors(['assignment' => 'Este locker ya fue liberado anteriormente.']);
        }

        try {
            DB::beginTransaction();

            // 1. Verificar pagos pending o overdue
            $hasDebts = Payment::where('assignment_id', $assignment->assignment_id)
                ->whereIn('payment_status', ['pending', 'overdue'])
                ->exists();

            if ($hasDebts) {
                DB::rollBack();
                return redirect()->back()->withErrors(['payments' => 'No se puede liberar: el usuario tiene pagos pendientes o vencidos vinculados a este locker.']);
            }

            // 2. Liberar assignment
            $assignment->update([
                'assignment_status' => 'released',
                'end_date' => today(),
            ]);

            // 3. Liberar locker (status = 0)
            $locker = Locker::find($assignment->locker_id);
            if ($locker) {
                $locker->update(['status' => 0]);
            }

            // 4. Notificar al usuario
            NotificationHelper::send(
                $assignment->user_id, 
                'locker_released', 
                'Locker liberado',
                'Tu locker ha sido liberado. Cualquier consulta dirígete al Decanato.'
            );

            DB::commit();
            
            // Retornar respuesta compatible con Inertia
            return redirect()->back()->with('success', 'Asignación liberada con éxito.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withErrors(['error' => 'Error de BD: ' . $e->getMessage()]);
        }
    }
}
