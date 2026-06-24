<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Models\Catalogo;
use App\Models\Categoria;
use App\Models\Talla;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CatalogoController extends Controller
{
    public function index(Request $request)
    {
        $query = Producto::where('activo', true)
            ->with(['imagenPrincipal', 'categoria', 'tallas']);

        // Filtros
        if ($request->filled('catalogo')) {
            $query->whereHas('catalogos', fn($q) => $q->where('catalogo.id', $request->catalogo));
        }
        if ($request->filled('categoria')) {
            $query->where('categoria_id', $request->categoria);
        }
        if ($request->filled('talla')) {
            $query->whereHas('tallas', fn($q) => $q->where('talla.id', $request->talla));
        }
        if ($request->filled('precio_min')) {
            $query->where('precio_unitario', '>=', $request->precio_min);
        }
        if ($request->filled('precio_max')) {
            $query->where('precio_unitario', '<=', $request->precio_max);
        }
        if ($request->filled('q')) {
            $query->where(function ($q) use ($request) {
                $q->where('nombre', 'ilike', '%' . $request->q . '%')
                  ->orWhere('descripcion', 'ilike', '%' . $request->q . '%');
            });
        }

        $productos  = $query->paginate(12)->withQueryString();
        $catalogos  = Catalogo::where('activo', true)->get();
        $categorias = Categoria::where('activo', true)->get();
        $tallas     = Talla::orderBy('tipo')->orderBy('codigo')->get();
        $filtros    = $request->only(['catalogo', 'categoria', 'talla', 'precio_min', 'precio_max', 'q']);

        return Inertia::render('Catalogo/Index', compact('productos', 'catalogos', 'categorias', 'tallas', 'filtros'));
    }

    public function show(int $id)
    {
        $producto = Producto::where('activo', true)
            ->with(['imagenes', 'categoria', 'tallas', 'promociones'])
            ->findOrFail($id);

        $relacionados = Producto::where('categoria_id', $producto->categoria_id)
            ->where('id', '!=', $id)
            ->where('activo', true)
            ->with('imagenPrincipal')
            ->limit(4)
            ->get();

        // Métricas: total vendido
        $totalVendido = $producto->total_vendido;

        return Inertia::render('Catalogo/Show', compact('producto', 'relacionados', 'totalVendido'));
    }
}
