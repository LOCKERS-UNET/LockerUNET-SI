<?php

namespace App\Http\Controllers;

use App\Models\Locker;
use App\Models\User;
use App\Models\LockerAssignment;
use Illuminate\Http\Request;
use App\Models\Semester; 
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Carbon\Carbon;

class StatsController extends Controller
{

// 👇🏼 NUEVA FUNCIÓN AUXILIAR: Generar rango de meses
    private function generarRangoMeses($mesesAtras = 6, $mesesAdelante = 3)
    {
        $mesesEspañol = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril', 5 => 'Mayo', 6 => 'Junio',
            7 => 'Julio', 8 => 'Agosto', 9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
        ];
        
        $rango = [];
        $fecha = Carbon::now()->subMonths($mesesAtras);
        $fin = Carbon::now()->addMonths($mesesAdelante);
        
        while ($fecha->lte($fin)) {
            $nombreMes = $mesesEspañol[$fecha->month];
            $rango[] = "{$nombreMes} {$fecha->year}";
            $fecha->addMonth();
        }
        
        return $rango;
    }

    public function getSemesters()
    {
        $semesters = Semester::where('is_active', true)
            ->orderBy('start_year', 'desc')
            ->orderBy('start_month', 'desc')
            ->get();
        
        return response()->json($semesters);
    }

    // 👇🏼 NUEVO: Crear nuevo semestre
    public function storeSemester(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:semesters,name',
            'start_month' => 'required|integer|between:1,12',
            'start_year' => 'required|integer|min:2020',
            'end_month' => 'required|integer|between:1,12',
            'end_year' => 'required|integer|min:2020',
        ]);

        // Validar que end_date sea mayor que start_date
        $start = Carbon::create($request->start_year, $request->start_month, 1);
        $end = Carbon::create($request->end_year, $request->end_month, 1);
        
        if ($end < $start) {
            return redirect()->back()->withErrors([
                'end_month' => 'La fecha de fin debe ser posterior a la fecha de inicio.'
            ]);
        }

        Semester::create([
            'name' => $request->name,
            'start_month' => $request->start_month,
            'start_year' => $request->start_year,
            'end_month' => $request->end_month,
            'end_year' => $request->end_year,
            'is_active' => true,
        ]);

        return redirect()->back()->with('success', 'Semestre creado exitosamente.');
    }

    public function index(Request $request)
    {
        // 1. Obtener filtros
        $carrera = $request->input('carrera');
        $mesFiltro = $request->input('mes', '');
        $semestreFiltro = $request->input('semestre', '');

        // 2. Variables para fecha
        $mesesMap = [
            'Enero' => 1, 'Febrero' => 2, 'Marzo' => 3, 'Abril' => 4, 'Mayo' => 5, 'Junio' => 6,
            'Julio' => 7, 'Agosto' => 8, 'Septiembre' => 9, 'Octubre' => 10, 'Noviembre' => 11, 'Diciembre' => 12
        ];
        
        $mesNum = Carbon::now()->month;
        $anioNum = Carbon::now()->year;

        if (!empty($mesFiltro)) {
            $partes = explode(' ', $mesFiltro);
            $nombreMes = $partes[0] ?? Carbon::now()->format('F');
            $mesNum = $mesesMap[$nombreMes] ?? Carbon::now()->month;
            $anioNum = $partes[1] ?? Carbon::now()->year;
        }

        // 3. Query base (solo activos)
        $queryBase = LockerAssignment::where('assignment_status', 'active');

        // 👇🏼 LÓGICA DE FILTRADO: Mes PRIORIDAD sobre Semestre
        if (!empty($mesFiltro)) {
            // Filtrar por mes específico
            $queryBase->whereMonth('created_at', $mesNum)
                      ->whereYear('created_at', $anioNum);
        } elseif (!empty($semestreFiltro)) {
            // Filtrar por rango de semestre
            $semestre = Semester::where('name', $semestreFiltro)->first();
            if ($semestre) {
                $queryBase->whereBetween('created_at', [
                    Carbon::create($semestre->start_year, $semestre->start_month, 1),
                    Carbon::create($semestre->end_year, $semestre->end_month, 1)->endOfMonth()
                ]);
            }
        } else {
            // Sin filtros → mes actual por defecto
            $queryBase->whereMonth('created_at', $mesNum)
                      ->whereYear('created_at', $anioNum);
        }

        // Filtro por carrera
        if (!empty($carrera)) {
            $queryBase->whereHas('user', fn($q) => $q->where('career', $carrera));
        }

        // KPIs
        $totalCapacity = Locker::count();
        $usoEnMes = $queryBase->count();
        
        // Gráfica
        $dailyData = (clone $queryBase)
            ->select(DB::raw('DAY(created_at) as day'), DB::raw('COUNT(*) as count'))
            ->groupBy('day')
            ->get()
            ->pluck('count', 'day');

        $chartPoints = [];
        $acumulado = 0;
        $diasEnMes = !empty($mesFiltro) 
            ? Carbon::create($anioNum, $mesNum)->daysInMonth 
            : 30; // Default para semestres

        for ($i = 1; $i <= $diasEnMes; $i++) {
            $acumulado += ($dailyData[$i] ?? 0);
            $chartPoints[] = $acumulado;
        }

        // Datos para selects
        $carrerasDisponibles = User::whereNotNull('career')
            ->where('career', '!=', '')
            ->distinct()
            ->orderBy('career', 'asc')
            ->pluck('career');
        
        $mesesDisponibles = $this->generarRangoMeses(6, 3);
        
        // 👇🏼 NUEVO: Obtener semestres disponibles
        $semestresDisponibles = Semester::where('is_active', true)
            ->orderBy('start_year', 'desc')
            ->orderBy('start_month', 'desc')
            ->pluck('name');

        return Inertia::render('Admin/EstadisticasLockers', [
            'stats' => [
                'uso_proyectado'  => $usoEnMes,
                'capacidad_total' => $totalCapacity,
                'crecimiento'     => "0.0",
                'tasa_ocupacion'  => $totalCapacity > 0 ? number_format(($usoEnMes / $totalCapacity) * 100, 1) : "0.0",
                'chart_data'      => $chartPoints,
            ],
            'filters' => [
                'carrera' => $carrera,
                'mes'     => $mesFiltro,
                'semestre'=> $semestreFiltro,
            ],
            'carrerasDisponibles' => $carrerasDisponibles,
            'mesesDisponibles'    => $mesesDisponibles,
            'semestresDisponibles'=> $semestresDisponibles, // 👈🏼 Pasar a la vista
        ]);
    }

    public function summary()
    {
        return response()->json([
            'total'       => (int) Locker::count(),
            'occupied'    => (int) Locker::where('status', 1)->count(),
            'available'   => (int) Locker::where('status', 0)->count(),
            'maintenance' => (int) Locker::where('status', 2)->count(),
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }

}