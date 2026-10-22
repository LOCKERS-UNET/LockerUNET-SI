<?php

namespace Tests\Feature\Admin;

use App\Models\Fine;
use App\Models\Incident;
use App\Models\Locker;
use App\Models\LockerAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class MultasIncidenciasTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['is_admin' => true]);
    }

    private function crearIncidencia(string $estado = 'pending'): Incident
    {
        return Incident::create([
            'user_id' => User::factory()->create(['is_admin' => false])->id,
            'locker_id' => Locker::factory()->create()->locker_id,
            'description' => 'El candado no abre',
            'status' => $estado,
        ]);
    }

    // ---------- Multas ----------

    public function test_el_formulario_de_multa_se_abre_por_carnet(): void
    {
        $estudiante = User::factory()->create(['is_admin' => false, 'card_code' => '24680']);

        $this->actingAs($this->admin)->get('/admin/multas/24680')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/CrearMulta')
                ->where('user.id', $estudiante->id));
    }

    public function test_un_carnet_inexistente_responde_404(): void
    {
        $this->actingAs($this->admin)->get('/admin/multas/00000')->assertNotFound();
    }

    public function test_crear_una_multa_la_asocia_al_locker_activo(): void
    {
        $asignacion = LockerAssignment::factory()->create();

        $this->actingAs($this->admin)->post('/admin/fines', ['user_id' => $asignacion->user_id, 'amount' => 15.5, 'reason' => 'Llave perdida'])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('fines', ['assignment_id' => $asignacion->assignment_id, 'user_id' => $asignacion->user_id, 'amount' => 15.5, 'created_by' => $this->admin->id]);
        $this->assertDatabaseHas('notifications', ['user_id' => $asignacion->user_id, 'notification_type' => 'fine_added']);
    }

    public function test_se_puede_multar_con_la_devolucion_pendiente(): void
    {
        $asignacion = LockerAssignment::factory()->devolucionSolicitada()->create();

        $this->actingAs($this->admin)->post('/admin/fines', ['user_id' => $asignacion->user_id, 'amount' => 5, 'reason' => 'Locker sucio'])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('fines', ['assignment_id' => $asignacion->assignment_id]);
    }

    public function test_no_se_multa_a_quien_no_tiene_locker(): void
    {
        $sinLocker = User::factory()->create(['is_admin' => false]);
        LockerAssignment::factory()->liberada()->create(['user_id' => $sinLocker->id]);

        $this->actingAs($this->admin)->post('/admin/fines', ['user_id' => $sinLocker->id, 'amount' => 5, 'reason' => 'x'])
            ->assertSessionHasErrors('user');

        $this->assertDatabaseCount('fines', 0);
    }

    public function test_crear_una_multa_valida_monto_y_motivo(): void
    {
        $asignacion = LockerAssignment::factory()->create();

        $this->actingAs($this->admin)->post('/admin/fines', ['user_id' => $asignacion->user_id, 'amount' => 0, 'reason' => ''])
            ->assertSessionHasErrors(['amount', 'reason']);

        $this->actingAs($this->admin)->post('/admin/fines', ['user_id' => $asignacion->user_id, 'amount' => -3, 'reason' => str_repeat('a', 501)])
            ->assertSessionHasErrors(['amount', 'reason']);

        $this->assertDatabaseCount('fines', 0);
    }

    public function test_eliminar_una_multa_notifica_al_estudiante(): void
    {
        $asignacion = LockerAssignment::factory()->create();
        $multa = Fine::create(['assignment_id' => $asignacion->assignment_id, 'user_id' => $asignacion->user_id, 'amount' => 5, 'reason' => 'x', 'created_by' => $this->admin->id]);

        $this->actingAs($this->admin)->delete("/admin/fines/{$multa->fine_id}")
            ->assertSessionHasNoErrors();

        $this->assertDatabaseMissing('fines', ['fine_id' => $multa->fine_id]);
        $this->assertDatabaseHas('notifications', ['user_id' => $asignacion->user_id, 'notification_type' => 'fine_removed']);
    }

    public function test_eliminar_una_multa_inexistente_responde_404(): void
    {
        $this->actingAs($this->admin)->delete('/admin/fines/999')->assertNotFound();
    }

    // ---------- Incidencias ----------

    public function test_el_admin_ve_y_filtra_las_incidencias(): void
    {
        $this->crearIncidencia();
        $revisada = $this->crearIncidencia('reviewed');

        $this->actingAs($this->admin)->get('/admin/incidents?status=reviewed')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Incidencias')
                ->has('incidents', 1)
                ->where('incidents.0.incident_id', $revisada->incident_id));
    }

    public function test_revisar_una_incidencia_guarda_quien_y_cuando(): void
    {
        $incidencia = $this->crearIncidencia();

        $this->actingAs($this->admin)->put("/admin/incidents/{$incidencia->incident_id}/review")
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('incidents', ['incident_id' => $incidencia->incident_id, 'status' => 'reviewed', 'reviewed_by' => $this->admin->id]);
        $this->assertNotNull($incidencia->fresh()->reviewed_at);
        $this->assertDatabaseHas('notifications', ['user_id' => $incidencia->user_id, 'notification_type' => 'incident_reviewed']);
    }

    public function test_no_se_revisa_dos_veces(): void
    {
        $incidencia = $this->crearIncidencia('reviewed');

        $this->actingAs($this->admin)->put("/admin/incidents/{$incidencia->incident_id}/review")
            ->assertSessionHasErrors('incident');

        $this->assertDatabaseCount('notifications', 0);
    }

    public function test_revisar_una_incidencia_inexistente_responde_404(): void
    {
        $this->actingAs($this->admin)->put('/admin/incidents/999/review')->assertNotFound();
    }
}
