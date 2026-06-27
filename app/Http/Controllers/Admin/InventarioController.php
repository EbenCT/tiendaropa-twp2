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

        $movimientos = $query->orderByDesc('fecha')->paginate(20)->withQueryString();
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

        if ($request->tipo === 'salida') {
            $stockDisponible = Producto::findOrFail($request->producto_id)->stock_actual;
            if ($request->cantidad > $stockDisponible) {
                return back()->withErrors([
                    'cantidad' => "La cantidad de salida ({$request->cantidad}) supera el stock disponible ({$stockDisponible}).",
                ]);
            }
        }

        Inventario::create([
            'producto_id' => $request->producto_id,
            'usuario_id'  => $request->user()->id,
            'tipo'        => $this->tipoLegado($request->tipo),
            'cantidad'    => $request->cantidad,
            'tecnica'     => $request->tecnica ?? 'PROMEDIO',
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

    /**
     * La columna heredada 'tipo' tiene un CHECK constraint que solo permite
     * INGRESO/SALIDA (mayúsculas, en español), por lo que 'entrada'/'salida'
     * deben mapearse a un valor permitido para no violar la BD.
     */
    private function tipoLegado(string $tipo): string
    {
        return $tipo === 'entrada' ? 'INGRESO' : 'SALIDA';
    }
}
