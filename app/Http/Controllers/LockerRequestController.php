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
     * Si el estudiante ya tiene un locker o una solicitud pendiente, el aviso a mostrar
     * (['mensaje', 'enlace', 'texto_enlace']) o null.
     * Antes solo se avisaba al final, después de elegir el locker y marcar la casilla.
     */
    public static function bloqueoParaSolicitar(int $userId): ?array
    {
        $asignacion = LockerAssignment::with('locker')->where('user_id', $userId)
            ->whereIn('assignment_status', LockerAssignment::VIGENTES)->first();
        if ($asignacion) {
            return ['mensaje' => "Ya tienes asignado el locker {$asignacion->locker?->locker_code}.", 'enlace' => '/assignments/my', 'texto_enlace' => 'Ir a Mi Locker'];
        }

        $pendiente = LockerRequest::with('locker')->where('user_id', $userId)
            ->where('request_status', 'pending')->first();
        if ($pendiente) {
            return ['mensaje' => "Ya tienes una solicitud pendiente del locker {$pendiente->locker?->locker_code}. Puedes verla o cancelarla en Mis Solicitudes.", 'enlace' => '/requests/my', 'texto_enlace' => 'Ir a Mis Solicitudes'];
        }

        return null;
    }

    /**
     * GET /solicitud-locker?locker_id=
     * Formulario de solicitud. Los datos del locker salen de la base: antes se
     * mostraban los que venían en la URL (se podía ver un locker y pedir otro).
     */
    public function form(Request $request)
    {
        // Buscar Locker ya muestra el motivo del bloqueo
        if (self::bloqueoParaSolicitar(Auth::id()) !== null) {
            return redirect()->route('buscar-locker');
        }

        // Solo un id entero: con ?locker_id[]=1 find() devolvía una colección y daba un error 500
        $lockerId = filter_var($request->query('locker_id'), FILTER_VALIDATE_INT);
        $locker = $lockerId ? Locker::with('sector.building')->solicitables()->find($lockerId) : null;

        if (! $locker) {
            return redirect()->route('buscar-locker')->withErrors(['locker' => 'Ese locker no existe o ya no está disponible. Elige otro.']);
        }

        $tamanos = ['small' => 'Pequeño', 'mid' => 'Mediano', 'large' => 'Grande'];

        // Arancel vigente para ese tamaño, para que el estudiante sepa el costo antes de pedirlo
        $arancel = FeeRate::where('locker_type', $locker->locker_type)
            ->where('effective_from', '<=', today())
            ->orderBy('effective_from', 'desc')
            ->value('monthly_amount');

        return Inertia::render('User/SolicitudLocker', [
            'lockerData' => [
                'locker_id' => $locker->locker_id,
                'codigo' => $locker->locker_code,
                'tamano' => $tamanos[$locker->locker_type] ?? $locker->locker_type,
                'edificio' => $locker->sector->building->building_name.' - '.$locker->sector->sector_name,
                'arancel_mensual' => $arancel,
            ],
        ]);
    }

    /**
     * POST /requests/{id}/cancel
     * El estudiante cancela su solicitud mientras sigue pendiente
     * (queda como 'cancelled' para no perder el historial)
     */
    public function cancel($id)
    {
        $lockerReq = LockerRequest::where('request_id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        if ($lockerReq->request_status !== 'pending') {
            return redirect()->back()->withErrors(['request' => 'Solo puedes cancelar una solicitud pendiente.']);
        }

        $lockerReq->update(['request_status' => 'cancelled']);

        return redirect()->back()->with('success', 'Solicitud cancelada. Puedes solicitar otro locker cuando quieras.');
    }

    /**
     * POST /requests
     * Crear una solicitud de locker
     */
    public function store(Request $request)
    {
        $userId = Auth::id();

        // Regla 1: Un user solo puede tener UNA assignment activa
        $hasActiveAssignment = LockerAssignment::where('user_id', $userId)
            ->whereIn('assignment_status', LockerAssignment::VIGENTES)
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

        // Regla 3: Solo se puede solicitar un locker disponible y sin solicitud pendiente de otra persona.
        // La fila del locker se bloquea para que dos solicitudes simultáneas no pasen las dos.
        $creada = DB::transaction(function () use ($request, $userId) {
            $locker = Locker::whereKey($request->locker_id)->lockForUpdate()->first();

            if (! $locker || ! Locker::solicitables()->whereKey($locker->locker_id)->exists()) {
                return false;
            }

            LockerRequest::create([
                'user_id' => $userId,
                'locker_id' => $locker->locker_id,
                'request_status' => 'pending',
                'requested_at' => now(),
            ]);

            return true;
        });

        if (! $creada) {
            return redirect()->route('buscar-locker')->withErrors(['locker' => 'Ese locker ya no está disponible o alguien más lo solicitó. Elige otro.']);
        }

        NotificationHelper::send(
            $userId, 
            'request_sent', 
            'Solicitud enviada',
            'Tu solicitud de locker fue enviada. Dirígete al Decanato de Desarrollo Estudiantil.'
        );

        // A 'Mis Solicitudes' para que vea su solicitud en estado Pendiente junto al aviso
        return redirect()->route('mis-solicitudes')->with('success', 'Solicitud enviada. Te avisaremos cuando el administrador la revise.');
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
            report($e);

            return redirect()->back()->withErrors(['error' => 'No se pudo aprobar la solicitud. Intenta de nuevo.']);
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
