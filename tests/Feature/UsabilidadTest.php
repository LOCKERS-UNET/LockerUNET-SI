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
}
