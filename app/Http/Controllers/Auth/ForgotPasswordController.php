<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\ResetCodeMail;
use App\Models\PasswordResetCode;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Inertia\Inertia;

class ForgotPasswordController extends Controller
{
    public function show()
    {
        return Inertia::render('Auth/ForgotPassword');
    }

    public function send(Request $request)
    {
        // Sin 'exists': la respuesta es la misma exista o no la cuenta, para no revelar qué correos están registrados
        $request->validate([
            'email' => ['required', 'email', 'ends_with:@unet.edu.ve'],
        ], [
            'email.required' => 'El correo es obligatorio.',
            'email.email' => 'Ingresa un correo válido.',
            'email.ends_with' => 'Usa tu correo institucional (termina en @unet.edu.ve).',
        ]);

        $user = User::where('email', $request->email)->first();
        $mensaje = 'Si el correo está registrado, te enviamos un código de verificación. Revisa también la carpeta de spam.';

        // 'reset_iniciado' habilita /verify-code; con un correo inexistente reset_user_id queda en 0 y ningún código coincide
        session(['reset_iniciado' => true, 'reset_user_id' => $user?->id ?? 0]);

        // Un código por minuto para cada correo (un doble clic generaba dos códigos). Se aplica a cualquier
        // correo, exista o no, así que decir que hay que esperar no revela si la cuenta existe.
        $clave = 'recuperar-clave:'.strtolower($request->email);
        if (RateLimiter::tooManyAttempts($clave, 1)) {
            return redirect('/verify-code')->with('status', 'Ya pediste un código hace menos de un minuto. Revisa tu correo o espera '
                .RateLimiter::availableIn($clave).' segundos para pedir otro.');
        }
        RateLimiter::hit($clave, 60);

        if (! $user) {
            return redirect('/verify-code')->with('status', $mensaje);
        }

        // Eliminar códigos anteriores no usados del mismo usuario
        PasswordResetCode::where('user_id', $user->id)
            ->where('used', false)
            ->delete();

        // Generar código de 6 dígitos
        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // Guardar en BD con expiración de 15 minutos
        PasswordResetCode::create([
            'user_id' => $user->id,      // 👈🏼 Usar user_id
            'token' => $code,          // 👈🏼 Usar 'token' (nombre real de la columna)
            'expires_at' => now()->addMinutes(15),
            'used' => false,
        ]);

        // Enviar el código por correo
        try {
            Mail::to($user->email)->send(new ResetCodeMail($code));
        } catch (\Exception $e) {
            // En desarrollo, mostrar el código en consola para pruebas
            if (app()->environment('local')) {
                logger()->error('Error al enviar correo: '.$e->getMessage());
                logger()->info("Código de verificación para {$user->email}: $code");
            }

            return back()->withErrors([
                'email' => 'No se pudo enviar el correo. Revisa la configuración de mail.',
            ]);
        }

        // Código nuevo, intentos nuevos (misma clave que VerificationCodeController@verify)
        RateLimiter::clear('reset-codigo:'.$user->id.'|'.$request->ip());

        return redirect('/verify-code')->with('status', $mensaje);
    }
}
