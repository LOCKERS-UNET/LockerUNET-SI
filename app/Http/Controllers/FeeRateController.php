<?php

namespace App\Http\Controllers;

use App\Models\FeeRate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

// Este controlador maneja los aranceles (tarifas) según el tipo de locker
class FeeRateController extends Controller
{
    // INDEX (Admin): Lista todas las tarifas
    // Ruta: GET /admin/aranceles
    public function index()
    {
        // Traemos las tarifas ordenadas por fecha (más reciente primero)
        $tarifas = FeeRate::with('admin')->orderBy('effective_from', 'desc')->get();

        return Inertia::render('Admin/Aranceles', [
            'tarifas' => $tarifas,
        ]);
    }

    // STORE (Admin): El admin crea una nueva tarifa
    // Ruta: POST /admin/aranceles
    public function store(Request $request)
    {
        $request->validate([
            'locker_type'    => 'required|in:small,mid,large',
            'monthly_amount' => 'required|numeric|min:0',
            'effective_from' => 'required|date',
            'reason'         => 'nullable|string',
        ]);

        FeeRate::create([
            'locker_type'    => $request->locker_type,
            'monthly_amount' => $request->monthly_amount,
            'effective_from' => $request->effective_from,
            'reason'         => $request->reason,
            'created_by'     => Auth::id(), // el admin logueado
        ]);

        return redirect()->back()->with('success', 'Tarifa registrada correctamente.');
    }

    // DESTROY (Admin): El admin elimina una tarifa
    // Ruta: DELETE /admin/aranceles/{id}
    public function destroy($id)
    {
        $tarifa = FeeRate::findOrFail($id);
        $tarifa->delete();

        return redirect()->back()->with('success', 'Tarifa eliminada correctamente.');
    }
}
