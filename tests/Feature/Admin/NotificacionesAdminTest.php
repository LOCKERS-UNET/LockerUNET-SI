<?php

namespace Tests\Feature\Admin;

use App\Models\Locker;
use App\Models\LockerAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class NotificacionesAdminTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $otroAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['is_admin' => true]);
        $this->otroAdmin = User::factory()->create(['is_admin' => true]);
    }

    private function estudiante(): User
    {
        return User::factory()->create(['is_admin' => false, 'name' => 'Ana', 'lastname' => 'Pérez', 'card_code' => '30001']);
    }

    private function avisosDeAdmin(string $tipo): int
    {
        return DB::table('notifications')->where('notification_type', $tipo)->whereIn('user_id', [$this->admin->id, $this->otroAdmin->id])->count();
    }

    public function test_una_solicitud_nueva_avisa_a_todos_los_admins(): void
    {
        $locker = Locker::factory()->create(['locker_code' => 'A-PB-001']);

        $this->actingAs($this->estudiante())->post('/solicitud-locker', ['locker_id' => $locker->locker_id]);

        $this->assertSame(2, $this->avisosDeAdmin('admin_request_new'));
        $this->assertStringContainsString('Ana Pérez (30001) pidió el locker A-PB-001', DB::table('notifications')->where('user_id', $this->admin->id)->value('message'));
    }

    public function test_cancelar_una_solicitud_avisa_a_los_admins(): void
    {
        $estudiante = $this->estudiante();
        $id = DB::table('locker_requests')->insertGetId(['user_id' => $estudiante->id, 'locker_id' => Locker::factory()->create()->locker_id, 'request_status' => 'pending']);

        $this->actingAs($estudiante)->post("/requests/{$id}/cancel");

        $this->assertSame(2, $this->avisosDeAdmin('admin_request_cancelled'));
    }

    public function test_pedir_la_devolucion_avisa_a_los_admins(): void
    {
        $asignacion = LockerAssignment::factory()->create(['user_id' => $this->estudiante()->id]);

        $this->actingAs($asignacion->user)->post("/devolucion-locker/{$asignacion->assignment_id}");

        $this->assertSame(2, $this->avisosDeAdmin('admin_return_requested'));
    }

    public function test_una_incidencia_avisa_a_los_admins(): void
    {
        $asignacion = LockerAssignment::factory()->create(['user_id' => $this->estudiante()->id]);

        $this->actingAs($asignacion->user)->post('/incidents', ['locker_id' => $asignacion->locker_id, 'description' => 'No cierra']);

        $this->assertSame(2, $this->avisosDeAdmin('admin_incident_new'));
    }

    public function test_los_estudiantes_no_reciben_los_avisos_de_admin(): void
    {
        $locker = Locker::factory()->create();
        $estudiante = $this->estudiante();

        $this->actingAs($estudiante)->post('/solicitud-locker', ['locker_id' => $locker->locker_id]);

        $this->assertDatabaseMissing('notifications', ['user_id' => $estudiante->id, 'notification_type' => 'admin_request_new']);
    }

    public function test_el_admin_ve_sus_notificaciones_y_el_contador(): void
    {
        $this->actingAs($this->estudiante())->post('/solicitud-locker', ['locker_id' => Locker::factory()->create()->locker_id]);

        $this->actingAs($this->admin)->getJson('/notifications/unread-count')->assertExactJson(['count' => 1]);
        $this->actingAs($this->admin)->get('/notifications')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('User/Notificaciones')
                ->has('notificaciones', 1)
                ->where('notificaciones.0.title', 'Nueva solicitud de locker'));
    }
}
