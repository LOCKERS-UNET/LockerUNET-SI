<?php

namespace App\Http\Controllers;

use App\Models\Multa;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BuscarUsuarioController extends Controller
{
    //
    //Aqui Buscamos al usuario
    public function index(Request $request){

        $search = trim($request->input('search'));

        $query = User::query();

        if($search){

            $users = $query->where('name','like',"%{$search}%")
                ->orWhere('lastname','like',"%{$search}%")
                ->get();

        }else{

            $users=[];

        }

        return Inertia::render('Admin/Usuarios',[

            'users'=> $users,
            'filters'=>$request->only(['search'])

        ]);

    }


    public function mostrar_usuario(User $user){

        $MiMulta = Multa::query()->where('user_id',$user->id)->first();

        return Inertia::render('Admin/VistaUsuario',[
            'user'=>$user,
            'multa'=>$MiMulta
        ]);


    }


    

}
