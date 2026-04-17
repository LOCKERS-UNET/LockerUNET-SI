<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class PerfilController extends Controller
{
    public function index()
    {
        return Inertia::render('User/EditProfile');
    }

    public function store(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();

        $request->validate([
            'name' => ['nullable', 'string', 'max:30'],
            'lastname' => ['nullable', 'string', 'max:30'],
            // Se ignora el ID del usuario actual para que el 'unique' no falle al no cambiar el correo
            'email' => ['nullable', 'email', 'unique:users,email,' . $user->id, 'ends_with:@unet.edu.ve'],
            'card_code' => ['nullable', 'string', 'max:5', 'unique:users,card_code,' . $user->id],
            'career' => ['nullable', 'string', 'max:50'],
            'profile_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'], // Máximo 2MB
        ]);

        // Procesar la imagen de perfil
        if ($request->hasFile('profile_photo')) {
            // Si el usuario ya tiene una foto, la eliminamos del disco
            if ($user->profile_photo) {
                Storage::disk('public')->delete($user->profile_photo);
            }

            // Guardamos la nueva foto en storage/app/public/profile_photos
            $path = $request->file('profile_photo')->store('profile_photos', 'public');
            $user->profile_photo = $path;
        }

        // Actualizar campos solo si se enviaron en el request
        $user->name = $request->name ?? $user->name;
        $user->lastname = $request->lastname ?? $user->lastname;
        $user->email = $request->email ?? $user->email;
        $user->card_code = $request->card_code ?? $user->card_code;
        $user->career = $request->career ?? $user->career;

        $user->save();

        return redirect()->back();
    }
}