<?php

namespace App\Http\Controllers\Auth;

use App\Helpers\EmailVerificationHelper;
use App\Http\Controllers\Controller;
use App\Models\EmailVerificationCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class EmailVerificationController extends Controller
{
    public function show()
    {
        $user = Auth::user();

        // Si no hay usuario o ya verificó su email
        if (!$user || $user->email_verified_at !== null) {
            return redirect('/');
        }

        return Inertia::render('Auth/VerifyEmail', [
            'status' => session('status'),
        ]);
    }

    public function send(Request $request)
    {
        $user = Auth::user();

        if (!$user || $user->email_verified_at !== null) {
            return redirect('/');
        }

        // Genera un código nuevo (invalida los anteriores) y lo envía por correo
        if (! EmailVerificationHelper::send($user)) {
            return back()->withErrors([
                'email' => 'No se pudo enviar el correo de verificación.'
            ]);
        }

        return back()->with('status', 'Se ha enviado un nuevo código de verificación a tu correo.');
    }

    public function verify(Request $request)
    {
        $request->validate([
            'code' => ['required', 'digits:6'],
        ], [
            'code.required' => 'Ingresa el código de verificación.',
            'code.digits'   => 'El código debe tener 6 dígitos.',
        ]);

        $user = Auth::user();

        if (!$user || $user->email_verified_at !== null) {
            return redirect('/');
        }

        // Buscar el código
        $record = EmailVerificationCode::where('user_id', $user->id)
            ->where('token', $request->code)
            ->where('used', false)
            ->first();

        if (!$record) {
            return back()->withErrors(['code' => 'El código es incorrecto.']);
        }

        if ($record->isExpired()) {
            $record->delete();
            return back()->withErrors(['code' => 'El código ha expirado. Solicita uno nuevo.']);
        }

        // Marcar como usado
        $record->update(['used' => true]);

        // Marcar email como verificado
        $user->update(['email_verified_at' => now()]);

        // Limpiar códigos viejos
        EmailVerificationCode::where('user_id', $user->id)->delete();

        return redirect('/')->with('status', '¡Correo verificado exitosamente! Ya puedes usar todas las funciones.');
    }

    public function resend(Request $request)
    {
        return $this->send($request);
    }
}