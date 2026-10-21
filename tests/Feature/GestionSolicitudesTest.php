<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class GestionSolicitudesTest extends TestCase
{
    use RefreshDatabase;

    private function crearSolicitud(User $estudiante, int $estadoLocker = 0): array
    {
        $buildingId = DB::table('buildings')->insertGetId(['building_code' => 'E-'.uniqid(), 'building_name' => 'Edificio de prueba']);
        $sectorId = DB::table('sectors')->insertGetId(['building_id' => $buildingId, 'sector_name' => 'Planta Baja']);
        $lockerId = DB::table('lockers')->insertGetId(['locker_code' => 'L-'.uniqid(), 'sector_id' => $sectorId, 'locker_type' => 'small', 'status' => $estadoLocker]);
        $requestId = DB::table('locker_requests')->insertGetId(['user_id' => $estudiante->id, 'locker_id' => $lockerId, 'request_status' => 'pending']);

        return [$requestId, $lockerId];
    }

    private function crearArancel(User $admin): void
    {
        DB::table('fee_rates')->insert(['locker_type' => 'small', 'monthly_amount' => 10, 'effective_from' => today()->subDay(), 'created_by' => $admin->id]);
    }

    public function test_el_admin_ve_la_pagina_de_solicitudes(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $estudiante = User::factory()->create(['is_admin' => false]);
        $this->crearSolicitud($estudiante);

        $this->actingAs($admin)->get('/admin/requests')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/GestionSolicitudes')
                ->has('requests', 1)
                ->where('requests.0.request_status', 'pending'));
    }

    public function test_aprobar_crea_la_asignacion_y_el_pago(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $estudiante = User::factory()->create(['is_admin' => false]);
        $this->crearArancel($admin);
        [$requestId, $lockerId] = $this->crearSolicitud($estudiante);

        $this->actingAs($admin)->put("/admin/requests/{$requestId}/approve")->assertSessionHasNoErrors();

        $this->assertDatabaseHas('locker_requests', ['request_id' => $requestId, 'request_status' => 'approved', 'reviewed_by' => $admin->id]);
        $this->assertDatabaseHas('lockers', ['locker_id' => $lockerId, 'status' => 1]);
        $this->assertDatabaseHas('locker_assignments', ['request_id' => $requestId, 'user_id' => $estudiante->id, 'assignment_status' => 'active']);
        $this->assertDatabaseHas('payments', ['user_id' => $estudiante->id, 'payment_status' => 'pending']);
    }

    public function test_aprobar_sin_arancel_no_cambia_nada(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $estudiante = User::factory()->create(['is_admin' => false]);
        [$requestId, $lockerId] = $this->crearSolicitud($estudiante);

        $this->actingAs($admin)->put("/admin/requests/{$requestId}/approve")->assertSessionHasErrors('fee_rate');

        $this->assertDatabaseHas('locker_requests', ['request_id' => $requestId, 'request_status' => 'pending']);
        $this->assertDatabaseHas('lockers', ['locker_id' => $lockerId, 'status' => 0]);
        $this->assertDatabaseCount('locker_assignments', 0);
    }

    public function test_rechazar_marca_la_solicitud(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $estudiante = User::factory()->create(['is_admin' => false]);
        [$requestId] = $this->crearSolicitud($estudiante);

        $this->actingAs($admin)->put("/admin/requests/{$requestId}/reject")->assertSessionHasNoErrors();

        $this->assertDatabaseHas('locker_requests', ['request_id' => $requestId, 'request_status' => 'rejected']);
        $this->assertDatabaseCount('locker_assignments', 0);
    }

    public function test_un_estudiante_no_puede_aprobar_solicitudes(): void
    {
        $estudiante = User::factory()->create(['is_admin' => false]);
        [$requestId] = $this->crearSolicitud($estudiante);

        $this->actingAs($estudiante)->put("/admin/requests/{$requestId}/approve")->assertRedirect(route('home'));

        $this->assertDatabaseHas('locker_requests', ['request_id' => $requestId, 'request_status' => 'pending']);
    }
}
