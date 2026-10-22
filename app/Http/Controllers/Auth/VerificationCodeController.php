<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\PasswordResetCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Inertia\Inertia;

class VerificationCodeController extends Controller
{
    public function show()
    {
        // Si no se pidió un código antes, redirigir al inicio del flujo
        if (!session('reset_iniciado')) {
            return redirect('/forgot-password');
        }

        return Inertia::render('Auth/VerificationCode', [
            'status' => session('status'),
        ]);
    }

    public function verify(Request $request)
    {
        // Al pegar el código suelen venir espacios: se dejan solo los dígitos
        $request->merge(['code' => preg_replace('/\D/', '', (string) $request->code)]);

        $request->validate([
            'code' => ['required', 'digits:6'],
        ], [
            'code.required' => 'Ingresa el código de verificación.',
            'code.digits'   => 'El código debe tener 6 dígitos.',
        ]);

        if (!session('reset_iniciado')) {
            return redirect('/forgot-password');
        }

        $userId = session('reset_user_id');

        // 5 códigos incorrectos bloquean 5 minutos (antes no había límite de intentos)
        $clave = 'reset-codigo:'.$userId.'|'.$request->ip();
        if (RateLimiter::tooManyAttempts($clave, 5)) {
            return back()->withErrors([
                'code' => 'Demasiados intentos. Espera '.ceil(RateLimiter::availableIn($clave) / 60).' minuto(s) y vuelve a intentarlo.',
            ]);
        }

        // Buscar el código por user_id y token (nombre correcto de columna)
        $record = PasswordResetCode::where('user_id', $userId)
            ->where('token', $request->code)  // 👈🏼 'token' es el nombre real
            ->where('used', false)            // 👈🏼 Solo códigos no usados
            ->first();

        if (!$record) {
            RateLimiter::hit($clave, 300);

            return back()->withErrors(['code' => 'El código es incorrecto.']);
        }

        RateLimiter::clear($clave);

        if ($record->isExpired()) {
            $record->delete();
            return back()->withErrors(['code' => 'El código ha expirado. Solicita uno nuevo.']);
        }

        // Marcar el código como usado para que no se pueda reutilizar
        $record->update(['used' => true]);

        // Marcar en sesión que el código fue verificado
        session(['reset_code_verified' => true]);

        return redirect('/new-password');
    }
}