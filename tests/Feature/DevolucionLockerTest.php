<?php

namespace Tests\Feature;

use App\Models\LockerAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DevolucionLockerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $estudiante;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['is_admin' => true]);
        $this->estudiante = User::factory()->create(['is_admin' => false]);
    }

    private function crearAsignacion(string $estado = 'active'): LockerAssignment
    {
        $buildingId = DB::table('buildings')->insertGetId(['building_code' => 'E-'.uniqid(), 'building_name' => 'Edificio de prueba']);
        $sectorId = DB::table('sectors')->insertGetId(['building_id' => $buildingId, 'sector_name' => 'Planta Baja']);
        $lockerId = DB::table('lockers')->insertGetId(['locker_code' => 'L-'.uniqid(), 'sector_id' => $sectorId, 'locker_type' => 'small', 'status' => 1]);

        $id = DB::table('locker_assignments')->insertGetId([
            'user_id' => $this->estudiante->id,
            'locker_id' => $lockerId,
            'start_date' => today(),
            'assignment_status' => $estado,
            'created_by' => $this->admin->id,
        ]);

        return LockerAssignment::find($id);
    }

    private function multar(LockerAssignment $asignacion): void
    {
        DB::table('fines')->insert(['assignment_id' => $asignacion->assignment_id, 'user_id' => $this->estudiante->id, 'amount' => 5, 'reason' => 'Daño', 'created_by' => $this->admin->id]);
    }

    public function test_el_admin_confirma_una_devolucion_solicitada(): void
    {
        $asignacion = $this->crearAsignacion('return_requested');

        $this->actingAs($this->admin)->put("/admin/assignments/{$asignacion->assignment_id}/release")->assertSessionHasNoErrors();

        $this->assertSame('released', $asignacion->fresh()->assignment_status);
        $this->assertDatabaseHas('lockers', ['locker_id' => $asignacion->locker_id, 'status' => 0]);
    }

    public function test_no_se_puede_solicitar_dos_veces(): void
    {
        $asignacion = $this->crearAsignacion('return_requested');

        $this->actingAs($this->estudiante)->post("/devolucion-locker/{$asignacion->assignment_id}")->assertSessionHasErrors('assignment');

        $this->assertSame('return_requested', $asignacion->fresh()->assignment_status);
    }

    public function test_una_multa_impide_solicitar_la_devolucion(): void
    {
        $asignacion = $this->crearAsignacion();
        $this->multar($asignacion);

        $this->actingAs($this->estudiante)->post("/devolucion-locker/{$asignacion->assignment_id}")->assertSessionHasErrors('fines');

        $this->assertSame('active', $asignacion->fresh()->assignment_status);
    }

    public function test_una_multa_impide_que_el_admin_confirme(): void
    {
        // Ej.: el admin multa al recibir el locker dañado; hasta que se pague (y se elimine) no se libera
        $asignacion = $this->crearAsignacion('return_requested');
        $this->multar($asignacion);

        $this->actingAs($this->admin)->put("/admin/assignments/{$asignacion->assignment_id}/release")->assertSessionHasErrors('fines');

        $this->assertSame('return_requested', $asignacion->fresh()->assignment_status);
        $this->assertDatabaseHas('lockers', ['locker_id' => $asignacion->locker_id, 'status' => 1]);
    }

    public function test_con_devolucion_pendiente_sigue_teniendo_su_locker(): void
    {
        $asignacion = $this->crearAsignacion('return_requested');

        // Sigue viendo su locker y no puede pedir otro mientras tanto
        $this->actingAs($this->estudiante)->get('/assignments/my')
            ->assertInertia(fn ($page) => $page->where('asignacion.assignment_status', 'return_requested'));

        $this->actingAs($this->estudiante)->post('/requests', ['locker_id' => $asignacion->locker_id])->assertSessionHasErrors('request');
    }

    public function test_se_puede_multar_una_asignacion_con_devolucion_pendiente(): void
    {
        $asignacion = $this->crearAsignacion('return_requested');

        $this->actingAs($this->admin)->post('/admin/fines', [
            'user_id' => $this->estudiante->id,
            'amount' => 5,
            'reason' => 'Locker dañado al devolverlo',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('fines', ['assignment_id' => $asignacion->assignment_id]);
    }
}
