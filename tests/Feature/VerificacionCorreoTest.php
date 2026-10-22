<?php

namespace Tests\Feature;

use App\Mail\EmailVerificationCodeMail;
use App\Models\EmailVerificationCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class VerificacionCorreoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
    }

    private function estudianteSinVerificar(): User
    {
        return User::factory()->unverified()->create([
            'email' => 'estudiante@unet.edu.ve',
            'password' => 'secreto123',
            'is_admin' => false,
        ]);
    }

    public function test_registrarse_envia_el_codigo_y_pide_verificar(): void
    {
        $this->post('/register', [
            'name' => 'Ana',
            'lastname' => 'Perez',
            'email' => 'ana@unet.edu.ve',
            'card_code' => '12345',
            'career' => 'Informática',
            'password' => 'secreto123',
            'password_confirmation' => 'secreto123',
        ])->assertRedirect('/verify-email');

        $user = User::where('email', 'ana@unet.edu.ve')->first();
        $this->assertNull($user->email_verified_at);
        Mail::assertSent(EmailVerificationCodeMail::class, fn ($mail) => $mail->hasTo('ana@unet.edu.ve'));
    }

    public function test_sin_verificar_no_entra_a_la_app(): void
    {
        $user = $this->estudianteSinVerificar();

        $this->actingAs($user)->get('/')->assertRedirect(route('verification.notice'));
        $this->actingAs($user)->get('/lockers')->assertRedirect(route('verification.notice'));
    }

    public function test_un_admin_sin_verificar_tampoco_entra(): void
    {
        $admin = User::factory()->unverified()->create(['is_admin' => true]);

        $this->actingAs($admin)->get('/inicio-admin')->assertRedirect(route('verification.notice'));
    }

    public function test_iniciar_sesion_sin_verificar_envia_un_codigo_nuevo(): void
    {
        $user = $this->estudianteSinVerificar();

        $this->post('/login', ['email' => $user->email, 'password' => 'secreto123'])
            ->assertRedirect('/verify-email')
            ->assertSessionHas('status');

        Mail::assertSent(EmailVerificationCodeMail::class, fn ($mail) => $mail->hasTo($user->email));
        $this->assertSame(1, EmailVerificationCode::where('user_id', $user->id)->where('used', false)->count());
    }

    public function test_iniciar_sesion_verificado_entra_normal(): void
    {
        $user = User::factory()->create(['email' => 'ok@unet.edu.ve', 'password' => 'secreto123', 'is_admin' => false]);

        $this->post('/login', ['email' => $user->email, 'password' => 'secreto123'])->assertRedirect(route('home'));

        Mail::assertNothingSent();
    }

    public function test_el_codigo_correcto_verifica_y_da_acceso(): void
    {
        $user = $this->estudianteSinVerificar();
        $this->post('/login', ['email' => $user->email, 'password' => 'secreto123']);

        $codigo = null;
        Mail::assertSent(EmailVerificationCodeMail::class, function ($mail) use (&$codigo) {
            $codigo = $mail->code;

            return true;
        });

        $this->post('/verify-email', ['code' => $codigo])->assertRedirect('/');

        $this->assertNotNull($user->fresh()->email_verified_at);
        $this->get('/')->assertOk();
    }

    public function test_un_codigo_incorrecto_no_verifica(): void
    {
        $user = $this->estudianteSinVerificar();
        $this->post('/login', ['email' => $user->email, 'password' => 'secreto123']);

        $codigoReal = EmailVerificationCode::where('user_id', $user->id)->value('token');
        $codigoMalo = $codigoReal === '000000' ? '111111' : '000000';

        $this->post('/verify-email', ['code' => $codigoMalo])->assertSessionHasErrors('code');

        $this->assertNull($user->fresh()->email_verified_at);
    }

    public function test_reenviar_invalida_el_codigo_anterior(): void
    {
        $user = $this->estudianteSinVerificar();
        $this->post('/login', ['email' => $user->email, 'password' => 'secreto123']);

        $this->post('/verify-email/resend')->assertSessionHas('status');

        Mail::assertSentCount(2);
        $this->assertSame(1, EmailVerificationCode::where('user_id', $user->id)->where('used', false)->count());
    }

    public function test_la_pagina_de_verificacion_muestra_el_mensaje_de_estado(): void
    {
        $user = $this->estudianteSinVerificar();

        $this->actingAs($user)
            ->withSession(['status' => 'Te enviamos un código nuevo.'])
            ->get('/verify-email')
            ->assertInertia(fn ($page) => $page->component('Auth/VerifyEmail')->where('status', 'Te enviamos un código nuevo.'));
    }

    public function test_el_codigo_pegado_con_espacios_funciona(): void
    {
        $user = $this->estudianteSinVerificar();
        $this->post('/login', ['email' => $user->email, 'password' => 'secreto123']);
        $codigo = EmailVerificationCode::where('user_id', $user->id)->value('token');

        $this->post('/verify-email', ['code' => ' '.substr($codigo, 0, 3).' '.substr($codigo, 3)."\n"])->assertRedirect('/');

        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    public function test_cinco_codigos_incorrectos_bloquean_los_intentos(): void
    {
        $user = $this->estudianteSinVerificar();
        $this->post('/login', ['email' => $user->email, 'password' => 'secreto123']);
        $codigo = EmailVerificationCode::where('user_id', $user->id)->value('token');
        $malo = $codigo === '000000' ? '111111' : '000000';

        for ($i = 0; $i < 5; $i++) {
            $this->post('/verify-email', ['code' => $malo])->assertSessionHasErrors(['code' => 'El código es incorrecto.']);
        }

        // Bloqueado aunque ahora mande el código correcto
        $this->post('/verify-email', ['code' => $codigo])->assertSessionHasErrors('code');
        $this->assertNull($user->fresh()->email_verified_at);
    }

    public function test_no_se_puede_reenviar_dos_veces_seguidas(): void
    {
        $user = $this->estudianteSinVerificar();
        $this->actingAs($user);

        $this->post('/verify-email/resend')->assertSessionHas('status');
        $this->post('/verify-email/resend')->assertSessionHasErrors('email');

        Mail::assertSentCount(1);
    }

    public function test_pedir_un_codigo_nuevo_libera_el_bloqueo(): void
    {
        $user = $this->estudianteSinVerificar();
        $this->post('/login', ['email' => $user->email, 'password' => 'secreto123']);

        for ($i = 0; $i < 5; $i++) {
            $this->post('/verify-email', ['code' => '000000']);
        }
        $this->post('/verify-email', ['code' => '000000'])->assertSessionHasErrors('code');

        $this->post('/verify-email/resend')->assertSessionHas('status');
        $codigo = EmailVerificationCode::where('user_id', $user->id)->where('used', false)->value('token');

        $this->post('/verify-email', ['code' => $codigo])->assertRedirect('/');
        $this->assertNotNull($user->fresh()->email_verified_at);
    }
}
