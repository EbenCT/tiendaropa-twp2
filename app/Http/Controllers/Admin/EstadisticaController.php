<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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

        // Totales generales
        $totalProductos = Producto::where('activo', true)->count();
        $totalPedidos   = Pedido::count();
        $totalVentas    = \DB::table('venta')->sum('total');
        $totalUsuarios  = \DB::table('usuario')->count();

        return Inertia::render('Admin/Estadisticas/Index', [
            'topProductos'     => $topProductos,
            'pedidosPorEstado' => $pedidosPorEstado,
            'topVisitas'       => $topVisitas,
            'resumen'          => [
                'totalProductos' => $totalProductos,
                'totalPedidos'   => $totalPedidos,
                'totalVentas'    => $totalVentas,
                'totalUsuarios'  => $totalUsuarios,
            ],
        ]);
    }
}
