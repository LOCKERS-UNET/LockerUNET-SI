<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\EmailVerificationCodeMail;
use App\Models\EmailVerificationCode;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;

class RegisterController extends Controller
{
        public function index()
    {
        // Si ya está autenticado, redirigir al home
        if (Auth::check()) {
            return redirect('/');
        }
        
        return Inertia::render('Auth/Register');
    }
    
    public function create()
    {
        return Inertia::render('Auth/Register');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'lastname' => 'required|string|max:255',
            'email' => ['required','email','unique:users','ends_with:@unet.edu.ve'],
            'card_code' => ['required','unique:users','max:5'],
            'career' => 'required|string|max:255',
            'password' => 'required|string|min:6|confirmed',
        ]);

        // Crear usuario (email_verified_at será null por defecto)
        $user = User::create([
            'name' => $request->name,
            'lastname' => $request->lastname,
            'email' => $request->email,
            'card_code' => $request->card_code,
            'career' => $request->career,
            'password' => Hash::make($request->password),
        ]);

        // Generar y enviar código de verificación
        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        
        EmailVerificationCode::create([
            'user_id'    => $user->id,
            'token'      => $code,
            'expires_at' => now()->addMinutes(15),
            'used'       => false,
        ]);

        try {
            Mail::to($user->email)->send(new EmailVerificationCodeMail($code, $user->name));
        } catch (\Exception $e) {
            // Si el envío del correo falla, eliminar el usuario creado
            $user->delete();
            return back()->withErrors(['email' => 'No se pudo enviar el código de verificación. Por favor, intenta nuevamente.']);
        }

        // Autenticar usuario
        Auth::login($user);

        // Redirigir a página de verificación
        return redirect('/verify-email')->with('status', 'Se ha enviado un código de verificación a tu correo.');
    }
}