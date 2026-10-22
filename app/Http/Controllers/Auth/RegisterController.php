<?php

namespace App\Http\Controllers\Auth;

use App\Helpers\EmailVerificationHelper;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class RegisterController extends Controller
{
        public function index()
    {
        // Si ya está autenticado, redirigir al home
        if (Auth::check()) {
            return redirect('/');
        }
        
        return Inertia::render('Auth/Register', ['carreras' => User::CARRERAS]);
    }
    
    public function create()
    {
        return Inertia::render('Auth/Register');
    }

    public function store(Request $request)
    {
        // El "ya está registrado" permite averiguar qué correos/carnets tienen cuenta. Solo se cuentan
        // esos choques (no todos los registros: en la universidad muchos comparten la misma IP).
        // El límite se revisa solo en los intentos duplicados: un registro con datos nuevos siempre pasa
        // (antes, tras 20 choques desde una IP, nadie de esa red podía registrarse).
        $clave = 'registro-duplicado:'.$request->ip();
        $correo = is_string($request->email) ? $request->email : '';
        $carnet = is_string($request->card_code) || is_int($request->card_code) ? (string) $request->card_code : '';
        if (User::where('email', $correo)->orWhere('card_code', $carnet)->exists()) {
            if (RateLimiter::tooManyAttempts($clave, 20)) {
                return back()->withErrors(['email' => 'Demasiados intentos con correos o carnets ya registrados. Intenta de nuevo en una hora.']);
            }
            RateLimiter::hit($clave, 3600);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'lastname' => 'required|string|max:255',
            'email' => ['required','email','unique:users','ends_with:@unet.edu.ve'],
            'card_code' => ['required', 'regex:/^\d{1,5}$/', 'unique:users'],
            'career' => ['required', Rule::in(User::CARRERAS)],
            // max:72: bcrypt ignora lo que pase de 72 caracteres
            'password' => 'required|string|min:8|max:72|confirmed',
        ], [
            'card_code.regex' => 'El carnet debe tener solo números (hasta 5 dígitos).',
            'password.max' => 'La contraseña no puede tener más de 72 caracteres.',
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
        if (! EmailVerificationHelper::send($user)) {
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