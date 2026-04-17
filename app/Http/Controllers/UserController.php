<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\LockerAssignment;
use App\Models\Fine;
use App\Models\Payment;
use Illuminate\Http\Request;
use Inertia\Inertia;

class UserController extends Controller
{
    /**
     * GET /admin/users
     * Listado principal de usuarios con su última asignación activa
     */
    public function index(Request $request)
    {
        $query = User::query();

        // Filtro de búsqueda textual
        if ($request->has('search') && $request->search) {
            $searchTerm = '%' . $request->search . '%';
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'LIKE', $searchTerm)
                  ->orWhere('lastname', 'LIKE', $searchTerm)
                  ->orWhere('card_code', 'LIKE', $searchTerm);
            });
        }

        // Recuperamos los usuarios junto con la asignación activa (si tienen)
        $users = $query->with(['assignments' => function ($q) {
            $q->where('assignment_status', 'active')
              ->with(['locker.sector.building']);
        }])->get()->map(function ($user) {
            $activeAssignment = $user->assignments->first();
            $locker = $activeAssignment ? $activeAssignment->locker : null;

            return [
                'id'            => $user->id,
                'name'          => $user->name,
                'lastname'      => $user->lastname,
                'card_code'     => $user->card_code,
                'career'        => $user->career,
                'is_admin'      => $user->is_admin,
                // Flatten locker data if present
                'locker_code'   => $locker ? $locker->locker_code : null,
                'building_name' => ($locker && $locker->sector && $locker->sector->building) ? $locker->sector->building->building_name : null,
                'sector_name'   => ($locker && $locker->sector) ? $locker->sector->sector_name : null,
            ];
        });

        return Inertia::render('Admin/Usuarios', [
            'users' => $users
        ]);
    }

    /**
     * GET /admin/users/search
     * Busca usuarios y devuelve JSON para el panel de asignaciones.
     */
    public function searchJson(Request $request)
    {
        $searchTerm = trim($request->input('search', ''));

        $users = User::when($searchTerm !== '', function ($query) use ($searchTerm) {
                $query->where('name', 'LIKE', '%' . $searchTerm . '%')
                    ->orWhere('lastname', 'LIKE', '%' . $searchTerm . '%')
                    ->orWhere('card_code', 'LIKE', '%' . $searchTerm . '%');
            })
            ->with(['assignments' => function ($q) {
                $q->where('assignment_status', 'active')
                  ->with(['locker.sector.building']);
            }])
            ->get()
            ->map(function ($user) {
                $activeAssignment = $user->assignments->first();

                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'lastname' => $user->lastname,
                    'email' => $user->email,
                    'career' => $user->career,
                    'card_code' => $user->card_code,
                    'locker_assignment' => $activeAssignment ? [
                        'assignment_id' => $activeAssignment->assignment_id,
                        'start_date' => $activeAssignment->start_date,
                        'locker' => $activeAssignment->locker ? [
                            'locker_code' => $activeAssignment->locker->locker_code,
                            'sector' => $activeAssignment->locker->sector ? [
                                'sector_name' => $activeAssignment->locker->sector->sector_name,
                                'building' => [
                                    'building_code' => $activeAssignment->locker->sector->building->building_code ?? null,
                                ],
                            ] : null,
                        ] : null,
                    ] : null,
                ];
            });

        return response()->json($users);
    }

    /**
     * GET /admin/users/{id}
     * Muestra todo el perfil, historial y deudas de un usuario en concreto
     */
    public function show($id)
    {
        $user = User::findOrFail($id);

        $assignment = LockerAssignment::with(['locker.sector.building'])
            ->where('user_id', $id)
            ->where('assignment_status', 'active')
            ->first();

        $fines = Fine::with(['assignment.locker'])
            ->where('user_id', $id)
            ->orderBy('created_at', 'desc')
            ->get();

        $pendingPayments = Payment::where('user_id', $id)
            ->whereIn('payment_status', ['pending', 'overdue'])
            ->orderBy('due_date', 'asc')
            ->get();

        return Inertia::render('Admin/VerUsuario', [
            'user'            => $user,
            'assignment'      => $assignment,
            'fines'           => $fines,
            'pendingPayments' => $pendingPayments,
        ]);
    }
}
