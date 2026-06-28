<?php

namespace App\Http\Controllers\Cliente;

use App\Http\Controllers\Controller;
use App\Models\CarritoItem;
use App\Models\DetallePedido;
use App\Models\Pedido;
use App\Models\Venta;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PedidoController extends Controller
{
    public function create(Request $request)
    {
        $items = CarritoItem::where('usuario_id', $request->user()->id)
            ->with(['producto', 'talla'])
            ->get();

        if ($items->isEmpty()) {
            return redirect()->route('carrito.index')->with('error', 'Tu carrito está vacío.');
        }

        $total = $items->sum(fn($i) => $i->cantidad * $i->producto->precio_unitario);

        return Inertia::render('Pedidos/Create', [
            'items' => $items,
            'total' => $total,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'direccion'  => 'required|string|max:500',
            'telefono'   => 'required|string|max:20',
            'referencia' => 'nullable|string|max:500',
        ], [
            'direccion.required' => 'La dirección de entrega es obligatoria.',
            'telefono.required'  => 'El teléfono de contacto es obligatorio.',
        ]);

        $user  = $request->user();
        $items = CarritoItem::where('usuario_id', $user->id)->with('producto')->get();

        if ($items->isEmpty()) {
            return back()->with('error', 'Tu carrito está vacío.');
        }

        $total = $items->sum(fn($i) => $i->cantidad * $i->producto->precio_unitario);

        // Crear pedido (la tabla 'pedido' no tiene columna 'total': se calcula desde detalle_pedido)
        $pedido = Pedido::create([
            'usuario_id'  => $user->id,
            'direccion'   => $request->direccion,
            'telefono'    => $request->telefono,
            'referencia'  => $request->referencia,
            'estado'      => 'PENDIENTE',
            'activo'      => true,
        ]);

        // Detalles
        foreach ($items as $item) {
            DetallePedido::create([
                'pedido_id'      => $pedido->id,
                'producto_id'    => $item->producto_id,
                'cantidad'       => $item->cantidad,
                'precio_unitario'=> $item->producto->precio_unitario,
                'subtotal'       => $item->cantidad * $item->producto->precio_unitario,
                'activo'         => true,
            ]);
        }

        // Crear venta
        Venta::create([
            'pedido_id'  => $pedido->id,
            'usuario_id' => $user->id,
            'total'      => $total,
            'fecha'      => now(),
            'activo'     => true,
        ]);

        // Vaciar carrito
        CarritoItem::where('usuario_id', $user->id)->delete();

        return redirect()->route('pedidos.pagar', $pedido->id)
            ->with('success', '¡Pedido #' . $pedido->id . ' creado! Completa tu pago para confirmarlo.');
    }

    public function historial(Request $request)
    {
        $pedidos = Pedido::where('usuario_id', $request->user()->id)
            ->with(['detalles.producto', 'venta.pagos'])
            ->orderByDesc('fecha')
            ->paginate(10);

        return Inertia::render('Pedidos/Historial', compact('pedidos'));
    }

    public function show(Request $request, int $id)
    {
        $pedido = Pedido::where('usuario_id', $request->user()->id)
            ->with(['detalles.producto.imagenPrincipal', 'venta.pagos.cuotas'])
            ->findOrFail($id);

        return Inertia::render('Pedidos/Show', compact('pedido'));
    }
}
