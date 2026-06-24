<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pedido;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PedidoAdminController extends Controller
{
    public function index(Request $request)
    {
        $query = Pedido::with(['usuario', 'detalles']);

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        $pedidos = $query->orderByDesc('created_at')->paginate(15)->withQueryString();

        return Inertia::render('Admin/Pedidos/Index', compact('pedidos'));
    }

    public function show(int $id)
    {
        $pedido = Pedido::with(['usuario', 'detalles.producto.imagenPrincipal', 'venta.pagos'])
            ->findOrFail($id);

        return Inertia::render('Admin/Pedidos/Show', compact('pedido'));
    }

    public function cambiarEstado(Request $request, int $id)
    {
        $request->validate([
            'estado' => 'required|in:PENDIENTE,CONFIRMADO,ENVIADO,ENTREGADO',
        ], [
            'estado.required' => 'El estado es obligatorio.',
            'estado.in'       => 'Estado no válido.',
        ]);

        $pedido = Pedido::findOrFail($id);
        $pedido->update(['estado' => $request->estado]);

        return back()->with('success', 'Estado del pedido #' . $id . ' cambiado a ' . $request->estado . '.');
    }
}
