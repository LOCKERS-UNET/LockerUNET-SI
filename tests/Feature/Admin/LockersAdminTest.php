<?php

namespace Tests\Feature\Admin;

use App\Models\Locker;
use App\Models\LockerAssignment;
use App\Models\Sector;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class LockersAdminTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    private function datosLocker(Sector $sector, array $cambios = []): array
    {
        return array_merge([
            'locker_code' => 'NUEVO-001',
            'locker_type' => 'mid',
            'status' => 0,
            'sector_id' => $sector->sector_id,
            'plate_number' => 'P-77',
        ], $cambios);
    }

    public function test_el_admin_ve_y_busca_lockers(): void
    {
        Locker::factory()->create(['locker_code' => 'A-PB-001']);
        Locker::factory()->create(['locker_code' => 'B-P1-002']);

        $this->actingAs($this->admin())->get('/admin/lockers?search=A-PB')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/GestionLockers')
                ->has('lockers', 1)
                ->where('lockers.0.locker_code', 'A-PB-001')
                ->where('filters.search', 'A-PB'));
    }

    public function test_el_admin_filtra_lockers_por_estado(): void
    {
        Locker::factory()->create();
        Locker::factory()->enMantenimiento()->create(['locker_code' => 'MANT-1']);

        $this->actingAs($this->admin())->get('/admin/lockers?status=2')
            ->assertInertia(fn (Assert $page) => $page
                ->has('lockers', 1)
                ->where('lockers.0.locker_code', 'MANT-1'));
    }

    public function test_crear_un_locker(): void
    {
        $sector = Sector::factory()->create();

        $this->actingAs($this->admin())->post('/admin/lockers', $this->datosLocker($sector))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('lockers', ['locker_code' => 'NUEVO-001', 'locker_type' => 'mid', 'status' => 0, 'sector_id' => $sector->sector_id]);
    }

    public function test_crear_valida_codigo_repetido_tipo_estado_y_sector(): void
    {
        Locker::factory()->create(['locker_code' => 'REPETIDO']);

        $this->actingAs($this->admin())->post('/admin/lockers', [
            'locker_code' => 'REPETIDO',
            'locker_type' => 'gigante',
            'status' => 7,
            'sector_id' => 999,
        ])->assertSessionHasErrors(['locker_code', 'locker_type', 'status', 'sector_id']);

        $this->assertDatabaseCount('lockers', 1);
    }

    public function test_el_admin_abre_el_formulario_de_edicion(): void
    {
        $locker = Locker::factory()->create();

        $this->actingAs($this->admin())->get("/admin/lockers/{$locker->locker_id}/edit")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/ModificarLocker')
                ->where('locker.locker_id', $locker->locker_id));
    }

    public function test_editar_un_locker(): void
    {
        $locker = Locker::factory()->create();

        $this->actingAs($this->admin())->put("/admin/lockers/{$locker->locker_id}", $this->datosLocker($locker->sector, ['locker_code' => 'EDITADO', 'locker_type' => 'large']))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('lockers', ['locker_id' => $locker->locker_id, 'locker_code' => 'EDITADO', 'locker_type' => 'large']);
    }

    public function test_editar_conserva_su_propio_codigo(): void
    {
        $locker = Locker::factory()->create(['locker_code' => 'MISMO']);

        $this->actingAs($this->admin())->put("/admin/lockers/{$locker->locker_id}", $this->datosLocker($locker->sector, ['locker_code' => 'MISMO']))
            ->assertSessionHasNoErrors();
    }

    public function test_poner_en_mantenimiento_un_locker_libre(): void
    {
        $locker = Locker::factory()->create();

        $this->actingAs($this->admin())->put("/admin/lockers/{$locker->locker_id}", $this->datosLocker($locker->sector, ['locker_code' => $locker->locker_code, 'status' => 2]))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('lockers', ['locker_id' => $locker->locker_id, 'status' => 2]);
    }

    public function test_no_se_pone_en_mantenimiento_un_locker_asignado(): void
    {
        $asignacion = LockerAssignment::factory()->create();
        $locker = $asignacion->locker;

        $this->actingAs($this->admin())->put("/admin/lockers/{$locker->locker_id}", $this->datosLocker($locker->sector, ['locker_code' => $locker->locker_code, 'status' => 2]))
            ->assertSessionHasErrors('status');

        $this->assertDatabaseHas('lockers', ['locker_id' => $locker->locker_id, 'status' => 1]);
    }

    public function test_no_se_pone_en_mantenimiento_con_devolucion_pendiente(): void
    {
        $asignacion = LockerAssignment::factory()->devolucionSolicitada()->create();
        $locker = $asignacion->locker;

        $this->actingAs($this->admin())->put("/admin/lockers/{$locker->locker_id}", $this->datosLocker($locker->sector, ['locker_code' => $locker->locker_code, 'status' => 2]))
            ->assertSessionHasErrors('status');
    }

    public function test_un_locker_asignado_no_se_marca_como_disponible(): void
    {
        $asignacion = LockerAssignment::factory()->create();
        $locker = $asignacion->locker;

        $this->actingAs($this->admin())->put("/admin/lockers/{$locker->locker_id}", $this->datosLocker($locker->sector, ['locker_code' => $locker->locker_code, 'status' => 0]))
            ->assertSessionHasErrors('status');

        $this->assertDatabaseHas('lockers', ['locker_id' => $locker->locker_id, 'status' => 1]);
    }

    public function test_un_locker_asignado_si_se_puede_editar_sin_cambiar_su_estado(): void
    {
        $asignacion = LockerAssignment::factory()->create();
        $locker = $asignacion->locker;

        $this->actingAs($this->admin())->put("/admin/lockers/{$locker->locker_id}", $this->datosLocker($locker->sector, ['locker_code' => $locker->locker_code, 'status' => 1, 'plate_number' => 'NUEVA']))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('lockers', ['locker_id' => $locker->locker_id, 'plate_number' => 'NUEVA', 'status' => 1]);
    }

    public function test_eliminar_un_locker_disponible_sin_historial(): void
    {
        $locker = Locker::factory()->create();

        $this->actingAs($this->admin())->delete("/admin/lockers/{$locker->locker_id}")
            ->assertSessionHasNoErrors();

        $this->assertDatabaseMissing('lockers', ['locker_id' => $locker->locker_id]);
    }

    public function test_no_se_elimina_un_locker_con_historial(): void
    {
        $asignacion = LockerAssignment::factory()->liberada()->create();
        $asignacion->locker->update(['status' => 0]);

        $this->actingAs($this->admin())->delete("/admin/lockers/{$asignacion->locker_id}")
            ->assertSessionHasErrors('locker');

        $this->assertDatabaseHas('lockers', ['locker_id' => $asignacion->locker_id]);
    }

    public function test_no_se_elimina_un_locker_con_solicitudes_pendientes(): void
    {
        $locker = Locker::factory()->create();
        $estudiante = User::factory()->create(['is_admin' => false]);
        DB::table('locker_requests')->insert(['user_id' => $estudiante->id, 'locker_id' => $locker->locker_id, 'request_status' => 'pending']);

        $this->actingAs($this->admin())->delete("/admin/lockers/{$locker->locker_id}")
            ->assertSessionHasErrors('locker');

        $this->assertDatabaseHas('lockers', ['locker_id' => $locker->locker_id]);
        $this->assertDatabaseCount('locker_requests', 1);
    }

    public function test_no_se_elimina_un_locker_en_mantenimiento(): void
    {
        $locker = Locker::factory()->enMantenimiento()->create();

        $this->actingAs($this->admin())->delete("/admin/lockers/{$locker->locker_id}")
            ->assertSessionHasErrors('locker');

        $this->assertDatabaseHas('lockers', ['locker_id' => $locker->locker_id]);
    }

    public function test_un_locker_inexistente_responde_404(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->get('/admin/lockers/999/edit')->assertNotFound();
        $this->actingAs($admin)->delete('/admin/lockers/999')->assertNotFound();
    }

    public function test_lockers_disponibles_en_json_solo_trae_los_libres(): void
    {
        $libre = Locker::factory()->create();
        Locker::factory()->ocupado()->create();
        Locker::factory()->enMantenimiento()->create();

        $this->actingAs($this->admin())->getJson('/admin/lockers/available')
            ->assertOk()
            ->assertJsonCount(1, 'lockers')
            ->assertJsonPath('lockers.0.locker_id', $libre->locker_id);
    }
}
