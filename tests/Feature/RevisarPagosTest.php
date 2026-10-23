<?php

namespace Tests\Feature;

use App\Models\LockerAssignment;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RevisarPagosTest extends TestCase
{
    use RefreshDatabase;

    private function pago(LockerAssignment $asignacion, $vence, string $estado = 'pending'): Payment
    {
        return Payment::create(['assignment_id' => $asignacion->assignment_id, 'user_id' => $asignacion->user_id, 'amount' => 60, 'due_date' => $vence, 'payment_status' => $estado, 'semester' => '2026-2']);
    }

    public function test_marca_los_vencidos_y_avisa(): void
    {
        $asignacion = LockerAssignment::factory()->create();
        $vencido = $this->pago($asignacion, today()->subDay());
        $alDia = $this->pago($asignacion, today()->addMonth());

        $this->artisan('app:check-payments')->assertSuccessful();

        $this->assertSame('overdue', $vencido->fresh()->payment_status);
        $this->assertSame('pending', $alDia->fresh()->payment_status);
        $this->assertDatabaseHas('notifications', ['user_id' => $asignacion->user_id, 'notification_type' => 'payment_overdue']);
    }

    public function test_recuerda_cinco_dias_antes_del_vencimiento(): void
    {
        $asignacion = LockerAssignment::factory()->create();
        $this->pago($asignacion, today()->addDays(5));

        $this->artisan('app:check-payments')->assertSuccessful();

        $this->assertDatabaseHas('notifications', ['user_id' => $asignacion->user_id, 'notification_type' => 'payment_reminder']);
        $this->assertStringContainsString(today()->addDays(5)->format('d/m/Y'), \DB::table('notifications')->where('notification_type', 'payment_reminder')->value('message'));
    }

    public function test_marca_todos_los_vencidos_aunque_sean_muchos(): void
    {
        $asignacion = LockerAssignment::factory()->create();
        Payment::insert(array_fill(0, 1500, ['assignment_id' => $asignacion->assignment_id, 'user_id' => $asignacion->user_id, 'amount' => 60, 'due_date' => today()->subDay(), 'payment_status' => 'pending', 'semester' => '2026-2']));

        $this->artisan('app:check-payments')->assertSuccessful();

        $this->assertSame(0, Payment::where('payment_status', 'pending')->count());
    }
}
