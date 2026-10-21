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

    public function test_un_estudiante_puede_devolver_su_propio_locker(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $dueno = User::factory()->create(['is_admin' => false]);
        $id = $this->crearAsignacion($dueno, $admin);

        $this->actingAs($dueno)->post("/devolucion-locker/{$id}")->assertRedirect();

        $this->assertSame('released', LockerAssignment::find($id)->assignment_status);
    }

    public function test_un_admin_puede_liberar_cualquier_asignacion(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $estudiante = User::factory()->create(['is_admin' => false]);
        $id = $this->crearAsignacion($estudiante, $admin);

        $this->actingAs($admin)->put("/admin/assignments/{$id}/release")->assertRedirect();

        $this->assertSame('released', LockerAssignment::find($id)->assignment_status);
    }
}
