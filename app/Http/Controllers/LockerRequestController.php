<?php

namespace App\Http\Controllers;

use App\Models\LockerRequest;
use App\Models\Locker;
use App\Models\LockerAssignment;
use App\Models\FeeRate;
use App\Models\Payment;
use App\Helpers\NotificationHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class LockerRequestController extends Controller
{
    // ==========================================
    // USUARIOS (Auth)
    // ==========================================

    /**
     * POST /requests
     * Crear una solicitud de locker
     */
    public function store(Request $request)
    {
        $userId = Auth::id();

        // Regla 1: Un user solo puede tener UNA assignment activa
        $hasActiveAssignment = LockerAssignment::where('user_id', $userId)
            ->where('assignment_status', 'active')
            ->exists();
        if ($hasActiveAssignment) {
            return redirect()->back()->withErrors(['request' => 'Ya posees un locker asignado físicamente de forma activa.']);
        }

        // Regla 2: Un user solo puede tener UNA request pending
        $hasPendingRequest = LockerRequest::where('user_id', $userId)
            ->where('request_status', 'pending')
            ->exists();
        if ($hasPendingRequest) {
            return redirect()->back()->withErrors(['request' => 'Ya tienes una solicitud en proceso. Debes esperar su aprobación o rechazo.']);
        }

        $request->validate([
            'locker_id' => 'required|exists:lockers,locker_id',
        ]);

        $locker = Locker::findOrFail($request->locker_id);

        // Regla 3: Solo se puede solicitar un locker con status = 0
        if ($locker->status != 0) {
            return redirect()->back()->withErrors(['locker' => 'El locker seleccionado ya no está disponible.']);
        }

        LockerRequest::create([
            'user_id' => $userId,
            'locker_id' => $locker->locker_id,
            'request_status' => 'pending',
            'requested_at' => now(),
        ]);

        NotificationHelper::send(
            $userId, 
            'request_sent', 
            'Solicitud enviada',
            'Tu solicitud de locker fue enviada. Dirígete al Decanato de Desarrollo Estudiantil.'
        );

        return redirect()->route('home')->with('success', 'Solicitud creada con éxito.');
    }

    /**
     * GET /requests/my
     * Solicitudes del usuario autenticado
     */
    public function myRequests()
    {
        $requests = LockerRequest::with(['locker.sector.building'])
            ->where('user_id', Auth::id())
            ->orderBy('requested_at', 'desc')
            ->get();

        return Inertia::render('User/MisSolicitudes', [
            'solicitudes' => $requests
        ]);
    }

    // ==========================================
    // ADMINISTRADORES
    // ==========================================

    /**
     * GET /admin/requests
     * Lista para el admin
     */
    public function index(Request $request)
    {
        $query = LockerRequest::with(['user', 'locker.sector.building']);

        if ($request->has('request_status') && $request->request_status) {
            $query->where('request_status', $request->request_status);
        }

        $requests = $query->orderBy('requested_at', 'desc')->get();

        return Inertia::render('Admin/GestionSolicitudes', [
            'requests' => $requests
        ]);
    }

    /**
     * PUT /admin/requests/{id}/approve
     */
    public function approve($id)
    {
        $lockerReq = LockerRequest::findOrFail($id);

        if ($lockerReq->request_status !== 'pending') {
            return redirect()->back()->withErrors(['request' => 'Esta solicitud ya fue procesada.']);
        }

        $locker = Locker::findOrFail($lockerReq->locker_id);

        if ($locker->status != 0) {
            return redirect()->back()->withErrors(['locker' => 'El locker de esta solicitud fue tomado u ocupado por otra vía. Recomienda al usuario otra opción.']);
        }

        try {
            DB::beginTransaction();

            // 1. Aprobar Request
            $lockerReq->update([
                'request_status' => 'approved',
                'reviewed_by' => Auth::id(),
                'reviewed_at' => now(),
            ]);

            // 2. Ocupar locker
            $locker->update(['status' => 1]);

            // 3. Crear Asignación
            $assignment = LockerAssignment::create([
                'user_id' => $lockerReq->user_id,
                'locker_id' => $locker->locker_id,
                'request_id' => $lockerReq->request_id,
                'start_date' => today(),
                'assignment_status' => 'active',
                'created_by' => Auth::id(),
            ]);

            // 4. Buscar FeeRate
            $feeRate = FeeRate::where('locker_type', $locker->locker_type)
                ->where('effective_from', '<=', today())
                ->orderBy('effective_from', 'desc')
                ->first();

            if (!$feeRate) {
                DB::rollBack();
                return redirect()->back()->withErrors(['fee_rate' => 'No hay arancel definido (FeeRate) para este tipo de locker. Debes crear uno primero.']);
            }

            // 5. Crear payment
            $year = now()->year;
            $half = now()->month <= 6 ? '1' : '2';
            $semester = "{$year}-{$half}";

            Payment::create([
                'assignment_id' => $assignment->assignment_id,
                'user_id' => $lockerReq->user_id,
                'amount' => $feeRate->monthly_amount,
                'due_date' => today()->addDays(30),
                'payment_status' => 'pending',
                'semester' => $semester,
            ]);

            // 6. Notificar
            NotificationHelper::send(
                $lockerReq->user_id, 
                'request_approved', 
                'Solicitud aprobada',
                'Tu solicitud de locker fue aprobada. Dirígete al Decanato para completar el proceso.'
            );

            DB::commit();

            return redirect()->back()->with('success', 'Solicitud aprobada y asignación creada exitosamente.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withErrors(['error' => 'Error de BD: ' . $e->getMessage()]);
        }
    }

    /**
     * PUT /admin/requests/{id}/reject
     */
    public function reject($id)
    {
        $lockerReq = LockerRequest::findOrFail($id);

        if ($lockerReq->request_status !== 'pending') {
            return redirect()->back()->withErrors(['request' => 'Esta solicitud ya fue procesada.']);
        }

        $lockerReq->update([
            'request_status' => 'rejected',
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
        ]);

        NotificationHelper::send(
            $lockerReq->user_id, 
            'request_rejected', 
            'Solicitud rechazada',
            'Tu solicitud de locker fue rechazada. Puedes contactar al Decanato para más información.'
        );

        return redirect()->back()->with('success', 'La solicitud fue rechazada y el usuario notificado.');
    }
}
