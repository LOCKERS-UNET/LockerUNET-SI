<?php

namespace App\Http\Controllers;

use App\Models\Locker;
use App\Models\Building;
use App\Models\Sector;
use Illuminate\Http\Request;
use Inertia\Inertia;

// Este controlador maneja todo lo relacionado con los Lockers
class LockerController extends Controller
{
    // INDEX: Devuelve todos los lockers con su sector y edificio
    // Ruta: GET /admin/lockers
    public function index()
    {
        // Traemos todos los lockers, incluyendo su sector y el edificio del sector
        $lockers = Locker::with(['sector.building'])->get();

        // Le pasamos los datos a la vista Vue
        return Inertia::render('Admin/GestionLockers', [
            'lockers' => $lockers,
        ]);
    }

    // STORE: Crea un nuevo locker
    // Ruta: POST /admin/lockers
    public function store(Request $request)
    {
        // Validamos que los datos enviados sean correctos
        $request->validate([
            'locker_code' => 'required|string|unique:lockers,locker_code',
            'sector_id'   => 'required|exists:sectors,sector_id',
            'locker_type' => 'required|in:small,mid,large',
            'plate_number'=> 'nullable|string',
            'status'      => 'nullable|integer|in:0,1,2',
        ]);

        // Creamos el locker con los datos del formulario
        $locker = Locker::create($request->all());

        // Redirigimos con un mensaje de éxito
        return redirect()->back()->with('success', 'Locker creado correctamente.');
    }

    // UPDATE: Actualiza un locker existente
    // Ruta: PUT /admin/lockers/{id}
    public function update(Request $request, $id)
    {
        // Buscamos el locker por su ID, si no existe lanza error 404
        $locker = Locker::findOrFail($id);

        // Validamos los datos (el código debe ser único pero ignoramos el locker actual)
        $request->validate([
            'locker_code' => 'required|string|unique:lockers,locker_code,' . $id . ',locker_id',
            'sector_id'   => 'required|exists:sectors,sector_id',
            'locker_type' => 'required|in:small,mid,large',
            'plate_number'=> 'nullable|string',
            'status'      => 'nullable|integer|in:0,1,2',
        ]);

        // Actualizamos el locker
        $locker->update($request->all());

        return redirect()->back()->with('success', 'Locker actualizado correctamente.');
    }

    // DESTROY: Elimina un locker
    // Ruta: DELETE /admin/lockers/{id}
    public function destroy($id)
    {
        $locker = Locker::findOrFail($id);
        $locker->delete();

        return redirect()->back()->with('success', 'Locker eliminado correctamente.');
    }

    // SHOW: Devuelve los lockers disponibles para que el estudiante pueda buscar
    // Ruta: GET /buscar-locker
    public function disponibles()
    {
        // Solo traemos los lockers con status 0 (disponible)
        $lockers = Locker::with(['sector.building'])
            ->where('status', 0)
            ->get();

        return Inertia::render('User/BuscarLocker', [
            'lockers' => $lockers,
        ]);
    }
}
