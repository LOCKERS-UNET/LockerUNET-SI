<?php

namespace App\Http\Controllers;

use App\Models\FeeRate;
use App\Models\Semester;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Illuminate\Support\Facades\Artisan;

class FeeRateController extends Controller
{
    /**
     * GET /fee-rates
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

        // Obtener semestres activos
        $semesters = Semester::where('is_active', true)
            ->orderBy('start_year', 'desc')
            ->orderBy('start_month', 'desc')
            ->get();

        return Inertia::render('Admin/Aranceles', [
            'rates' => $rates,
            'semesters' => $semesters,
        ]);
    }

    /**
     * POST /admin/fee-rates
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

        return redirect()->back()->with('success', 'Nuevo arancel registrado correctamente.');
    }

    /**
     * 👇 NUEVO: Generar pagos del semestre
     */
    public function generatePayments(Request $request)
    {
        $request->validate([
            'semester_name' => 'required|string|exists:semesters,name',
        ]);

        $semesterName = $request->semester_name;

        try {
            // Ejecutar el command
            Artisan::call('payments:generate-semester', [
                'semester' => $semesterName
            ]);

            $output = Artisan::output();

            return redirect()->back()->with('success', "Pagos del semestre {$semesterName} generados exitosamente.");
            
        } catch (\Exception $e) {
            return redirect()->back()->withErrors([
                'error' => 'Error al generar pagos: ' . $e->getMessage()
            ]);
        }
    }
}