<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Payment;
use App\Helpers\NotificationHelper;

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
        // 1. Marcar pagos vencidos
        Payment::where('due_date', '<', today())
            ->where('payment_status', 'pending')
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
            ->each(function ($payment) {
                NotificationHelper::send(
                    $payment->user_id,
                    'payment_reminder',
                    'Recordatorio de pago',
                    "Tu pago de Bs.{$payment->amount} vence el {$payment->due_date->format('d/m/Y')}. Dirígete al Decanato."
                );
            });

        $this->info('Chequeo de pagos ejecutado correctamente.');
    }
}
