<?php

namespace App\Http\Controllers;

use App\Models\Locker;
use App\Models\User;
use App\Models\LockerAssignment;
use Illuminate\Http\Request;
use App\Models\Semester; 
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
        $mesFiltro = is_string($request->input('mes')) ? $request->input('mes') : '';
        $semestreFiltro = is_string($request->input('semestre')) ? $request->input('semestre') : '';

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
            // El año viene del texto del filtro: si no son 4 dígitos ("Octubre abc") daba un error 500
            $anioNum = isset($partes[1]) && preg_match('/^\d{4}$/', $partes[1]) ? (int) $partes[1] : Carbon::now()->year;
        }

        $semestre = (empty($mesFiltro) && !empty($semestreFiltro))
            ? Semester::where('name', $semestreFiltro)->first()
            : null;

        // Sin filtro (o con un semestre que no existe) se muestra el mes actual, y el select lo muestra elegido:
        // antes la página abría con el mes vacío
        if (empty($mesFiltro) && !$semestre) {
            $mesFiltro = array_search($mesNum, $mesesMap)." {$anioNum}";
            $semestreFiltro = '';
        }

        // 3. Asignaciones vigentes (con filtro de carrera); de ellas, las nuevas en el período
        $vigentes = LockerAssignment::whereIn('assignment_status', LockerAssignment::VIGENTES)
            ->when(!empty($carrera), fn ($q) => $q->whereHas('user', fn ($u) => $u->where('career', $carrera)));

        $queryBase = clone $vigentes;

        if ($semestre) {
            $inicio = Carbon::create($semestre->start_year, $semestre->start_month, 1);
            $fin = Carbon::create($semestre->end_year, $semestre->end_month, 1)->endOfMonth();
            $queryBase->whereBetween('created_at', [$inicio, $fin]);
        } else {
            $queryBase->whereMonth('created_at', $mesNum)
                      ->whereYear('created_at', $anioNum);
        }

        // KPIs. La ocupación cuenta los lockers ocupados del inventario, como el panel de inicio
        $totalCapacity = Locker::count();
        $ocupados = Locker::where('status', 1)->count();
        $usoEnMes = $queryBase->count();

        // Gráfica: acumulado por día del mes, o por mes del semestre.
        // Se agrupa en PHP: DAY() es de MySQL y no existe en SQLite ni en PostgreSQL
        $fechas = (clone $queryBase)->pluck('created_at');
        $chartPoints = [];
        $chartLabels = [];
        $acumulado = 0;

        if ($semestre) {
            $porMes = $fechas->countBy(fn ($fecha) => $fecha->format('Y-m'));
            for ($fecha = $inicio->copy(); $fecha->lte($fin); $fecha->addMonth()) {
                $acumulado += $porMes[$fecha->format('Y-m')] ?? 0;
                $chartPoints[] = $acumulado;
                $chartLabels[] = array_search($fecha->month, $mesesMap);
            }
        } else {
            $porDia = $fechas->countBy(fn ($fecha) => $fecha->day);
            for ($i = 1; $i <= Carbon::create($anioNum, $mesNum)->daysInMonth; $i++) {
                $acumulado += $porDia[$i] ?? 0;
                $chartPoints[] = $acumulado;
                $chartLabels[] = (string) $i;
            }
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
                'vigentes'        => $vigentes->count(),
                'ocupados'        => $ocupados,
                'capacidad_total' => $totalCapacity,
                'tasa_ocupacion'  => $totalCapacity > 0 ? number_format(($ocupados / $totalCapacity) * 100, 1) : "0.0",
                'chart_data'      => $chartPoints,
                'chart_labels'    => $chartLabels,
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