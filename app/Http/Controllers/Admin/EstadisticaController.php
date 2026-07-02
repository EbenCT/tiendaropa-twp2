<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bitacora;
use App\Models\DetallePedido;
use App\Models\PageVisit;
use App\Models\Pedido;
use App\Models\Producto;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EstadisticaController extends Controller
{
    public function index()
    {
        // Productos más vendidos (top 10)
        $topProductos = Producto::select('producto.id', 'producto.nombre', 'producto.imagen_url')
            ->join('detalle_pedido', 'producto.id', '=', 'detalle_pedido.producto_id')
            ->selectRaw('SUM(detalle_pedido.cantidad) as total_vendido')
            ->groupBy('producto.id', 'producto.nombre', 'producto.imagen_url')
            ->orderByDesc('total_vendido')
            ->limit(10)
            ->get();

        // Pedidos por estado
        $pedidosPorEstado = Pedido::selectRaw('estado, COUNT(*) as total')
            ->groupBy('estado')
            ->get()
            ->pluck('total', 'estado');

        // Visitas por página (top 10)
        $topVisitas = PageVisit::orderByDesc('visit_count')
            ->limit(10)
            ->get();

        // Ventas por mes (últimos 12 meses)
        $ventasPorMes = \DB::table('venta')
            ->selectRaw("EXTRACT(MONTH FROM fecha) as mes, EXTRACT(YEAR FROM fecha) as anio, COUNT(*) as cantidad, SUM(total) as total")
            ->whereRaw("fecha >= NOW() - INTERVAL '12 months'")
            ->groupByRaw("EXTRACT(YEAR FROM fecha), EXTRACT(MONTH FROM fecha)")
            ->orderByRaw("anio, mes")
            ->get()
            ->map(fn ($r) => [
                'mes'      => (int) $r->mes,
                'anio'     => (int) $r->anio,
                'cantidad' => (int) $r->cantidad,
                'total'    => (float) $r->total,
            ]);

        // Distribución de usuarios por rol
        $usuariosPorRol = \DB::table('usuario')
            ->selectRaw('COALESCE(rol_nuevo, LOWER(rol)) as rol_label, COUNT(*) as total')
            ->groupByRaw('COALESCE(rol_nuevo, LOWER(rol))')
            ->get()
            ->pluck('total', 'rol_label');

        // Últimas entradas de la bitácora (para el panel de auditoría rápido)
        $ultimasBitacora = Bitacora::with('usuario')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get()
            ->map(fn ($r) => [
                'accion'      => $r->accion,
                'modulo'      => $r->modulo,
                'descripcion' => $r->descripcion,
                'ip'          => $r->ip,
                'usuario'     => $r->usuario ? $r->usuario->nombre . ' ' . $r->usuario->apellido : null,
                'fecha'       => $r->created_at?->format('d/m/Y H:i'),
            ]);

        // Totales generales
        $totalProductos = Producto::where('activo', true)->count();
        $totalPedidos   = Pedido::count();
        $totalVentas    = \DB::table('venta')->sum('total');
        $totalUsuarios  = \DB::table('usuario')->count();

        return Inertia::render('Admin/Estadisticas/Index', [
            'topProductos'    => $topProductos,
            'pedidosPorEstado'=> $pedidosPorEstado,
            'topVisitas'      => $topVisitas,
            'ventasPorMes'    => $ventasPorMes,
            'usuariosPorRol'  => $usuariosPorRol,
            'ultimasBitacora' => $ultimasBitacora,
            'resumen'         => [
                'totalProductos' => $totalProductos,
                'totalPedidos'   => $totalPedidos,
                'totalVentas'    => $totalVentas,
                'totalUsuarios'  => $totalUsuarios,
            ],
        ]);
    }
}
