<?php

namespace Tests\Feature\Admin;

use App\Models\Fine;
use App\Models\Locker;
use App\Models\LockerAssignment;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SolicitudesAsignacionesTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['is_admin' => true]);
    }

    private function estudiante(): User
    {
        return User::factory()->create(['is_admin' => false]);
    }

    private function crearArancel(string $tipo = 'small', float $monto = 10): void
    {
        DB::table('fee_rates')->insert(['locker_type' => $tipo, 'monthly_amount' => $monto, 'effective_from' => today()->subDay(), 'created_by' => $this->admin->id]);
    }

    private function crearSolicitud(User $estudiante, Locker $locker, string $estado = 'pending'): int
    {
        return DB::table('locker_requests')->insertGetId(['user_id' => $estudiante->id, 'locker_id' => $locker->locker_id, 'request_status' => $estado, 'requested_at' => now()]);
    }

    private function crearPago(LockerAssignment $asignacion, string $estado): Payment
    {
        return Payment::create(['assignment_id' => $asignacion->assignment_id, 'user_id' => $asignacion->user_id, 'amount' => 60, 'due_date' => today()->addMonth(), 'payment_status' => $estado, 'semester' => '2026-2']);
    }

    // ---------- Solicitudes ----------

    public function test_el_admin_filtra_las_solicitudes_por_estado(): void
    {
        $estudiante = $this->estudiante();
        $this->crearSolicitud($estudiante, Locker::factory()->create());
        $this->crearSolicitud($estudiante, Locker::factory()->create(), 'cancelled');

        $this->actingAs($this->admin)->get('/admin/requests?request_status=cancelled')
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/GestionSolicitudes')
                ->has('requests', 1)
                ->where('requests.0.request_status', 'cancelled'));
    }

    public function test_aprobar_notifica_al_estudiante(): void
    {
        $estudiante = $this->estudiante();
        $this->crearArancel();
        $id = $this->crearSolicitud($estudiante, Locker::factory()->create());

        $this->actingAs($this->admin)->put("/admin/requests/{$id}/approve");

        $this->assertDatabaseHas('notifications', ['user_id' => $estudiante->id, 'notification_type' => 'request_approved']);
    }

    public function test_no_se_aprueba_una_solicitud_ya_procesada(): void
    {
        $this->crearArancel();
        $locker = Locker::factory()->create();
        $id = $this->crearSolicitud($this->estudiante(), $locker, 'rejected');

        $this->actingAs($this->admin)->put("/admin/requests/{$id}/approve")->assertSessionHasErrors('request');

        $this->assertDatabaseCount('locker_assignments', 0);
        $this->assertDatabaseHas('lockers', ['locker_id' => $locker->locker_id, 'status' => 0]);
    }

    public function test_no_se_rechaza_una_solicitud_cancelada(): void
    {
        $id = $this->crearSolicitud($this->estudiante(), Locker::factory()->create(), 'cancelled');

        $this->actingAs($this->admin)->put("/admin/requests/{$id}/reject")->assertSessionHasErrors('request');

        $this->assertDatabaseHas('locker_requests', ['request_id' => $id, 'request_status' => 'cancelled']);
    }

    public function test_no_se_aprueba_si_el_locker_ya_no_esta_libre(): void
    {
        $this->crearArancel();
        $locker = Locker::factory()->enMantenimiento()->create();
        $id = $this->crearSolicitud($this->estudiante(), $locker);

        $this->actingAs($this->admin)->put("/admin/requests/{$id}/approve")->assertSessionHasErrors('locker');

        $this->assertDatabaseHas('locker_requests', ['request_id' => $id, 'request_status' => 'pending']);
        $this->assertDatabaseCount('locker_assignments', 0);
    }

    public function test_rechazar_notifica_al_estudiante(): void
    {
        $estudiante = $this->estudiante();
        $id = $this->crearSolicitud($estudiante, Locker::factory()->create());

        $this->actingAs($this->admin)->put("/admin/requests/{$id}/reject");

        $this->assertDatabaseHas('locker_requests', ['request_id' => $id, 'reviewed_by' => $this->admin->id]);
        $this->assertDatabaseHas('notifications', ['user_id' => $estudiante->id, 'notification_type' => 'request_rejected']);
    }

    public function test_una_solicitud_inexistente_responde_404(): void
    {
        $this->actingAs($this->admin)->put('/admin/requests/999/approve')->assertNotFound();
        $this->actingAs($this->admin)->put('/admin/requests/999/reject')->assertNotFound();
    }

    // ---------- Asignaciones ----------

    public function test_el_admin_ve_y_filtra_las_asignaciones(): void
    {
        LockerAssignment::factory()->create();
        $pendiente = LockerAssignment::factory()->devolucionSolicitada()->create();

        $this->actingAs($this->admin)->get('/admin/assignments?assignment_status=return_requested')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Asignaciones')
                ->has('asignaciones', 1)
                ->where('asignaciones.0.assignment_id', $pendiente->assignment_id));
    }

    public function test_el_listado_json_busca_por_carnet(): void
    {
        $buscada = LockerAssignment::factory()->create(['user_id' => User::factory()->create(['is_admin' => false, 'card_code' => '777'])]);
        LockerAssignment::factory()->create(['user_id' => User::factory()->create(['is_admin' => false, 'card_code' => '111'])]);

        $this->actingAs($this->admin)->getJson('/admin/assignments/list?search=777')
            ->assertOk()
            ->assertJsonCount(1, 'asignaciones')
            ->assertJsonPath('asignaciones.0.assignment_id', $buscada->assignment_id);
    }

    public function test_asignacion_directa_ocupa_el_locker_y_genera_el_pago(): void
    {
        $estudiante = $this->estudiante();
        $locker = Locker::factory()->create(['locker_type' => 'large']);
        $this->crearArancel('large', 25);

        $this->actingAs($this->admin)->post('/admin/assignments', ['user_id' => $estudiante->id, 'locker_id' => $locker->locker_id])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('locker_assignments', ['user_id' => $estudiante->id, 'locker_id' => $locker->locker_id, 'request_id' => null, 'assignment_status' => 'active', 'created_by' => $this->admin->id]);
        $this->assertDatabaseHas('lockers', ['locker_id' => $locker->locker_id, 'status' => 1]);
        $this->assertDatabaseHas('payments', ['user_id' => $estudiante->id, 'amount' => 25, 'payment_status' => 'pending']);
        $this->assertDatabaseHas('notifications', ['user_id' => $estudiante->id, 'notification_type' => 'locker_assigned']);
    }

    public function test_asignacion_directa_sin_arancel_no_deja_rastro(): void
    {
        $estudiante = $this->estudiante();
        $locker = Locker::factory()->create();

        $this->actingAs($this->admin)->post('/admin/assignments', ['user_id' => $estudiante->id, 'locker_id' => $locker->locker_id])
            ->assertSessionHasErrors('fee_rate');

        $this->assertDatabaseCount('locker_assignments', 0);
        $this->assertDatabaseHas('lockers', ['locker_id' => $locker->locker_id, 'status' => 0]);
    }

    public function test_asignacion_directa_rechaza_a_quien_ya_tiene_locker(): void
    {
        $this->crearArancel();
        $existente = LockerAssignment::factory()->create();
        $libre = Locker::factory()->create();

        $this->actingAs($this->admin)->post('/admin/assignments', ['user_id' => $existente->user_id, 'locker_id' => $libre->locker_id])
            ->assertSessionHasErrors('user');

        $this->assertDatabaseHas('lockers', ['locker_id' => $libre->locker_id, 'status' => 0]);
    }

    public function test_asignacion_directa_rechaza_un_locker_ocupado(): void
    {
        $this->crearArancel();
        $ocupado = Locker::factory()->ocupado()->create();

        $this->actingAs($this->admin)->post('/admin/assignments', ['user_id' => $this->estudiante()->id, 'locker_id' => $ocupado->locker_id])
            ->assertSessionHasErrors('locker');

        $this->assertDatabaseCount('locker_assignments', 0);
    }

    public function test_asignacion_directa_valida_usuario_y_locker(): void
    {
        $this->actingAs($this->admin)->post('/admin/assignments', ['user_id' => 999, 'locker_id' => 999])
            ->assertSessionHasErrors(['user_id', 'locker_id']);
    }

    public function test_confirmar_una_devolucion_libera_el_locker(): void
    {
        $asignacion = LockerAssignment::factory()->devolucionSolicitada()->create();

        $this->actingAs($this->admin)->put("/admin/assignments/{$asignacion->assignment_id}/release")
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('locker_assignments', ['assignment_id' => $asignacion->assignment_id, 'assignment_status' => 'released']);
        $this->assertNotNull($asignacion->fresh()->end_date);
        $this->assertDatabaseHas('lockers', ['locker_id' => $asignacion->locker_id, 'status' => 0]);
        $this->assertDatabaseHas('notifications', ['user_id' => $asignacion->user_id, 'notification_type' => 'locker_released']);
    }

    public function test_no_se_libera_con_pagos_vencidos(): void
    {
        $asignacion = LockerAssignment::factory()->devolucionSolicitada()->create();
        $this->crearPago($asignacion, 'overdue');

        $this->actingAs($this->admin)->put("/admin/assignments/{$asignacion->assignment_id}/release")
            ->assertSessionHasErrors('payments');

        $this->assertDatabaseHas('lockers', ['locker_id' => $asignacion->locker_id, 'status' => 1]);
    }

    public function test_no_se_libera_con_multas(): void
    {
        $asignacion = LockerAssignment::factory()->devolucionSolicitada()->create();
        Fine::create(['assignment_id' => $asignacion->assignment_id, 'user_id' => $asignacion->user_id, 'amount' => 5, 'reason' => 'Llave perdida', 'created_by' => $this->admin->id]);

        $this->actingAs($this->admin)->put("/admin/assignments/{$asignacion->assignment_id}/release")
            ->assertSessionHasErrors('fines');

        $this->assertDatabaseHas('locker_assignments', ['assignment_id' => $asignacion->assignment_id, 'assignment_status' => 'return_requested']);
    }

    public function test_con_los_pagos_al_dia_si_se_libera(): void
    {
        $asignacion = LockerAssignment::factory()->create();
        $this->crearPago($asignacion, 'paid');

        $this->actingAs($this->admin)->put("/admin/assignments/{$asignacion->assignment_id}/release")
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('locker_assignments', ['assignment_id' => $asignacion->assignment_id, 'assignment_status' => 'released']);
    }

    public function test_no_se_libera_dos_veces(): void
    {
        $asignacion = LockerAssignment::factory()->liberada()->create();

        $this->actingAs($this->admin)->put("/admin/assignments/{$asignacion->assignment_id}/release")
            ->assertSessionHasErrors('assignment');
    }
}
