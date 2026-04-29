<?php

namespace App\Console\Commands;

use App\Models\LockerAssignment;
use App\Models\Payment;
use App\Models\FeeRate;
use App\Models\Semester;
use Carbon\Carbon;
use Illuminate\Console\Command;

class GenerateSemesterPayments extends Command
{
    protected $signature = 'payments:generate-semester {semester?}';
    protected $description = 'Genera pagos semestrales para todos los usuarios con lockers activos';

    public function handle()
    {
        $semesterName = $this->argument('semester');
        
        // Si no se especifica semestre, buscar el activo más reciente
        if (!$semesterName) {
            $semester = Semester::where('is_active', true)
                ->orderBy('start_year', 'desc')
                ->orderBy('start_month', 'desc')
                ->first();
            
            if (!$semester) {
                $this->error('No hay semestres activos registrados.');
                return 1;
            }
        } else {
            $semester = Semester::where('name', $semesterName)->first();
            if (!$semester) {
                $this->error("Semestre {$semesterName} no encontrado.");
                return 1;
            }
        }

        $this->info("Generando pagos para el semestre: {$semester->name}");

        // Obtener todas las asignaciones activas
        $activeAssignments = LockerAssignment::with(['user', 'locker'])
            ->where('assignment_status', 'active')
            ->get();

        $this->info("Encontradas {$activeAssignments->count()} asignaciones activas");

        $created = 0;
        $skipped = 0;

        foreach ($activeAssignments as $assignment) {
            // Verificar si ya existe un pago para este semestre y asignación
            $existingPayment = Payment::where('assignment_id', $assignment->assignment_id)
                ->where('semester', $semester->name)
                ->first();

            if ($existingPayment) {
                $this->warn("⊗ Usuario {$assignment->user->name} ya tiene pago para {$semester->name}");
                $skipped++;
                continue;
            }

            // Obtener tarifa vigente para el tipo de locker
            $feeRate = FeeRate::where('locker_type', $assignment->locker->locker_type)
                ->where('effective_from', '<=', Carbon::now())
                ->orderBy('effective_from', 'desc')
                ->first();

            if (!$feeRate) {
                $this->error("⊗ No hay tarifa vigente para locker tipo: {$assignment->locker->locker_type}");
                continue;
            }

            // Calcular monto (puede ser mensual * 6 meses o el monto que definas)
            // Ajusta según necesites: mensual, semestral, etc.
            $amount = $feeRate->monthly_amount * 6; // 6 meses = semestre

            // Crear pago
            Payment::create([
                'assignment_id' => $assignment->assignment_id,
                'user_id' => $assignment->user_id,
                'amount' => $amount,
                'due_date' => $semester->end_date, // Fecha límite = fin del semestre
                'payment_status' => 'pending',
                'semester' => $semester->name,
            ]);

            $this->info("✓ Pago creado para {$assignment->user->name} - Locker {$assignment->locker->locker_code}: Bs. {$amount}");
            $created++;
        }

        $this->newLine();
        $this->info("═══════════════════════════════════════");
        $this->info("✅ PROCESO COMPLETADO");
        $this->info("═══════════════════════════════════════");
        $this->info("Pagos creados: {$created}");
        $this->info("Pagos omitidos (ya existen): {$skipped}");
        $this->info("═══════════════════════════════════════");

        return 0;
    }
}