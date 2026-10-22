<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
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

    public function test_el_perfil_no_cambia_el_correo_ni_el_carnet(): void
    {
        $estudiante = User::factory()->create(['is_admin' => false, 'email' => 'yo@unet.edu.ve', 'card_code' => '11111']);

        $this->actingAs($estudiante)->post('/edit-profile', [
            'name' => 'Nuevo', 'lastname' => 'Nombre', 'career' => 'Civil',
            'email' => 'otra.persona@unet.edu.ve', 'card_code' => '22222',
        ])->assertSessionHasNoErrors();

        $estudiante->refresh();
        $this->assertSame('yo@unet.edu.ve', $estudiante->email);
        $this->assertSame('11111', (string) $estudiante->card_code);
        $this->assertSame('Nuevo', $estudiante->name);
        $this->assertNotNull($estudiante->email_verified_at);
    }

    public function test_el_perfil_exige_nombre_y_una_carrera_valida(): void
    {
        $estudiante = User::factory()->create(['is_admin' => false, 'name' => 'Ana']);

        $this->actingAs($estudiante)->post('/edit-profile', ['name' => '', 'lastname' => 'X', 'career' => 'Inventada'])
            ->assertSessionHasErrors(['name', 'career']);

        $this->assertSame('Ana', $estudiante->fresh()->name);
    }

    public function test_la_pagina_de_solicitud_usa_los_datos_del_locker_de_la_base(): void
    {
        $estudiante = User::factory()->create(['is_admin' => false]);
        $lockerId = $this->crearLocker();
        $codigo = DB::table('lockers')->where('locker_id', $lockerId)->value('locker_code');

        // Datos falsos en la URL: se ignoran
        $this->actingAs($estudiante)->get("/solicitud-locker?locker_id={$lockerId}&codigo=A-FALSO-001&edificio=Edificio+A")
            ->assertInertia(fn (Assert $page) => $page
                ->where('lockerData.codigo', $codigo)
                ->where('lockerData.tamano', 'Pequeño')
                ->where('lockerData.edificio', 'Edificio de prueba - Planta Baja'));
    }

    public function test_un_locker_ocupado_o_inexistente_vuelve_a_la_busqueda(): void
    {
        $estudiante = User::factory()->create(['is_admin' => false]);
        $ocupado = $this->crearLocker(1);

        $this->actingAs($estudiante)->get("/solicitud-locker?locker_id={$ocupado}")->assertRedirect(route('buscar-locker'))->assertSessionHasErrors('locker');
        $this->actingAs($estudiante)->get('/solicitud-locker?locker_id=999999')->assertRedirect(route('buscar-locker'));
    }

    public function test_buscar_locker_pagina_y_filtra_en_el_servidor(): void
    {
        $estudiante = User::factory()->create(['is_admin' => false]);
        $edificioA = DB::table('buildings')->insertGetId(['building_code' => 'A', 'building_name' => 'Edificio A']);
        $edificioB = DB::table('buildings')->insertGetId(['building_code' => 'B', 'building_name' => 'Edificio B']);
        $sectorA = DB::table('sectors')->insertGetId(['building_id' => $edificioA, 'sector_name' => 'PB']);
        $sectorB = DB::table('sectors')->insertGetId(['building_id' => $edificioB, 'sector_name' => 'PB']);
        for ($i = 1; $i <= 20; $i++) {
            DB::table('lockers')->insert(['locker_code' => sprintf('A-%02d', $i), 'sector_id' => $sectorA, 'locker_type' => $i % 2 ? 'small' : 'large', 'status' => 0]);
        }
        DB::table('lockers')->insert(['locker_code' => 'A-OCUPADO', 'sector_id' => $sectorA, 'locker_type' => 'small', 'status' => 1]);
        DB::table('lockers')->insert(['locker_code' => 'B-01', 'sector_id' => $sectorB, 'locker_type' => 'small', 'status' => 0]);

        // Solo disponibles (21), 15 por página, con el edificio en cada locker
        $this->actingAs($estudiante)->get('/lockers')->assertInertia(fn (Assert $page) => $page
            ->where('lockers.total', 21)
            ->has('lockers.data', 15)
            ->where('lockers.last_page', 2)
            ->has('lockers.data.0.sector.building.building_name')
            ->has('edificios', 2));

        // Filtros en el servidor
        $this->actingAs($estudiante)->get('/lockers?edificio=A&tamano=large')->assertInertia(fn (Assert $page) => $page
            ->where('lockers.total', 10)
            ->where('filtros.edificio', 'A'));

        $this->actingAs($estudiante)->get('/lockers?edificio=B')->assertInertia(fn (Assert $page) => $page
            ->where('lockers.total', 1)
            ->where('lockers.data.0.locker_code', 'B-01'));
    }

    public function test_las_paginas_con_sesion_no_se_guardan_en_cache_y_cifran_el_historial(): void
    {
        $estudiante = User::factory()->create(['is_admin' => false]);

        $respuesta = $this->actingAs($estudiante)->get('/profile');
        $this->assertStringContainsString('no-store', $respuesta->headers->get('Cache-Control'));
        $respuesta->assertInertia(fn (Assert $page) => $page->component('User/Profile'));
        $this->assertTrue($respuesta->viewData('page')['encryptHistory']);
    }

    public function test_cerrar_sesion_invalida_la_sesion_y_limpia_el_historial(): void
    {
        $estudiante = User::factory()->create(['is_admin' => false]);
        $this->actingAs($estudiante)->get('/profile');
        $tokenAntes = session()->token();

        $this->post('/logout')->assertRedirect(route('login'))->assertSessionHas('inertia.clear_history', true);

        $this->assertGuest();
        $this->assertNotSame($tokenAntes, session()->token());
        $this->get('/profile')->assertRedirect(route('login'));
    }

    public function test_el_estudiante_cancela_su_solicitud_pendiente_y_puede_pedir_otra(): void
    {
        $estudiante = User::factory()->create(['is_admin' => false]);
        $otro = User::factory()->create(['is_admin' => false]);
        $id = DB::table('locker_requests')->insertGetId(['user_id' => $estudiante->id, 'locker_id' => $this->crearLocker(), 'request_status' => 'pending']);

        // Nadie más puede cancelarla
        $this->actingAs($otro)->post("/requests/{$id}/cancel")->assertNotFound();

        $this->actingAs($estudiante)->post("/requests/{$id}/cancel")->assertSessionHas('success');
        $this->assertDatabaseHas('locker_requests', ['request_id' => $id, 'request_status' => 'cancelled']);

        // Ya no está "en proceso": puede pedir otro locker
        $this->actingAs($estudiante)->post('/requests', ['locker_id' => $this->crearLocker()])->assertSessionHasNoErrors();

        // Una cancelada no se vuelve a cancelar
        $this->actingAs($estudiante)->post("/requests/{$id}/cancel")->assertSessionHasErrors('request');
    }

    public function test_cambiar_la_contrasena_pide_la_actual(): void
    {
        $estudiante = User::factory()->create(['is_admin' => false, 'password' => 'vieja1234']);

        $this->actingAs($estudiante)->post('/edit-profile/password', ['current_password' => 'equivocada', 'password' => 'nueva12345', 'password_confirmation' => 'nueva12345'])
            ->assertSessionHasErrors(['current_password' => 'La contraseña actual no es correcta.']);

        $this->actingAs($estudiante)->post('/edit-profile/password', ['current_password' => 'vieja1234', 'password' => 'nueva12345', 'password_confirmation' => 'nueva12345'])
            ->assertSessionHasNoErrors()->assertSessionHas('success');

        $this->assertTrue(Hash::check('nueva12345', $estudiante->fresh()->password));
    }

    public function test_la_pagina_de_solicitud_muestra_el_arancel_vigente(): void
    {
        $estudiante = User::factory()->create(['is_admin' => false]);
        $admin = User::factory()->create(['is_admin' => true]);
        DB::table('fee_rates')->insert([
            ['locker_type' => 'small', 'monthly_amount' => 100, 'effective_from' => today()->subMonth(), 'created_by' => $admin->id],
            ['locker_type' => 'small', 'monthly_amount' => 150, 'effective_from' => today()->subDay(), 'created_by' => $admin->id],
            ['locker_type' => 'small', 'monthly_amount' => 999, 'effective_from' => today()->addMonth(), 'created_by' => $admin->id],
        ]);

        $this->actingAs($estudiante)->get('/solicitud-locker?locker_id='.$this->crearLocker())
            ->assertInertia(fn (Assert $page) => $page->where('lockerData.arancel_mensual', fn ($monto) => (float) $monto === 150.0));
    }

    public function test_parametros_en_forma_de_arreglo_no_rompen_la_pagina(): void
    {
        $estudiante = User::factory()->create(['is_admin' => false]);
        $this->crearLocker();

        $this->actingAs($estudiante)->get('/lockers?tamano[]=mid&edificio[]=x')->assertOk();
        $this->actingAs($estudiante)->get('/lockers?page[]=2')->assertOk();
        $this->actingAs($estudiante)->get('/solicitud-locker?locker_id[]=1')->assertRedirect(route('buscar-locker'));
        $this->actingAs($estudiante)->get('/solicitud-locker?locker_id=abc')->assertRedirect(route('buscar-locker'));
        $this->actingAs($estudiante)->get('/notifications?search[]=x')->assertOk();

        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin)->get('/admin/users?search[]=x')->assertOk();
        $this->actingAs($admin)->getJson('/admin/assignments/list?search[]=x')->assertOk();
    }

    public function test_un_locker_con_solicitud_pendiente_no_se_puede_pedir(): void
    {
        $primero = User::factory()->create(['is_admin' => false]);
        $segundo = User::factory()->create(['is_admin' => false]);
        $lockerId = $this->crearLocker();

        $this->actingAs($primero)->post('/requests', ['locker_id' => $lockerId])->assertSessionHasNoErrors();

        // Ya no sale en la búsqueda ni se puede abrir ni pedir
        $this->actingAs($segundo)->get('/lockers')->assertInertia(fn (Assert $page) => $page->where('lockers.total', 0));
        $this->actingAs($segundo)->get("/solicitud-locker?locker_id={$lockerId}")->assertRedirect(route('buscar-locker'));
        $this->actingAs($segundo)->post('/requests', ['locker_id' => $lockerId])->assertSessionHasErrors('locker');
        $this->assertDatabaseCount('locker_requests', 1);

        // Si el primero cancela, vuelve a estar disponible
        $id = DB::table('locker_requests')->value('request_id');
        $this->actingAs($primero)->post("/requests/{$id}/cancel");
        $this->actingAs($segundo)->get('/lockers')->assertInertia(fn (Assert $page) => $page->where('lockers.total', 1));
    }

    public function test_buscar_locker_avisa_si_ya_tiene_una_solicitud_pendiente(): void
    {
        $estudiante = User::factory()->create(['is_admin' => false]);
        $this->crearLocker();
        $otro = $this->crearLocker();

        $this->actingAs($estudiante)->get('/lockers')->assertInertia(fn (Assert $page) => $page->where('bloqueo', null));

        $this->actingAs($estudiante)->post('/requests', ['locker_id' => $otro]);

        $this->actingAs($estudiante)->get('/lockers')->assertInertia(fn (Assert $page) => $page
            ->where('bloqueo.enlace', '/requests/my')
            ->where('bloqueo.mensaje', fn ($m) => str_contains($m, 'Ya tienes una solicitud pendiente')));
        $this->actingAs($estudiante)->get('/solicitud-locker?locker_id='.$this->crearLocker())->assertRedirect(route('buscar-locker'));
    }

    public function test_la_contrasena_no_puede_pasar_de_72_caracteres(): void
    {
        $larga = str_repeat('Z', 73);

        $this->post('/register', ['password' => $larga, 'password_confirmation' => $larga])
            ->assertSessionHasErrors(['password' => 'La contraseña no puede tener más de 72 caracteres.']);

        $estudiante = User::factory()->create(['is_admin' => false, 'password' => 'vieja1234']);
        $this->actingAs($estudiante)->post('/edit-profile/password', ['current_password' => 'vieja1234', 'password' => $larga, 'password_confirmation' => $larga])
            ->assertSessionHasErrors('password');
    }

    public function test_el_registro_limita_los_intentos_con_cuentas_existentes(): void
    {
        User::factory()->create(['email' => 'existe@unet.edu.ve']);

        for ($i = 0; $i < 20; $i++) {
            $this->post('/register', ['email' => 'existe@unet.edu.ve'])->assertSessionHasErrors(['email' => 'El correo ingresado ya está registrado.']);
        }

        $this->post('/register', ['email' => 'existe@unet.edu.ve'])
            ->assertSessionHasErrors(['email' => 'Demasiados intentos con correos o carnets ya registrados. Intenta de nuevo en una hora.']);
        // Un registro con datos nuevos desde la misma IP (otro estudiante de la misma red) sí pasa
        Mail::fake();
        $this->post('/register', [
            'name' => 'Otra', 'lastname' => 'Persona', 'email' => 'nueva@unet.edu.ve', 'card_code' => '54321',
            'career' => 'Civil', 'password' => 'secreto123', 'password_confirmation' => 'secreto123',
        ])->assertRedirect('/verify-email');
    }

    public function test_la_pagina_404_esta_en_espanol(): void
    {
        $this->get('/no-existe-esta-pagina')->assertNotFound()->assertSee('Página no encontrada')->assertSee('Volver al inicio');
    }

    public function test_el_login_bloquea_tras_5_intentos_fallidos(): void
    {
        User::factory()->create(['email' => 'yo@unet.edu.ve', 'password' => 'correcta123', 'is_admin' => false]);

        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['email' => 'yo@unet.edu.ve', 'password' => 'mala'])->assertSessionHasErrors(['auth' => 'Credenciales Incorrectas.']);
        }

        // Bloqueado aunque ahora ponga la contraseña correcta
        $this->post('/login', ['email' => 'yo@unet.edu.ve', 'password' => 'correcta123'])->assertSessionHasErrors('auth');
        $this->assertGuest();
        $this->assertStringStartsWith('Demasiados intentos fallidos', session('errors')->first('auth'));
    }

    public function test_si_la_contrasena_cambia_las_otras_sesiones_se_cierran(): void
    {
        $estudiante = User::factory()->create(['email' => 'yo@unet.edu.ve', 'password' => 'vieja1234', 'is_admin' => false]);

        // Sesión abierta en "otro navegador"
        $this->post('/login', ['email' => 'yo@unet.edu.ve', 'password' => 'vieja1234']);
        $this->get('/profile')->assertOk();

        // La contraseña cambia desde otro lado (perfil en otro equipo o recuperación)
        $estudiante->update(['password' => 'nueva12345']);
        $this->app['auth']->forgetGuards(); // como en una petición real, el usuario se vuelve a leer de la base

        $this->get('/profile')->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_quien_cambia_la_contrasena_sigue_con_su_sesion(): void
    {
        User::factory()->create(['email' => 'yo@unet.edu.ve', 'password' => 'vieja1234', 'is_admin' => false]);
        $this->post('/login', ['email' => 'yo@unet.edu.ve', 'password' => 'vieja1234']);
        $this->get('/edit-profile')->assertOk();

        $this->post('/edit-profile/password', ['current_password' => 'vieja1234', 'password' => 'nueva12345', 'password_confirmation' => 'nueva12345'])
            ->assertSessionHasNoErrors();
        $this->app['auth']->forgetGuards();

        $this->get('/profile')->assertOk();
    }

    public function test_el_inicio_cuenta_los_disponibles_igual_que_la_busqueda(): void
    {
        $estudiante = User::factory()->create(['is_admin' => false]);
        $otro = User::factory()->create(['is_admin' => false]);
        $pedido = $this->crearLocker();
        $edificio = DB::table('lockers')->join('sectors', 'sectors.sector_id', '=', 'lockers.sector_id')->where('locker_id', $pedido)->value('building_id');
        $sector = DB::table('sectors')->where('building_id', $edificio)->value('sector_id');
        DB::table('lockers')->insert([
            ['locker_code' => 'L-LIBRE', 'sector_id' => $sector, 'locker_type' => 'small', 'status' => 0],
            ['locker_code' => 'L-OCUPADO', 'sector_id' => $sector, 'locker_type' => 'small', 'status' => 1],
        ]);
        $this->actingAs($otro)->post('/requests', ['locker_id' => $pedido]);

        $this->actingAs($estudiante)->get('/')->assertInertia(fn (Assert $page) => $page
            ->where('buildings.0.available_lockers', 1)
            ->where('buildings.0.total_lockers', 3));
        $this->actingAs($estudiante)->get('/lockers')->assertInertia(fn (Assert $page) => $page->where('lockers.total', 1));
    }
}
