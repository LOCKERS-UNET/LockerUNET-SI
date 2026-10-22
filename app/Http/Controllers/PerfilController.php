<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class PerfilController extends Controller
{
    public function index()
    {
        return Inertia::render('User/EditProfile', ['carreras' => User::CARRERAS]);
    }

    public function store(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();

        // El correo y el carnet no se editan aquí: son la identidad institucional y el correo ya
        // fue verificado (antes se podía cambiar por el de otra persona sin código). Los cambia el Decanato.
        $request->validate([
            'name' => ['required', 'string', 'max:30'],
            'lastname' => ['required', 'string', 'max:30'],
            'career' => ['required', Rule::in(User::CARRERAS)],
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

        $user->name = trim($request->name);
        $user->lastname = trim($request->lastname);
        $user->career = $request->career;

        $user->save();

        return redirect()->back();
    }

    /**
     * POST /edit-profile/password
     * Cambiar la contraseña desde adentro (antes solo existía "olvidé mi contraseña")
     */
    public function password(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:8', 'confirmed', 'different:current_password'],
        ], [
            'current_password.current_password' => 'La contraseña actual no es correcta.',
            'password.different' => 'La nueva contraseña debe ser distinta de la actual.',
        ]);

        Auth::user()->update(['password' => $request->password]);

        return redirect()->back()->with('success', 'Contraseña actualizada.');
    }
}
