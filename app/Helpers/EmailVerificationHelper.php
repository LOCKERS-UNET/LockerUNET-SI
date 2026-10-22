<?php

namespace App\Helpers;

use App\Mail\EmailVerificationCodeMail;
use App\Models\EmailVerificationCode;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

class EmailVerificationHelper
{
    /**
     * Genera un código de 6 dígitos (válido 15 minutos), invalida los anteriores
     * y lo envía al correo del usuario. Devuelve false si el correo no se pudo enviar.
     */
    public static function send(User $user): bool
    {
        EmailVerificationCode::where('user_id', $user->id)
            ->where('used', false)
            ->delete();

        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        EmailVerificationCode::create([
            'user_id' => $user->id,
            'token' => $code,
            'expires_at' => now()->addMinutes(15),
            'used' => false,
        ]);

        try {
            Mail::to($user->email)->send(new EmailVerificationCodeMail($code, $user->name));
        } catch (\Exception $e) {
            report($e);

            return false;
        }

        return true;
    }
}
