<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ReporteController extends Controller
{
    public function index(Request $request)
    {
        $anio = $request->get('anio', date('Y'));

        // Ventas por mes
        $ventasPorMes = DB::table('venta')
            ->selectRaw("EXTRACT(MONTH FROM fecha) as mes, COUNT(*) as cantidad, COALESCE(SUM(total), 0) as total")
            ->whereRaw("EXTRACT(YEAR FROM fecha) = ?", [$anio])
            ->groupByRaw("EXTRACT(MONTH FROM fecha)")
            ->orderByRaw("EXTRACT(MONTH FROM fecha)")
            ->get()
            ->map(fn($row) => [
                'mes'      => (int) $row->mes,
                'nombre'   => $this->nombreMes((int) $row->mes),
                'cantidad' => (int) $row->cantidad,
                'total'    => (float) $row->total,
            ]);

        // Años disponibles
        $aniosDisponibles = DB::table('venta')
            ->selectRaw("DISTINCT EXTRACT(YEAR FROM fecha) as anio")
            ->orderBy('anio')
            ->pluck('anio')
            ->map(fn($a) => (int) $a);

        if ($aniosDisponibles->isEmpty()) {
            $aniosDisponibles = collect([(int) date('Y')]);
        }

        $totalAnual = $ventasPorMes->sum('total');
        $totalVentas = $ventasPorMes->sum('cantidad');

        return Inertia::render('Admin/Reportes/Index', [
            'ventasPorMes'      => $ventasPorMes,
            'anio'              => (int) $anio,
            'aniosDisponibles'  => $aniosDisponibles,
            'totalAnual'        => $totalAnual,
            'totalVentas'       => $totalVentas,
        ]);
    }

    private function nombreMes(int $mes): string
    {
        $nombres = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
        ];
        return $nombres[$mes] ?? '';
    }
}
