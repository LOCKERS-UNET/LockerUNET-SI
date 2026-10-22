<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

// Arreglos de la prueba de usabilidad (qa-reports/RESUMEN.md)
class UsabilidadTest extends TestCase
{
    use RefreshDatabase;

    private function crearLocker(int $estado = 0): int
    {
        $buildingId = DB::table('buildings')->insertGetId(['building_code' => 'E-'.uniqid(), 'building_name' => 'Edificio de prueba']);
        $sectorId = DB::table('sectors')->insertGetId(['building_id' => $buildingId, 'sector_name' => 'Planta Baja']);

        return DB::table('lockers')->insertGetId(['locker_code' => 'L-'.uniqid(), 'sector_id' => $sectorId, 'locker_type' => 'small', 'status' => $estado]);
    }

    public function test_enviar_una_solicitud_lleva_a_mis_solicitudes_con_un_mensaje(): void
    {
        $estudiante = User::factory()->create(['is_admin' => false]);
        $lockerId = $this->crearLocker();

        $this->actingAs($estudiante)->post('/requests', ['locker_id' => $lockerId])
            ->assertRedirect(route('mis-solicitudes'))
            ->assertSessionHas('success');

        // El mensaje flash llega a la página como prop compartida
        $this->actingAs($estudiante)
            ->withSession(['success' => 'Solicitud enviada.'])
            ->get('/requests/my')
            ->assertInertia(fn (Assert $page) => $page->where('flash.success', 'Solicitud enviada.'));
    }

    public function test_los_errores_de_validacion_salen_en_espanol(): void
    {
        $this->post('/register', ['email' => 'alguien@gmail.com', 'card_code' => ''])
            ->assertSessionHasErrors([
                'lastname' => 'El campo apellido es obligatorio.',
                'card_code' => 'El campo carnet es obligatorio.',
                'email' => 'Usa tu correo institucional (termina en @unet.edu.ve).',
            ]);
    }

    public function test_la_fecha_de_la_solicitud_se_envia_con_zona_horaria(): void
    {
        $estudiante = User::factory()->create(['is_admin' => false]);
        DB::table('locker_requests')->insert(['user_id' => $estudiante->id, 'locker_id' => $this->crearLocker(), 'request_status' => 'pending', 'requested_at' => '2026-10-22 05:37:23']);

        // ISO con zona: el navegador la convierte a hora local (antes llegaba "2026-10-22 05:37:23" y se mostraba en UTC)
        $this->actingAs($estudiante)->get('/requests/my')
            ->assertInertia(fn (Assert $page) => $page->where('solicitudes.0.requested_at', '2026-10-22T05:37:23.000000Z'));
    }

    public function test_el_registro_rechaza_carnets_que_no_son_numeros(): void
    {
        foreach (['-5', '1e3', 'abc', '123456'] as $carnet) {
            $this->post('/register', ['card_code' => $carnet])->assertSessionHasErrors('card_code');
        }

        $this->post('/register', ['card_code' => '12345'])->assertSessionDoesntHaveErrors('card_code');
    }

    public function test_la_contrasena_debe_tener_al_menos_8_caracteres(): void
    {
        $this->post('/register', ['password' => 'corta12', 'password_confirmation' => 'corta12'])
            ->assertSessionHasErrors(['password' => 'El campo contraseña debe tener al menos 8 caracteres.']);
    }
}
