<?php

namespace Database\Seeders;

use App\Models\Fine;
use App\Models\Incident;
use App\Models\Locker;
use App\Models\LockerAssignment;
use App\Models\LockerRequest;
use App\Models\Payment;
use App\Models\Semester;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Datos para probar el panel admin (capa B de la QA). NO está en DatabaseSeeder:
 * se corre a mano sobre una base recién sembrada, nunca sobre la base real.
 *
 *   php artisan db:seed --class=QaAdminSeeder
 *
 * Estudiantes qa-est1..qa-est10@unet.edu.ve, contraseña "Prueba123.".
 */
class QaAdminSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('is_admin', 1)->firstOrFail();
        $password = Hash::make('Prueba123.');

        $estudiantes = collect(range(1, 10))->map(fn ($n) => User::create([
            'name' => ['Ana', 'Luis', 'María', 'José', 'Carla', 'Pedro', 'Sofía', 'Diego', 'Elena', 'Jorge'][$n - 1],
            'lastname' => ['Pérez', 'Gómez', 'Rondón', 'Mora', 'Silva', 'Ramírez', 'Duque', 'Vivas', 'Contreras', 'Useche'][$n - 1],
            'email' => "qa-est{$n}@unet.edu.ve",
            'password' => $password,
            'card_code' => (string) (30000 + $n),
            'career' => User::CARRERAS[$n % count(User::CARRERAS)],
            'is_admin' => 0,
            'email_verified_at' => now(),
        ]))->values();

        // Primer locker libre y sin solicitudes del tamaño pedido (o de cualquiera, si ya no quedan),
        // en orden para que los datos sean repetibles
        $tomar = function (string $tipo) {
            $libres = Locker::where('status', 0)->whereDoesntHave('requests')->orderBy('locker_id');

            return (clone $libres)->where('locker_type', $tipo)->first() ?? $libres->firstOrFail();
        };

        $asignar = function (User $estudiante, Locker $locker, string $estado = 'active') use ($admin) {
            $locker->update(['status' => $estado === 'released' ? 0 : 1]);

            return LockerAssignment::create([
                'user_id' => $estudiante->id,
                'locker_id' => $locker->locker_id,
                'start_date' => today()->subMonths(2),
                'end_date' => $estado === 'released' ? today()->subWeek() : null,
                'assignment_status' => $estado,
                'created_by' => $admin->id,
            ]);
        };

        $pago = fn (LockerAssignment $a, string $estado, $vence) => Payment::create([
            'assignment_id' => $a->assignment_id,
            'user_id' => $a->user_id,
            'amount' => 21000,
            'due_date' => $vence,
            'payment_status' => $estado,
            'semester' => '2026-2',
        ]);

        // 1-3: asignaciones activas con un pago pendiente, uno vencido y uno pagado
        $a1 = $asignar($estudiantes[0], $tomar('small'));
        $pago($a1, 'pending', today()->addWeeks(2));
        $a2 = $asignar($estudiantes[1], $tomar('mid'));
        $pago($a2, 'overdue', today()->subWeek());
        $a3 = $asignar($estudiantes[2], $tomar('large'));
        $pago($a3, 'paid', today()->subMonth());

        // 4: devolución solicitada y al día: el admin puede confirmarla
        $a4 = $asignar($estudiantes[3], $tomar('small'), 'return_requested');
        $pago($a4, 'paid', today()->subMonth());

        // 5: devolución solicitada con una multa: confirmarla debe fallar
        $a5 = $asignar($estudiantes[4], $tomar('mid'), 'return_requested');
        $pago($a5, 'paid', today()->subMonth());
        Fine::create(['assignment_id' => $a5->assignment_id, 'user_id' => $a5->user_id, 'amount' => 5000, 'reason' => 'Llave entregada con retraso', 'created_by' => $admin->id]);

        // 6: historial ya liberado
        $asignar($estudiantes[5], $tomar('small'), 'released');

        // 7-9: solicitudes pendientes; la 9 es de alguien que ya tiene locker (aprobarla debe fallar)
        foreach ([[6, 'small'], [7, 'large'], [0, 'mid']] as [$i, $tipo]) {
            LockerRequest::create([
                'user_id' => $estudiantes[$i]->id,
                'locker_id' => $tomar($tipo)->locker_id,
                'request_status' => 'pending',
                'requested_at' => now()->subDays(3 - $i % 3),
            ]);
        }

        // Una rechazada y una cancelada para el historial de solicitudes
        LockerRequest::create(['user_id' => $estudiantes[8]->id, 'locker_id' => $tomar('small')->locker_id, 'request_status' => 'rejected', 'requested_at' => now()->subWeeks(2), 'reviewed_by' => $admin->id, 'reviewed_at' => now()->subWeeks(2)]);
        LockerRequest::create(['user_id' => $estudiantes[9]->id, 'locker_id' => $tomar('small')->locker_id, 'request_status' => 'cancelled', 'requested_at' => now()->subWeek()]);

        // Incidencias: dos pendientes y una revisada
        Incident::create(['user_id' => $a1->user_id, 'locker_id' => $a1->locker_id, 'description' => 'El candado está oxidado y cuesta abrirlo.', 'status' => 'pending']);
        Incident::create(['user_id' => $a2->user_id, 'locker_id' => $a2->locker_id, 'description' => 'La puerta del locker no cierra bien.', 'status' => 'pending']);
        Incident::create(['user_id' => $a3->user_id, 'locker_id' => $a3->locker_id, 'description' => 'Falta la placa con el número.', 'status' => 'reviewed', 'reviewed_by' => $admin->id, 'reviewed_at' => now()->subDays(5)]);

        // Un locker en mantenimiento
        $tomar('large')->update(['status' => 2]);

        // Semestres: el actual (sin pagos generados todavía para 2027-1)
        Semester::create(['name' => '2026-2', 'start_month' => 7, 'start_year' => 2026, 'end_month' => 12, 'end_year' => 2026, 'is_active' => true]);
        Semester::create(['name' => '2027-1', 'start_month' => 1, 'start_year' => 2027, 'end_month' => 6, 'end_year' => 2027, 'is_active' => true]);
    }
}
