<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Inventario;
use App\Models\Producto;
use Illuminate\Http\Request;
use Inertia\Inertia;

class InventarioController extends Controller
{
    public function index(Request $request)
    {
        $query = Inventario::with(['producto']);

        if ($request->filled('producto_id')) {
            $query->where('producto_id', $request->producto_id);
        }

        $movimientos = $query->orderByDesc('created_at')->paginate(20)->withQueryString();
        $productos   = Producto::where('activo', true)->select('id', 'nombre')->get();

        return Inertia::render('Admin/Inventario/Index', compact('movimientos', 'productos'));
    }

    public function create()
    {
        $productos = Producto::where('activo', true)->select('id', 'nombre', 'stock_actual')->get();

        return Inertia::render('Admin/Inventario/Create', compact('productos'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'producto_id' => 'required|exists:producto,id',
            'tipo'        => 'required|in:entrada,salida',
            'cantidad'    => 'required|integer|min:1',
            'tecnica'     => 'nullable|string|max:20',
        ], [
            'producto_id.required' => 'Selecciona un producto.',
            'tipo.required'        => 'El tipo de movimiento es obligatorio.',
            'cantidad.required'    => 'La cantidad es obligatoria.',
            'cantidad.min'         => 'La cantidad mínima es 1.',
        ]);

        Inventario::create([
            'producto_id' => $request->producto_id,
            'tipo'        => $request->tipo,
            'cantidad'    => $request->cantidad,
            'tecnica'     => $request->tecnica ?? 'PROMEDIO',
            'activo'      => true,
        ]);

        // Actualizar stock del producto
        $producto = Producto::findOrFail($request->producto_id);
        if ($request->tipo === 'entrada') {
            $producto->increment('stock_actual', $request->cantidad);
        } else {
            $producto->decrement('stock_actual', max(0, $request->cantidad));
        }

        return redirect()->route('admin.inventario.index')
            ->with('success', 'Movimiento de inventario registrado.');
    }
}
