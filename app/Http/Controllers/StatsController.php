<?php

namespace App\Http\Controllers;

use App\Models\Locker;
use App\Models\LockerAssignment;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StatsController extends Controller
{
    /**
     * GET /admin/stats/summary
     * Totales de lockers por estado
     */
    public function summary()
    {
        return response()->json([
            'total'       => Locker::count(),
            'occupied'    => Locker::where('status', 1)->count(),
            'available'   => Locker::where('status', 0)->count(),
            'maintenance' => Locker::where('status', 2)->count(),
        ]);
    }

    /**
     * GET /admin/stats/by-career
     * Cuántos ocupantes aglomerados por carrera universitaria
     */
    public function byCareer()
    {
        $stats = DB::table('locker_assignments')
            ->join('users', 'locker_assignments.user_id', '=', 'users.id')
            ->where('locker_assignments.assignment_status', 'active')
            ->select('users.career', DB::raw('COUNT(locker_assignments.assignment_id) as count'))
            ->groupBy('users.career')
            ->get();

        // Fix null careers if untracked
        $formattedStats = $stats->map(function ($item) {
            return [
                'career' => $item->career ?: 'Sin especificar',
                'count'  => $item->count
            ];
        });

        return response()->json($formattedStats);
    }

    /**
     * GET /admin/stats/by-semester
     * Montos facturados acumulados por semestre
     */
    public function bySemester(Request $request)
    {
        $query = DB::table('payments')
            ->select('semester', DB::raw('SUM(amount) as total'), DB::raw('COUNT(payment_id) as count'))
            ->groupBy('semester');

        if ($request->has('semester') && $request->semester) {
            $query->where('semester', $request->semester);
        }

        $stats = $query->get();

        return response()->json($stats);
    }

    /**
     * GET /admin/stats/monthly
     * Curva de demanda (Asignaciones creadas por día del mes)
     */
    public function monthly(Request $request)
    {
        $month = $request->input('month', now()->month);
        $year = $request->input('year', now()->year);

        $stats = DB::table('locker_assignments')
            ->select(DB::raw('DAY(created_at) as day'), DB::raw('COUNT(assignment_id) as count'))
            ->whereMonth('created_at', $month)
            ->whereYear('created_at', $year)
            ->groupBy('day')
            ->get();

        // Para facilitar la gráfica, llenamos los días no reportados con 0
        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
        $response = [];

        // Inicializamos del 1 al $daysInMonth
        for ($i = 1; $i <= $daysInMonth; $i++) {
            $response[$i] = ['day' => $i, 'count' => 0];
        }

        // Rellenamos la información real
        foreach ($stats as $stat) {
            $response[$stat->day] = ['day' => $stat->day, 'count' => $stat->count];
        }

        return response()->json(array_values($response));
    }
}
