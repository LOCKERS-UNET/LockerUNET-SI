<?php

namespace Tests\Feature\Admin;

use App\Models\Locker;
use App\Models\LockerAssignment;
use App\Models\Payment;
use App\Models\Semester;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PagosArancelesTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['is_admin' => true]);
    }

    private function crearArancel(string $tipo, float $monto, $desde): void
    {
        DB::table('fee_rates')->insert(['locker_type' => $tipo, 'monthly_amount' => $monto, 'effective_from' => $desde, 'created_by' => $this->admin->id]);
    }

    private function crearSemestre(string $nombre = '2027-1'): Semester
    {
        return Semester::create(['name' => $nombre, 'start_month' => 1, 'start_year' => 2027, 'end_month' => 6, 'end_year' => 2027, 'is_active' => true]);
    }

    private function crearPago(string $estado = 'pending'): Payment
    {
        $asignacion = LockerAssignment::factory()->create();

        return Payment::create(['assignment_id' => $asignacion->assignment_id, 'user_id' => $asignacion->user_id, 'amount' => 60, 'due_date' => today()->addMonth(), 'payment_status' => $estado, 'semester' => '2026-2']);
    }

    // ---------- Pagos ----------

    public function test_marcar_un_pago_como_pagado_notifica_al_estudiante(): void
    {
        $pago = $this->crearPago();

        $this->actingAs($this->admin)->patch("/admin/payments/{$pago->payment_id}/paid")
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('payments', ['payment_id' => $pago->payment_id, 'payment_status' => 'paid']);
        $this->assertDatabaseHas('notifications', ['user_id' => $pago->user_id, 'notification_type' => 'payment_confirmed']);
    }

    public function test_un_pago_vencido_tambien_se_puede_marcar_pagado(): void
    {
        $pago = $this->crearPago('overdue');

        $this->actingAs($this->admin)->patch("/admin/payments/{$pago->payment_id}/paid")
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('payments', ['payment_id' => $pago->payment_id, 'payment_status' => 'paid']);
    }

    public function test_no_se_marca_pagado_dos_veces(): void
    {
        $pago = $this->crearPago('paid');

        $this->actingAs($this->admin)->patch("/admin/payments/{$pago->payment_id}/paid")
            ->assertSessionHasErrors('payment');

        $this->assertDatabaseCount('notifications', 0);
    }

    public function test_un_pago_inexistente_responde_404(): void
    {
        $this->actingAs($this->admin)->patch('/admin/payments/999/paid')->assertNotFound();
    }

    // ---------- Aranceles ----------

    public function test_la_pagina_muestra_la_tarifa_vigente_de_cada_tipo(): void
    {
        $this->crearArancel('small', 10, today()->subMonth());
        $this->crearArancel('small', 15, today()->subDay());
        $this->crearArancel('small', 99, today()->addMonth()); // futura: todavía no rige
        $this->crearSemestre();

        $this->actingAs($this->admin)->get('/fee-rates')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Aranceles')
                ->where('rates.small.monthly_amount', fn ($monto) => (float) $monto === 15.0)
                ->where('rates.mid', null)
                ->has('semesters', 1));
    }

    public function test_crear_un_arancel_rige_desde_hoy(): void
    {
        $this->actingAs($this->admin)->post('/admin/fee-rates', ['locker_type' => 'mid', 'monthly_amount' => 12.5, 'reason' => 'Ajuste del semestre'])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('fee_rates', ['locker_type' => 'mid', 'monthly_amount' => 12.5, 'reason' => 'Ajuste del semestre', 'created_by' => $this->admin->id]);
        $this->assertSame(today()->toDateString(), substr(DB::table('fee_rates')->value('effective_from'), 0, 10));
    }

    public function test_crear_un_arancel_valida_tipo_monto_y_motivo(): void
    {
        $this->actingAs($this->admin)->post('/admin/fee-rates', ['locker_type' => 'gigante', 'monthly_amount' => 0])
            ->assertSessionHasErrors(['locker_type', 'monthly_amount', 'reason']);

        $this->actingAs($this->admin)->post('/admin/fee-rates', ['locker_type' => 'small', 'monthly_amount' => -5, 'reason' => 'x'])
            ->assertSessionHasErrors('monthly_amount');

        $this->assertDatabaseCount('fee_rates', 0);
    }

    public function test_con_dos_aranceles_del_mismo_dia_se_cobra_el_ultimo(): void
    {
        $this->actingAs($this->admin)->post('/admin/fee-rates', ['locker_type' => 'small', 'monthly_amount' => 10, 'reason' => 'Primero']);
        $this->actingAs($this->admin)->post('/admin/fee-rates', ['locker_type' => 'small', 'monthly_amount' => 20, 'reason' => 'Corrección']);
        $estudiante = User::factory()->create(['is_admin' => false]);
        $locker = Locker::factory()->create(['locker_type' => 'small']);

        $this->actingAs($this->admin)->post('/admin/assignments', ['user_id' => $estudiante->id, 'locker_id' => $locker->locker_id])
            ->assertSessionHasNoErrors();

        $this->assertSame(20.0, (float) Payment::where('user_id', $estudiante->id)->value('amount'));
        $this->actingAs($this->admin)->get('/fee-rates')
            ->assertInertia(fn (Assert $page) => $page->where('rates.small.monthly_amount', fn ($monto) => (float) $monto === 20.0));
    }

    // ---------- Generar pagos del semestre ----------

    public function test_generar_pagos_cobra_seis_meses_a_las_asignaciones_activas(): void
    {
        $this->crearSemestre();
        $this->crearArancel('small', 10, today()->subDay());
        $activa = LockerAssignment::factory()->create();
        $devolviendo = LockerAssignment::factory()->devolucionSolicitada()->create();
        $liberada = LockerAssignment::factory()->liberada()->create();

        $this->actingAs($this->admin)->post('/admin/payments/generate-semester', ['semester_name' => '2027-1'])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('payments', ['assignment_id' => $activa->assignment_id, 'semester' => '2027-1', 'amount' => 60, 'payment_status' => 'pending']);
        $this->assertDatabaseMissing('payments', ['assignment_id' => $devolviendo->assignment_id]);
        $this->assertDatabaseMissing('payments', ['assignment_id' => $liberada->assignment_id]);
    }

    public function test_generar_pagos_dos_veces_no_duplica(): void
    {
        $this->crearSemestre();
        $this->crearArancel('small', 10, today()->subDay());
        LockerAssignment::factory()->create();

        $this->actingAs($this->admin)->post('/admin/payments/generate-semester', ['semester_name' => '2027-1']);
        $this->actingAs($this->admin)->post('/admin/payments/generate-semester', ['semester_name' => '2027-1']);

        $this->assertDatabaseCount('payments', 1);
    }

    public function test_generar_pagos_exige_un_semestre_existente(): void
    {
        $this->actingAs($this->admin)->post('/admin/payments/generate-semester', ['semester_name' => '1999-9'])
            ->assertSessionHasErrors('semester_name');

        $this->assertDatabaseCount('payments', 0);
    }
}
