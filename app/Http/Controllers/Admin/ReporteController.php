<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Categoria;
use App\Models\Producto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ReporteController extends Controller
{
    public function index(Request $request)
    {
        $anio       = (int) $request->get('anio', date('Y'));
        $mes        = $request->filled('mes') ? (int) $request->mes : null;
        $categoriaId = $request->filled('categoria') ? (int) $request->categoria : null;

        // ── Ventas por mes ───────────────────────────────────────────
        $qVentas = DB::table('venta')
            ->join('pedido', 'pedido.id', '=', 'venta.pedido_id')
            ->selectRaw("EXTRACT(MONTH FROM venta.fecha) as mes, COUNT(*) as cantidad, COALESCE(SUM(venta.total), 0) as total")
            ->whereRaw("EXTRACT(YEAR FROM venta.fecha) = ?", [$anio]);

        if ($mes) {
            $qVentas->whereRaw("EXTRACT(MONTH FROM venta.fecha) = ?", [$mes]);
        }

        if ($categoriaId) {
            $qVentas->join('detalle_pedido', 'detalle_pedido.pedido_id', '=', 'pedido.id')
                ->join('producto', 'producto.id', '=', 'detalle_pedido.producto_id')
                ->where('producto.categoria_id', $categoriaId);
        }

        $ventasPorMes = $qVentas
            ->groupByRaw("EXTRACT(MONTH FROM venta.fecha)")
            ->orderByRaw("EXTRACT(MONTH FROM venta.fecha)")
            ->get()
            ->map(fn($row) => [
                'mes'      => (int) $row->mes,
                'nombre'   => $this->nombreMes((int) $row->mes),
                'cantidad' => (int) $row->cantidad,
                'total'    => (float) $row->total,
            ]);

        // ── Top productos del periodo ────────────────────────────────
        $qTop = DB::table('detalle_pedido')
            ->join('producto', 'producto.id', '=', 'detalle_pedido.producto_id')
            ->join('pedido', 'pedido.id', '=', 'detalle_pedido.pedido_id')
            ->join('venta', 'venta.pedido_id', '=', 'pedido.id')
            ->selectRaw("producto.id, producto.nombre, SUM(detalle_pedido.cantidad) as total_uds, COALESCE(SUM(detalle_pedido.subtotal), 0) as total_bs")
            ->whereRaw("EXTRACT(YEAR FROM venta.fecha) = ?", [$anio]);

        if ($mes) {
            $qTop->whereRaw("EXTRACT(MONTH FROM venta.fecha) = ?", [$mes]);
        }
        if ($categoriaId) {
            $qTop->where('producto.categoria_id', $categoriaId);
        }

        $topProductos = $qTop
            ->groupBy('producto.id', 'producto.nombre')
            ->orderByDesc('total_uds')
            ->limit(10)
            ->get();

        // ── Ventas por categoría ─────────────────────────────────────
        $ventasPorCategoria = DB::table('detalle_pedido')
            ->join('producto', 'producto.id', '=', 'detalle_pedido.producto_id')
            ->join('categoria', 'categoria.id', '=', 'producto.categoria_id')
            ->join('pedido', 'pedido.id', '=', 'detalle_pedido.pedido_id')
            ->join('venta', 'venta.pedido_id', '=', 'pedido.id')
            ->selectRaw("categoria.nombre as categoria, SUM(detalle_pedido.cantidad) as total_uds, COALESCE(SUM(detalle_pedido.subtotal), 0) as total_bs")
            ->whereRaw("EXTRACT(YEAR FROM venta.fecha) = ?", [$anio])
            ->when($mes, fn($q) => $q->whereRaw("EXTRACT(MONTH FROM venta.fecha) = ?", [$mes]))
            ->groupBy('categoria.nombre')
            ->orderByDesc('total_bs')
            ->get();

        // ── Años disponibles ─────────────────────────────────────────
        $aniosDisponibles = DB::table('venta')
            ->selectRaw("DISTINCT EXTRACT(YEAR FROM fecha) as anio")
            ->orderBy('anio')
            ->pluck('anio')
            ->map(fn($a) => (int) $a);

        if ($aniosDisponibles->isEmpty()) {
            $aniosDisponibles = collect([(int) date('Y')]);
        }

        $categorias  = Categoria::where('activo', true)->orderBy('nombre')->get(['id', 'nombre']);
        $totalAnual  = $ventasPorMes->sum('total');
        $totalVentas = $ventasPorMes->sum('cantidad');

        return Inertia::render('Admin/Reportes/Index', [
            'ventasPorMes'        => $ventasPorMes,
            'topProductos'        => $topProductos,
            'ventasPorCategoria'  => $ventasPorCategoria,
            'anio'                => $anio,
            'mes'                 => $mes,
            'categoriaFiltro'     => $categoriaId,
            'aniosDisponibles'    => $aniosDisponibles,
            'categorias'          => $categorias,
            'totalAnual'          => $totalAnual,
            'totalVentas'         => $totalVentas,
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
