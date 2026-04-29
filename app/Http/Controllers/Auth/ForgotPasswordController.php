<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\ResetCodeMail;
use App\Models\PasswordResetCode;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;

class ForgotPasswordController extends Controller
{
    public function show()
    {
        return Inertia::render('Auth/ForgotPassword');
    }

    public function send(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email', 'exists:users,email'],
        ], [
            'email.required' => 'El correo es obligatorio.',
            'email.email'    => 'Ingresa un correo válido.',
            'email.exists'   => 'No encontramos una cuenta con ese correo.',
        ]);

        // Buscar el usuario por email
        $user = User::where('email', $request->email)->firstOrFail();

        // Eliminar códigos anteriores no usados del mismo usuario
        PasswordResetCode::where('user_id', $user->id)
            ->where('used', false)
            ->delete();

        // Generar código de 6 dígitos
        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // Guardar en BD con expiración de 15 minutos
        PasswordResetCode::create([
            'user_id'    => $user->id,      // 👈🏼 Usar user_id
            'token'      => $code,          // 👈🏼 Usar 'token' (nombre real de la columna)
            'expires_at' => now()->addMinutes(15),
            'used'       => false,
        ]);

        // Guardar el user_id en sesión (más seguro que email)
        session(['reset_user_id' => $user->id]);

        // Enviar el código por correo
        try {
            Mail::to($user->email)->send(new ResetCodeMail($code));
        } catch (\Exception $e) {
            // En desarrollo, mostrar el código en consola para pruebas
            if (app()->environment('local')) {
                logger()->error('Error al enviar correo: ' . $e->getMessage());
                logger()->info("Código de verificación para {$user->email}: $code");
            }
            
            return back()->withErrors([
                'email' => 'No se pudo enviar el correo. Revisa la configuración de mail.'
            ]);
        }

        return redirect('/verify-code')->with(
            'status',
            'Se ha enviado un código de verificación a tu correo.'
        );
    }
}