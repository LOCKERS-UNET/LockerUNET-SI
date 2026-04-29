<?php

namespace App\Http\Controllers;

use App\Models\LockerAssignment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class ProfileController extends Controller
{
    /**
     * GET /profile
     * Carga de datos para mi perfil
     */
    public function show()
    {
        $user = Auth::user();
        
        $assignment = LockerAssignment::with(['locker.sector.building'])
            ->where('user_id', $user->id)
            ->where('assignment_status', 'active')
            ->first();

        // En caso de que se necesiten ambos
        return Inertia::render('User/Profile', [
            'user' => $user,
            'asignacion' => $assignment
        ]);
    }

    /**
     * PUT /profile
     * Actualiza solo los campos permitidos del estudiante
     */
    public function update(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'name'      => 'required|string|max:255',
            'lastname'  => 'required|string|max:255',
            'email'     => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users')->ignore($user->id),
                // Custom rule
                function ($attribute, $value, $fail) {
                    if (!str_ends_with(strtolower($value), '@unet.edu.ve')) {
                        $fail('El correo debe pertenecer al dominio institucional @unet.edu.ve');
                    }
                },
            ],
            'card_code' => [
                'required',
                'regex:/^\d{5}$/',
                Rule::unique('users')->ignore($user->id),
            ],
            'career'    => 'nullable|string|max:255',
        ], [
            'card_code.regex' => 'El código de carnet debe tener exactamente 5 dígitos numéricos.',
        ]);

        $user->update([
            'name'      => $request->name,
            'lastname'  => $request->lastname,
            'email'     => $request->email,
            'card_code' => $request->card_code,
            'career'    => $request->career,
        ]);

        return redirect()->back()->with('success', 'Perfil actualizado exitosamente.');
    }
}
