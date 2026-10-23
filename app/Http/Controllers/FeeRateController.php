<?php

namespace App\Http\Controllers;

use App\Models\FeeRate;
use App\Models\Payment;
use App\Models\Semester;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

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
            $rates[$type] = FeeRate::vigente($type);
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
        $request->merge(['monthly_amount' => self::montoConPunto($request->monthly_amount)]);

        $request->validate([
            'locker_type' => 'required|in:small,mid,large',
            'monthly_amount' => 'required|numeric|gt:0|max:99999999.99',
            'reason' => 'required|string|max:500',
        ]);

        FeeRate::create([
            'locker_type' => $request->locker_type,
            'monthly_amount' => $request->monthly_amount,
            'effective_from' => today(),
            'reason' => $request->reason,
            'created_by' => Auth::id(),
        ]);

        return redirect()->back()->with('success', 'Nuevo arancel registrado correctamente.');
    }

    /**
     * "12,50" → "12.50". En Venezuela se escribe la coma decimal; el campo era type="number" y el
     * navegador descartaba la coma, así que se guardaba 1250. Lo usa también FineController.
     */
    public static function montoConPunto(mixed $monto): mixed
    {
        return is_string($monto) ? str_replace(',', '.', trim($monto)) : $monto;
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
            $antes = Payment::where('semester', $semesterName)->count();

            $codigo = Artisan::call('payments:generate-semester', [
                'semester' => $semesterName,
            ]);

            $creados = Payment::where('semester', $semesterName)->count() - $antes;

            // Antes decía "generados exitosamente" aunque no se hubiera creado ningún pago
            if ($codigo !== 0) {
                return redirect()->back()->withErrors([
                    'error' => "Faltan aranceles para algunos tipos de locker. Se generaron {$creados} pagos: crea los aranceles que faltan y vuelve a generar.",
                ]);
            }

            return redirect()->back()->with('success', $creados > 0
                ? "Se generaron {$creados} pagos del semestre {$semesterName}."
                : "No había pagos nuevos que generar para el semestre {$semesterName}.");
        } catch (\Exception $e) {
            report($e);

            return redirect()->back()->withErrors([
                'error' => 'No se pudieron generar los pagos del semestre. Intenta de nuevo.',
            ]);
        }
    }
}
