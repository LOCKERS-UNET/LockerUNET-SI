<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class PerfilController extends Controller
{
    /**
     * GET /edit-profile
     * Solo cambiar la contraseña: los datos del perfil no se editan desde la app
     */
    public function index()
    {
        return Inertia::render('User/EditProfile');
    }

    /**
     * POST /edit-profile/password
     * Cambiar la contraseña desde adentro (antes solo existía "olvidé mi contraseña")
     */
    public function password(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:8', 'max:72', 'confirmed', 'different:current_password'], // bcrypt ignora lo que pase de 72
        ], [
            'current_password.current_password' => 'La contraseña actual no es correcta.',
            'password.different' => 'La nueva contraseña debe ser distinta de la actual.',
            'password.max' => 'La contraseña no puede tener más de 72 caracteres.',
        ]);

        Auth::user()->update(['password' => $request->password]);

        return redirect()->back()->with('success', 'Contraseña actualizada.');
    }
}
