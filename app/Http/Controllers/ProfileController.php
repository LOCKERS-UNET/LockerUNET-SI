<?php

namespace App\Http\Controllers;

use App\Models\LockerAssignment;
use Illuminate\Support\Facades\Auth;
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
            ->whereIn('assignment_status', LockerAssignment::VIGENTES)
            ->first();

        // En caso de que se necesiten ambos
        return Inertia::render('User/Profile', [
            'user' => $user,
            'asignacion' => $assignment,
        ]);
    }
}
