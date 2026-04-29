<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\EmailVerificationCodeMail;
use App\Models\EmailVerificationCode;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
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

        return Inertia::render('Auth/VerifyEmail');
    }

    public function send(Request $request)
    {
        $user = Auth::user();

        if (!$user || $user->email_verified_at !== null) {
            return redirect('/');
        }

        // Eliminar códigos anteriores no usados
        EmailVerificationCode::where('user_id', $user->id)
            ->where('used', false)
            ->delete();

        // Generar código de 6 dígitos
        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // Guardar en BD con expiración de 15 minutos
        EmailVerificationCode::create([
            'user_id'    => $user->id,
            'token'      => $code,
            'expires_at' => now()->addMinutes(15),
            'used'       => false,
        ]);

        // Enviar el código por correo
        try {
            Mail::to($user->email)->send(new EmailVerificationCodeMail($code, $user->name));
        } catch (\Exception $e) {
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