<?php

namespace App\Console\Commands;

use App\Helpers\NotificationHelper;
use App\Models\Payment;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class CheckPayments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:check-payments';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verifica y actualiza estados de pagos vencidos automáticamente (Cron job)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // 1. Marcar pagos vencidos. lazyById y no each(): each() pagina de 1000 en 1000 y, como aquí se cambia
        // el mismo campo por el que se filtra, se saltaba registros (con 1500 vencidos solo marcaba 1000)
        Payment::where('due_date', '<', today())
            ->where('payment_status', 'pending')
            ->lazyById()
            ->each(function ($payment) {
                $payment->update(['payment_status' => 'overdue']);

                NotificationHelper::send(
                    $payment->user_id,
                    'payment_overdue',
                    'Pago vencido',
                    "Tu pago de Bs.{$payment->amount} está vencido. Dirígete al Decanato de Desarrollo Estudiantil."
                );
            });

        // 2. Notificación Recordatorio 5 días antes del vencimiento
        Payment::whereDate('due_date', today()->addDays(5))
            ->where('payment_status', 'pending')
            ->lazyById()
            ->each(function ($payment) {
                NotificationHelper::send(
                    $payment->user_id,
                    'payment_reminder',
                    'Recordatorio de pago',
                    "Tu pago de Bs.{$payment->amount} vence el {$this->fecha($payment->due_date)}. Dirígete al Decanato."
                );
            });

        $this->info('Chequeo de pagos ejecutado correctamente.');
    }

    // due_date llega como texto ("2026-11-21"): Payment no lo convierte en fecha y ->format() fallaba
    private function fecha(mixed $valor): string
    {
        return Carbon::parse($valor)->format('d/m/Y');
    }
}
