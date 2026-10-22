<?php

namespace Tests\Feature\Admin;

use App\Models\Fine;
use App\Models\Locker;
use App\Models\LockerAssignment;
use App\Models\Payment;
use App\Models\Semester;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class UsuariosEstadisticasTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['is_admin' => true, 'name' => 'Admin', 'lastname' => 'Decanato', 'card_code' => '1']);
    }

    private function estudiante(array $datos = []): User
    {
        return User::factory()->create(array_merge(['is_admin' => false], $datos));
    }

    // ---------- Usuarios ----------

    public function test_el_listado_muestra_el_locker_activo_de_cada_usuario(): void
    {
        $asignacion = LockerAssignment::factory()->create();

        $this->actingAs($this->admin)->get('/admin/users?search='.$asignacion->user->email)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Usuarios')
                ->has('users', 1)
                ->where('users.0.id', $asignacion->user_id)
                ->where('users.0.locker_code', $asignacion->locker->locker_code)
                ->missing('users.0.password'));
    }

    public function test_el_listado_busca_por_carnet(): void
    {
        $this->estudiante(['card_code' => '31415']);
        $this->estudiante(['card_code' => '27182']);

        $this->actingAs($this->admin)->get('/admin/users?search=31415')
            ->assertInertia(fn (Assert $page) => $page
                ->has('users', 1)
                ->where('users.0.card_code', '31415'));
    }

    public function test_la_busqueda_json_incluye_la_asignacion_activa(): void
    {
        $asignacion = LockerAssignment::factory()->create(['user_id' => $this->estudiante(['name' => 'Zoraida'])->id]);

        $this->actingAs($this->admin)->getJson('/admin/users/search?search=Zoraida')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.locker_assignment.assignment_id', $asignacion->assignment_id)
            ->assertJsonMissingPath('0.password');
    }

    public function test_el_detalle_muestra_multas_y_pagos_pendientes(): void
    {
        $asignacion = LockerAssignment::factory()->create();
        Fine::create(['assignment_id' => $asignacion->assignment_id, 'user_id' => $asignacion->user_id, 'amount' => 5, 'reason' => 'x', 'created_by' => $this->admin->id]);
        foreach (['pending', 'overdue', 'paid'] as $estado) {
            Payment::create(['assignment_id' => $asignacion->assignment_id, 'user_id' => $asignacion->user_id, 'amount' => 60, 'due_date' => today(), 'payment_status' => $estado, 'semester' => '2026-2']);
        }

        $this->actingAs($this->admin)->get("/admin/users/{$asignacion->user_id}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/VistaUsuario')
                ->where('user.id', $asignacion->user_id)
                ->where('assignment.assignment_id', $asignacion->assignment_id)
                ->has('multa', 1)
                ->has('pendingPayments', 2)
                ->missing('user.password'));
    }

    public function test_el_detalle_de_un_usuario_inexistente_responde_404(): void
    {
        $this->actingAs($this->admin)->get('/admin/users/999')->assertNotFound();
    }

    // ---------- Estadísticas ----------

    public function test_el_resumen_cuenta_los_lockers_por_estado(): void
    {
        Locker::factory()->count(3)->create();
        Locker::factory()->ocupado()->count(2)->create();
        Locker::factory()->enMantenimiento()->create();

        $this->actingAs($this->admin)->getJson('/admin/stats/summary')
            ->assertOk()
            ->assertExactJson(['total' => 6, 'occupied' => 2, 'available' => 3, 'maintenance' => 1]);
    }

    public function test_la_grafica_acumula_las_asignaciones_del_mes_por_dia(): void
    {
        Carbon::setTestNow('2026-10-20 12:00:00');
        LockerAssignment::factory()->create(['created_at' => '2026-10-03 09:00:00']);
        LockerAssignment::factory()->count(2)->create(['created_at' => '2026-10-10 09:00:00']);
        LockerAssignment::factory()->liberada()->create(['created_at' => '2026-10-10 09:00:00']); // no cuenta
        LockerAssignment::factory()->create(['created_at' => '2026-09-28 09:00:00']); // otro mes

        $this->actingAs($this->admin)->get('/estadisticas-lockers?mes=Octubre%202026')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/EstadisticasLockers')
                ->where('stats.uso_proyectado', 3)
                ->has('stats.chart_data', 31)
                ->where('stats.chart_data.1', 0)
                ->where('stats.chart_data.2', 1)
                ->where('stats.chart_data.9', 3)
                ->where('stats.chart_data.30', 3));

        Carbon::setTestNow();
    }

    public function test_las_estadisticas_filtran_por_carrera(): void
    {
        LockerAssignment::factory()->create(['user_id' => $this->estudiante(['career' => 'Ingeniería Civil'])->id]);
        LockerAssignment::factory()->create(['user_id' => $this->estudiante(['career' => 'Arquitectura'])->id]);

        $this->actingAs($this->admin)->get('/estadisticas-lockers?carrera='.urlencode('Arquitectura'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('stats.uso_proyectado', 1)
                ->where('filters.carrera', 'Arquitectura'));
    }

    public function test_las_estadisticas_filtran_por_semestre(): void
    {
        Semester::create(['name' => '2026-1', 'start_month' => 1, 'start_year' => 2026, 'end_month' => 6, 'end_year' => 2026, 'is_active' => true]);
        LockerAssignment::factory()->create(['created_at' => '2026-03-15 09:00:00']);
        LockerAssignment::factory()->create(['created_at' => '2026-08-15 09:00:00']);

        $this->actingAs($this->admin)->get('/estadisticas-lockers?semestre=2026-1')
            ->assertInertia(fn (Assert $page) => $page
                ->where('stats.uso_proyectado', 1)
                ->has('semestresDisponibles', 1));
    }

    public function test_crear_un_semestre(): void
    {
        $this->actingAs($this->admin)->post('/admin/semesters', ['name' => '2027-1', 'start_month' => 1, 'start_year' => 2027, 'end_month' => 6, 'end_year' => 2027])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('semesters', ['name' => '2027-1', 'is_active' => true]);

        $this->actingAs($this->admin)->getJson('/admin/semesters')
            ->assertOk()
            ->assertJsonPath('0.name', '2027-1');
    }

    public function test_un_semestre_no_termina_antes_de_empezar_ni_se_repite(): void
    {
        $this->actingAs($this->admin)->post('/admin/semesters', ['name' => '2027-2', 'start_month' => 9, 'start_year' => 2027, 'end_month' => 2, 'end_year' => 2027])
            ->assertSessionHasErrors('end_month');

        Semester::create(['name' => '2027-1', 'start_month' => 1, 'start_year' => 2027, 'end_month' => 6, 'end_year' => 2027, 'is_active' => true]);

        $this->actingAs($this->admin)->post('/admin/semesters', ['name' => '2027-1', 'start_month' => 13, 'start_year' => 2019, 'end_month' => 6, 'end_year' => 2027])
            ->assertSessionHasErrors(['name', 'start_month', 'start_year']);

        $this->assertDatabaseCount('semesters', 1);
    }
}
