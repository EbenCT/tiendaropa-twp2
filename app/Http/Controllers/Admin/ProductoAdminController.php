<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Producto;
use App\Models\ProductoImagen;
use App\Models\Categoria;
use App\Models\Catalogo;
use App\Models\Talla;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class ProductoAdminController extends Controller
{
    public function index(Request $request)
    {
        $query = Producto::with(['categoria', 'imagenPrincipal']);

        if ($request->filled('q')) {
            $query->where('nombre', 'ilike', '%' . $request->q . '%');
        }
        if ($request->filled('categoria')) {
            $query->where('categoria_id', $request->categoria);
        }

        $productos  = $query->orderByDesc('id')->paginate(15)->withQueryString();
        $categorias = Categoria::where('activo', true)->get();

        return Inertia::render('Admin/Productos/Index', compact('productos', 'categorias'));
    }

    public function create()
    {
        $categorias = Categoria::where('activo', true)->get();
        $catalogos  = Catalogo::where('activo', true)->get();
        $tallas     = Talla::orderBy('tipo')->orderBy('codigo')->get();

        return Inertia::render('Admin/Productos/Form', [
            'categorias' => $categorias,
            'catalogos'  => $catalogos,
            'tallas'     => $tallas,
            'producto'   => null,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre'          => 'required|string|max:200',
            'descripcion'     => 'nullable|string',
            'categoria_id'    => 'required|exists:categoria,id',
            'precio_unitario' => 'required|numeric|min:0',
            'stock_actual'    => 'required|integer|min:0',
            'imagen_url'      => 'nullable|string|max:500',
            'destacado'       => 'boolean',
            'es_nueva_coleccion' => 'boolean',
        ], [
            'nombre.required'          => 'El nombre del producto es obligatorio.',
            'categoria_id.required'    => 'La categoría es obligatoria.',
            'precio_unitario.required' => 'El precio es obligatorio.',
            'stock_actual.required'    => 'El stock es obligatorio.',
        ]);

        $producto = Producto::create(array_merge(
            $request->only(['nombre', 'descripcion', 'categoria_id', 'precio_unitario', 'stock_actual', 'imagen_url']),
            [
                'activo'            => true,
                'destacado'         => $request->boolean('destacado'),
                'es_nueva_coleccion'=> $request->boolean('es_nueva_coleccion'),
            ]
        ));

        // Tallas
        if ($request->has('tallas')) {
            foreach ($request->tallas as $tallaData) {
                $producto->tallas()->attach($tallaData['id'], ['stock' => $tallaData['stock'] ?? 0]);
            }
        }

        // Catálogos
        if ($request->has('catalogos')) {
            $producto->catalogos()->sync($request->catalogos);
        }

        return redirect()->route('admin.productos.index')
            ->with('success', 'Producto "' . $producto->nombre . '" creado.');
    }

    public function edit(int $id)
    {
        $producto   = Producto::with(['imagenes', 'tallas', 'catalogos'])->findOrFail($id);
        $categorias = Categoria::where('activo', true)->get();
        $catalogos  = Catalogo::where('activo', true)->get();
        $tallas     = Talla::orderBy('tipo')->orderBy('codigo')->get();

        return Inertia::render('Admin/Productos/Form', compact('producto', 'categorias', 'catalogos', 'tallas'));
    }

    public function update(Request $request, int $id)
    {
        $request->validate([
            'nombre'          => 'required|string|max:200',
            'descripcion'     => 'nullable|string',
            'categoria_id'    => 'required|exists:categoria,id',
            'precio_unitario' => 'required|numeric|min:0',
            'stock_actual'    => 'required|integer|min:0',
            'imagen_url'      => 'nullable|string|max:500',
            'destacado'       => 'boolean',
            'es_nueva_coleccion' => 'boolean',
            'activo'          => 'boolean',
        ], [
            'nombre.required'          => 'El nombre del producto es obligatorio.',
            'categoria_id.required'    => 'La categoría es obligatoria.',
            'precio_unitario.required' => 'El precio es obligatorio.',
            'stock_actual.required'    => 'El stock es obligatorio.',
        ]);

        $producto = Producto::findOrFail($id);
        $producto->update(array_merge(
            $request->only(['nombre', 'descripcion', 'categoria_id', 'precio_unitario', 'stock_actual', 'imagen_url', 'activo']),
            [
                'destacado'         => $request->boolean('destacado'),
                'es_nueva_coleccion'=> $request->boolean('es_nueva_coleccion'),
            ]
        ));

        // Tallas sync
        if ($request->has('tallas')) {
            $syncData = [];
            foreach ($request->tallas as $t) {
                $syncData[$t['id']] = ['stock' => $t['stock'] ?? 0];
            }
            $producto->tallas()->sync($syncData);
        }

        // Catálogos
        if ($request->has('catalogos')) {
            $producto->catalogos()->sync($request->catalogos);
        }

        return redirect()->route('admin.productos.index')
            ->with('success', 'Producto "' . $producto->nombre . '" actualizado.');
    }

    public function destroy(int $id)
    {
        $producto = Producto::findOrFail($id);
        $producto->update(['activo' => false]);

        return back()->with('success', 'Producto desactivado.');
    }
}
