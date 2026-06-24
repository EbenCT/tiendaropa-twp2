<?php

namespace App\Http\Controllers\Cliente;

use App\Http\Controllers\Controller;
use App\Models\CarritoItem;
use App\Models\Producto;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CarritoController extends Controller
{
    public function index(Request $request)
    {
        $items = CarritoItem::where('usuario_id', $request->user()->id)
            ->with(['producto.imagenPrincipal', 'producto.categoria', 'talla'])
            ->get()
            ->map(fn($item) => [
                'id'       => $item->id,
                'cantidad' => $item->cantidad,
                'producto' => [
                    'id'              => $item->producto->id,
                    'nombre'          => $item->producto->nombre,
                    'precio_unitario' => $item->producto->precio_unitario,
                    'imagen'          => $item->producto->imagenPrincipal?->url ?? $item->producto->imagen_url,
                    'stock_actual'    => $item->producto->stock_actual,
                    'categoria'       => $item->producto->categoria?->nombre,
                ],
                'talla' => $item->talla ? [
                    'id'     => $item->talla->id,
                    'codigo' => $item->talla->codigo,
                ] : null,
                'subtotal' => $item->cantidad * $item->producto->precio_unitario,
            ]);

        $total = $items->sum('subtotal');

        return Inertia::render('Carrito/Index', compact('items', 'total'));
    }

    public function agregar(Request $request)
    {
        $request->validate([
            'producto_id' => 'required|exists:producto,id',
            'talla_id'    => 'nullable|exists:talla,id',
            'cantidad'    => 'required|integer|min:1|max:99',
        ], [
            'producto_id.required' => 'El producto es obligatorio.',
            'producto_id.exists'   => 'El producto no existe.',
            'cantidad.required'    => 'La cantidad es obligatoria.',
            'cantidad.min'         => 'La cantidad mínima es 1.',
        ]);

        $existing = CarritoItem::where('usuario_id', $request->user()->id)
            ->where('producto_id', $request->producto_id)
            ->where('talla_id', $request->talla_id)
            ->first();

        if ($existing) {
            $existing->increment('cantidad', $request->cantidad);
        } else {
            CarritoItem::create([
                'usuario_id'  => $request->user()->id,
                'producto_id' => $request->producto_id,
                'talla_id'    => $request->talla_id,
                'cantidad'    => $request->cantidad,
            ]);
        }

        return back()->with('success', 'Producto agregado al carrito.');
    }

    public function actualizar(Request $request, int $id)
    {
        $request->validate([
            'cantidad' => 'required|integer|min:1|max:99',
        ], [
            'cantidad.required' => 'La cantidad es obligatoria.',
            'cantidad.min'      => 'La cantidad mínima es 1.',
        ]);

        $item = CarritoItem::where('usuario_id', $request->user()->id)->findOrFail($id);
        $item->update(['cantidad' => $request->cantidad]);

        return back()->with('success', 'Carrito actualizado.');
    }

    public function eliminar(Request $request, int $id)
    {
        CarritoItem::where('usuario_id', $request->user()->id)->findOrFail($id)->delete();
        return back()->with('success', 'Producto eliminado del carrito.');
    }
}
