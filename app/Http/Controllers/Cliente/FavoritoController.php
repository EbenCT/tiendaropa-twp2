<?php

namespace App\Http\Controllers\Cliente;

use App\Http\Controllers\Controller;
use App\Models\Favorito;
use Illuminate\Http\Request;
use Inertia\Inertia;

class FavoritoController extends Controller
{
    public function index(Request $request)
    {
        $favoritos = Favorito::where('usuario_id', $request->user()->id)
            ->with(['producto.imagenPrincipal', 'producto.categoria'])
            ->get()
            ->map(fn($fav) => [
                'id'       => $fav->id,
                'producto' => [
                    'id'              => $fav->producto->id,
                    'nombre'          => $fav->producto->nombre,
                    'precio_unitario' => $fav->producto->precio_unitario,
                    'imagen_url'      => $fav->producto->imagenPrincipal?->url ?? $fav->producto->imagen_url,
                    'stock_actual'    => $fav->producto->stock_actual,
                    'categoria'       => $fav->producto->categoria,
                    'activo'          => $fav->producto->activo,
                ],
            ]);

        return Inertia::render('Favoritos/Index', compact('favoritos'));
    }

    public function toggle(Request $request, int $id)
    {
        $existing = Favorito::where('usuario_id', $request->user()->id)
            ->where('producto_id', $id)
            ->first();

        if ($existing) {
            $existing->delete();
            return back()->with('info', 'Producto eliminado de favoritos.');
        }

        Favorito::create([
            'usuario_id'  => $request->user()->id,
            'producto_id' => $id,
        ]);

        return back()->with('success', 'Producto agregado a favoritos.');
    }
}
