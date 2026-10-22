<?php

namespace App\Http\Controllers\Auth;

use App\Helpers\EmailVerificationHelper;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
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
            'card_code' => ['required', 'regex:/^\d{1,5}$/', 'unique:users'],
            'career' => 'required|string|max:255',
            'password' => 'required|string|min:8|confirmed',
        ], [
            'card_code.regex' => 'El carnet debe tener solo números (hasta 5 dígitos).',
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