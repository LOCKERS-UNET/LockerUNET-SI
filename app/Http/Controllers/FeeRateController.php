<?php

namespace App\Http\Controllers;

use App\Models\FeeRate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class FeeRateController extends Controller
{
    /**
     * GET /fee-rates
     * Obtiene los aranceles más recientes para cada tamaño
     */
    public function index()
    {
        $types = ['small', 'mid', 'large'];
        $rates = [];

        foreach ($types as $type) {
            $rates[$type] = FeeRate::where('locker_type', $type)
                ->where('effective_from', '<=', today())
                ->orderBy('effective_from', 'desc')
                ->orderBy('rate_id', 'desc')
                ->first();
        }

        return Inertia::render('Admin/Aranceles', [
            'rates' => $rates
        ]);
    }

    /**
     * POST /admin/fee-rates
     * Registra un nuevo aumento de tarifa en el tiempo (histórico)
     */
    public function store(Request $request)
    {
        $request->validate([
            'locker_type'    => 'required|in:small,mid,large',
            'monthly_amount' => 'required|numeric|gt:0',
            'reason'         => 'required|string|max:500',
        ]);

        FeeRate::create([
            'locker_type'    => $request->locker_type,
            'monthly_amount' => $request->monthly_amount,
            'effective_from' => today(),
            'reason'         => $request->reason,
            'created_by'     => Auth::id(),
        ]);

        return redirect()->back()->with('success', 'Nuevo arancel estipulado correctamente. Empezará a tomar rigor a partir de hoy.');
    }
}
