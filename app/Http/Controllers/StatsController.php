<?php

namespace App\Http\Controllers;

use App\Models\Locker;
use App\Models\LockerAssignment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Carbon\Carbon;

class StatsController extends Controller
{
public function index(Request $request)
    {
        // 1. Obtener filtros
        $carrera = $request->input('carrera');
        $mesFiltro = $request->input('mes', 'Abril 2026'); // Valor por defecto

        // 2. Extraer Mes y Año manualmente para evitar errores de Carbon
        $mesesMap = [
            'Enero' => 1, 'Febrero' => 2, 'Marzo' => 3, 'Abril' => 4, 'Mayo' => 5, 'Junio' => 6,
            'Julio' => 7, 'Agosto' => 8, 'Septiembre' => 9, 'Octubre' => 10, 'Noviembre' => 11, 'Diciembre' => 12
        ];
        
        $partes = explode(' ', $mesFiltro);
        $nombreMes = $partes[0] ?? 'Abril';
        $mesNum = $mesesMap[$nombreMes] ?? 4;
        $anioNum = $partes[1] ?? 2026;

        // 3. Consultas a la base de datos
        // Importante: No filtramos por status 'active' para que la gráfica muestre el histórico de creación
        $queryBase = LockerAssignment::whereMonth('created_at', $mesNum)
                                    ->whereYear('created_at', $anioNum);

        if (!empty($carrera)) {
            $queryBase->whereHas('user', fn($q) => $q->where('career', $carrera));
        }

        // KPIs
        $totalCapacity = Locker::count();
        $usoEnMes = $queryBase->count();
        
        // Gráfica: Conteo por día
        $dailyData = (clone $queryBase)
            ->select(DB::raw('DAY(created_at) as day'), DB::raw('COUNT(*) as count'))
            ->groupBy('day')
            ->get()
            ->pluck('count', 'day');

        $chartPoints = [];
        $acumulado = 0;
$diasEnMes = Carbon::create($anioNum, $mesNum)->daysInMonth;

// El ciclo for se mantiene igual:
for ($i = 1; $i <= $diasEnMes; $i++) {
    $acumulado += ($dailyData[$i] ?? 0);
    $chartPoints[] = $acumulado;
}

        return Inertia::render('Admin/EstadisticasLockers', [
            'stats' => [
                'uso_proyectado'  => $usoEnMes,
                'capacidad_total' => $totalCapacity,
                'crecimiento'     => "0.0", // Puedes implementar lógica comparativa después
                'tasa_ocupacion'  => $totalCapacity > 0 ? number_format(($usoEnMes / $totalCapacity) * 100, 1) : "0.0",
                'chart_data'      => $chartPoints,
            ],
            'filters' => [
                'carrera' => $carrera,
                'mes'     => $mesFiltro
            ]
        ]);
    }
public function summary()
{
    return response()->json([
        'total'       => (int) Locker::count(),
        'occupied'    => (int) Locker::where('status', 1)->count(),
        'available'   => (int) Locker::where('status', 0)->count(),
        'maintenance' => (int) Locker::where('status', 2)->count(),
    ], 200, [], JSON_UNESCAPED_UNICODE); // 👈🏼 Asegura encoding correcto
}
}