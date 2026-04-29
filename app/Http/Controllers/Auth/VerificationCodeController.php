<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\PasswordResetCode;
use Illuminate\Http\Request;
use Inertia\Inertia;

class VerificationCodeController extends Controller
{
    public function show()
    {
        // Si no hay user_id en sesión, redirigir al inicio del flujo
        if (!session('reset_user_id')) {
            return redirect('/forgot-password');
        }

        return Inertia::render('Auth/VerificationCode');
    }

    public function verify(Request $request)
    {
        $request->validate([
            'code' => ['required', 'digits:6'],
        ], [
            'code.required' => 'Ingresa el código de verificación.',
            'code.digits'   => 'El código debe tener 6 dígitos.',
        ]);

        $userId = session('reset_user_id');

        if (!$userId) {
            return redirect('/forgot-password');
        }

        // Buscar el código por user_id y token (nombre correcto de columna)
        $record = PasswordResetCode::where('user_id', $userId)
            ->where('token', $request->code)  // 👈🏼 'token' es el nombre real
            ->where('used', false)            // 👈🏼 Solo códigos no usados
            ->first();

        if (!$record) {
            return back()->withErrors(['code' => 'El código es incorrecto.']);
        }

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