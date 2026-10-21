<?php

namespace App\Http\Controllers\Auth;

use App\Helpers\EmailVerificationHelper;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

        if(!Auth::attempt($credenciales)){
           return back()->withErrors([
                'auth' => 'Credenciales Incorrectas.',
            ]);
        }


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

        return to_route('login');

    }

}
