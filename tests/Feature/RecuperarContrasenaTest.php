<?php

namespace Tests\Feature;

use App\Mail\ResetCodeMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class RecuperarContrasenaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
    }

    public function test_la_respuesta_no_revela_si_el_correo_existe(): void
    {
        User::factory()->create(['email' => 'existe@unet.edu.ve']);

        $existe = $this->post('/forgot-password', ['email' => 'existe@unet.edu.ve']);
        $this->flushSession();
        $noExiste = $this->post('/forgot-password', ['email' => 'nadie@unet.edu.ve']);

        $existe->assertRedirect('/verify-code')->assertSessionHasNoErrors();
        $noExiste->assertRedirect('/verify-code')->assertSessionHasNoErrors();
        $this->assertSame($existe->getSession()->get('status'), $noExiste->getSession()->get('status'));

        Mail::assertSent(ResetCodeMail::class, 1);
    }

    public function test_un_correo_inexistente_no_puede_avanzar_con_ningun_codigo(): void
    {
        $this->post('/forgot-password', ['email' => 'nadie@unet.edu.ve']);

        $this->get('/verify-code')->assertOk();
        $this->post('/verify-code', ['code' => '123456'])->assertSessionHasErrors(['code' => 'El código es incorrecto.']);
        $this->get('/new-password')->assertRedirect();
    }

    public function test_el_codigo_de_recuperacion_tiene_limite_de_intentos(): void
    {
        User::factory()->create(['email' => 'existe@unet.edu.ve']);
        $this->post('/forgot-password', ['email' => 'existe@unet.edu.ve']);

        for ($i = 0; $i < 5; $i++) {
            $this->post('/verify-code', ['code' => '000000']);
        }

        $this->post('/verify-code', ['code' => '000000'])->assertSessionHasErrors(['code']);
        $this->assertStringStartsWith('Demasiados intentos', session('errors')->first('code'));
    }

    public function test_pedir_el_codigo_dos_veces_seguidas_envia_uno_solo(): void
    {
        User::factory()->create(['email' => 'existe@unet.edu.ve']);

        $this->post('/forgot-password', ['email' => 'existe@unet.edu.ve'])->assertRedirect('/verify-code');
        $this->post('/forgot-password', ['email' => 'existe@unet.edu.ve'])->assertRedirect('/verify-code')->assertSessionHasNoErrors();

        Mail::assertSent(ResetCodeMail::class, 1);
    }
}
