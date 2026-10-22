<?php

namespace Tests\Feature;

use App\Models\LockerAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RutasProtegidasTest extends TestCase
{
    use RefreshDatabase;

    private function crearAsignacion(User $estudiante, User $admin): int
    {
        $buildingId = DB::table('buildings')->insertGetId(['building_code' => 'E-'.uniqid(), 'building_name' => 'Edificio de prueba']);
        $sectorId = DB::table('sectors')->insertGetId(['building_id' => $buildingId, 'sector_name' => 'Planta Baja']);
        $lockerId = DB::table('lockers')->insertGetId(['locker_code' => 'L-'.uniqid(), 'sector_id' => $sectorId, 'locker_type' => 'small', 'status' => 1]);

        return DB::table('locker_assignments')->insertGetId([
            'user_id' => $estudiante->id,
            'locker_id' => $lockerId,
            'start_date' => today(),
            'assignment_status' => 'active',
            'created_by' => $admin->id,
        ]);
    }

    public function test_un_estudiante_no_puede_ver_la_pagina_de_aranceles(): void
    {
        $estudiante = User::factory()->create(['is_admin' => false]);

        $this->actingAs($estudiante)->get('/fee-rates')->assertRedirect(route('home'));
    }

    public function test_un_admin_puede_ver_la_pagina_de_aranceles(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)->get('/fee-rates')->assertOk();
    }

    // Ya pasaba antes de ac81933 (la ruta dentro del grupo admin sobrescribia la de afuera); queda como regresion
    public function test_un_estudiante_no_puede_liberar_asignaciones_por_la_ruta_de_admin(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $estudiante = User::factory()->create(['is_admin' => false]);
        $id = $this->crearAsignacion($estudiante, $admin);

        $this->actingAs($estudiante)->put("/admin/assignments/{$id}/release")->assertRedirect(route('home'));

        $this->assertSame('active', LockerAssignment::find($id)->assignment_status);
    }

    public function test_un_estudiante_no_puede_devolver_el_locker_de_otro(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $dueno = User::factory()->create(['is_admin' => false]);
        $otro = User::factory()->create(['is_admin' => false]);
        $id = $this->crearAsignacion($dueno, $admin);

        $this->actingAs($otro)->post("/devolucion-locker/{$id}")->assertForbidden();

        $this->assertSame('active', LockerAssignment::find($id)->assignment_status);
    }

    public function test_un_estudiante_puede_solicitar_la_devolucion_de_su_locker(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $dueno = User::factory()->create(['is_admin' => false]);
        $id = $this->crearAsignacion($dueno, $admin);

        $this->actingAs($dueno)->post("/devolucion-locker/{$id}")->assertRedirect()->assertSessionHasNoErrors();

        // Queda pendiente: el locker sigue ocupado hasta que el admin confirme (DevolucionLockerTest)
        $asignacion = LockerAssignment::find($id);
        $this->assertSame('return_requested', $asignacion->assignment_status);
        $this->assertDatabaseHas('lockers', ['locker_id' => $asignacion->locker_id, 'status' => 1]);
    }

    public function test_un_admin_puede_liberar_cualquier_asignacion(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $estudiante = User::factory()->create(['is_admin' => false]);
        $id = $this->crearAsignacion($estudiante, $admin);

        $this->actingAs($admin)->put("/admin/assignments/{$id}/release")->assertRedirect()->assertSessionHasNoErrors();

        $asignacion = LockerAssignment::find($id);
        $this->assertSame('released', $asignacion->assignment_status);
        $this->assertDatabaseHas('lockers', ['locker_id' => $asignacion->locker_id, 'status' => 0]);
    }

    public function test_la_ruta_de_devolucion_es_solo_para_el_dueno_aunque_sea_admin(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $estudiante = User::factory()->create(['is_admin' => false]);
        $id = $this->crearAsignacion($estudiante, $admin);

        // El admin libera por PUT /admin/assignments/{id}/release
        $this->actingAs($admin)->post("/devolucion-locker/{$id}")->assertForbidden();

        $this->assertSame('active', LockerAssignment::find($id)->assignment_status);
    }

    public function test_no_se_puede_devolver_con_pagos_pendientes_o_vencidos(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $dueno = User::factory()->create(['is_admin' => false]);

        foreach (['pending', 'overdue'] as $estado) {
            $id = $this->crearAsignacion($dueno, $admin);
            DB::table('payments')->insert(['assignment_id' => $id, 'user_id' => $dueno->id, 'amount' => 10, 'due_date' => today(), 'payment_status' => $estado]);

            $this->actingAs($dueno)->post("/devolucion-locker/{$id}")->assertSessionHasErrors('payments');

            $asignacion = LockerAssignment::find($id);
            $this->assertSame('active', $asignacion->assignment_status, "con pago {$estado}");
            $this->assertDatabaseHas('lockers', ['locker_id' => $asignacion->locker_id, 'status' => 1]);
        }
    }

    public function test_devolver_una_asignacion_inexistente_da_404(): void
    {
        $estudiante = User::factory()->create(['is_admin' => false]);

        $this->actingAs($estudiante)->post('/devolucion-locker/999999')->assertNotFound();
    }

    public function test_un_invitado_es_enviado_al_login(): void
    {
        $this->post('/devolucion-locker/1')->assertRedirect('/login');
        $this->put('/admin/assignments/1/release')->assertRedirect('/login');
        $this->get('/fee-rates')->assertRedirect('/login');
    }
}
