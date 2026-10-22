<?php

namespace App\Http\Controllers;

use App\Models\LockerAssignment;
use App\Models\Locker;
use App\Models\FeeRate;
use App\Models\Fine;
use App\Models\Payment;
use App\Helpers\NotificationHelper;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

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
            ->whereIn('assignment_status', LockerAssignment::VIGENTES)
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
        $query = LockerAssignment::with(['user', 'locker.sector.building', 'payments']);

        if ($request->has('assignment_status') && $request->assignment_status) {
            $query->where('assignment_status', $request->assignment_status);
        }

        if (is_string($request->search) && $request->search !== '') {
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

        if (is_string($request->search) && $request->search !== '') {
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
            ->whereIn('assignment_status', LockerAssignment::VIGENTES)
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

            $feeRate = FeeRate::vigente($locker->locker_type);

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
            report($e);

            return redirect()->back()->withErrors(['error' => 'No se pudo crear la asignación. Intenta de nuevo.']);
        }
    }

    /**
     * Pagos pendientes/vencidos o multas sin resolver impiden devolver el locker.
     * Las multas no tienen estado: el admin las elimina cuando se pagan.
     * Devuelve el error a mostrar, o null si se puede devolver.
     */
    private function bloqueoDeDevolucion(LockerAssignment $assignment): ?array
    {
        $hasDebts = Payment::where('assignment_id', $assignment->assignment_id)
            ->whereIn('payment_status', ['pending', 'overdue'])
            ->exists();

        if ($hasDebts) {
            return ['payments' => 'No se puede devolver: hay pagos pendientes o vencidos vinculados a este locker.'];
        }

        if (Fine::where('assignment_id', $assignment->assignment_id)->exists()) {
            return ['fines' => 'No se puede devolver: hay multas sin pagar vinculadas a este locker.'];
        }

        return null;
    }

    /**
     * POST /devolucion-locker/{id}
     * El estudiante solicita devolver su locker. El locker sigue ocupado
     * hasta que un admin confirme la entrega con release().
     */
    public function requestReturn($id)
    {
        try {
            return DB::transaction(function () use ($id) {
                $assignment = LockerAssignment::whereKey($id)->lockForUpdate()->firstOrFail();

                // Solo el dueño puede solicitar la devolución
                if ((int) $assignment->user_id !== (int) auth()->id()) {
                    abort(403);
                }

                if ($assignment->assignment_status === 'return_requested') {
                    return redirect()->back()->withErrors(['assignment' => 'Ya solicitaste la devolución. Entrega la llave en el Decanato para completarla.']);
                }

                if ($assignment->assignment_status !== 'active') {
                    return redirect()->back()->withErrors(['assignment' => 'Este locker ya fue devuelto.']);
                }

                if ($error = $this->bloqueoDeDevolucion($assignment)) {
                    return redirect()->back()->withErrors($error);
                }

                $assignment->update(['assignment_status' => 'return_requested']);

                NotificationHelper::send(
                    $assignment->user_id,
                    'return_requested',
                    'Devolución solicitada',
                    'Entrega la llave en el Decanato. Tu locker quedará liberado cuando el administrador confirme la devolución.'
                );

                return redirect()->back()->with('success', 'Devolución solicitada. Entrega la llave en el Decanato para completarla.');
            });
        } catch (HttpExceptionInterface|ModelNotFoundException $e) {
            throw $e;
        } catch (\Exception $e) {
            report($e);

            return redirect()->back()->withErrors(['error' => 'No se pudo solicitar la devolución. Intenta de nuevo.']);
        }
    }

    /**
     * PUT /admin/assignments/{id}/release
     * El admin libera el locker: confirma una devolución solicitada o libera
     * directamente una asignación activa. Bloqueado por pagos o multas pendientes.
     */
    public function release($id)
    {
        try {
            return DB::transaction(function () use ($id) {
                // Bloquear la fila evita que dos liberaciones simultáneas pasen las validaciones
                $assignment = LockerAssignment::whereKey($id)->lockForUpdate()->firstOrFail();

                // La ruta ya está en el grupo admin; se comprueba también aquí por si se reutiliza
                if (! auth()->user()->is_admin) {
                    abort(403);
                }

                if ($assignment->assignment_status === 'released') {
                    return redirect()->back()->withErrors(['assignment' => 'Este locker ya fue liberado anteriormente.']);
                }

                // 1. Verificar pagos pendientes/vencidos y multas
                if ($error = $this->bloqueoDeDevolucion($assignment)) {
                    return redirect()->back()->withErrors($error);
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

                // Retornar respuesta compatible con Inertia
                return redirect()->back()->with('success', 'Asignación liberada con éxito.');
            });
        } catch (HttpExceptionInterface|ModelNotFoundException $e) {
            // 403 y 404 se responden como tales, no como error de BD
            throw $e;
        } catch (\Exception $e) {
            report($e);

            return redirect()->back()->withErrors(['error' => 'No se pudo liberar el locker. Intenta de nuevo.']);
        }
    }
}
