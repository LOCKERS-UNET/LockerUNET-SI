<?php

namespace App\Http\Controllers\Auth;

use App\Helpers\EmailVerificationHelper;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Inertia\Inertia;

class LoginController extends Controller
{
    //

    public function index(){

        return Inertia::render('Auth/Login');

    }


    public function store(Request $request){


        $credenciales = $request->validate([
            'email' => ['required','email','ends_with:@unet.edu.ve'],
            'password'=>['required']

        ]);

        // 5 intentos fallidos por correo e IP bloquean 1 minuto (antes no había límite: se podían probar contraseñas sin fin)
        $clave = 'login:'.strtolower($credenciales['email']).'|'.$request->ip();
        if (RateLimiter::tooManyAttempts($clave, 5)) {
            return back()->withErrors([
                'auth' => 'Demasiados intentos fallidos. Espera '.RateLimiter::availableIn($clave).' segundos y vuelve a intentarlo.',
            ]);
        }

        if(!Auth::attempt($credenciales)){
            RateLimiter::hit($clave, 60);

            return back()->withErrors([
                'auth' => 'Credenciales Incorrectas.',
            ]);
        }

        RateLimiter::clear($clave);
        $request->session()->regenerate();


        // Sin correo verificado no se entra: se envía un código nuevo y se pide verificar
        if(! Auth::user()->hasVerifiedEmail()){
            if(! EmailVerificationHelper::send(Auth::user())){
                return redirect('/verify-email')->withErrors([
                    'email' => 'Tu correo no está verificado y no pudimos enviarte el código. Usa "Reenviar código".',
                ]);
            }

            return redirect('/verify-email')->with('status', 'Tu correo no está verificado. Te enviamos un código nuevo.');
        }

        if(Auth::user()->is_admin){
            return to_route('home-admin');    
        }
        return to_route('home');

    }

    public function logout(Request $request){

        Auth::logout();

        // Antes solo se hacía logout: la sesión y el token CSRF seguían siendo los mismos
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        Inertia::clearHistory();

        return to_route('login');

    }

}
