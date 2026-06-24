<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Models\Promocion;
use Inertia\Inertia;

class HomeController extends Controller
{
    public function index()
    {
        try {
            $destacados = Producto::where('destacado', true)
                ->where('activo', true)
                ->select('id','categoria_id','nombre','precio_unitario','imagen_url','stock_actual','activo','destacado','es_nueva_coleccion')
                ->with(['categoria:id,nombre'])
                ->limit(8)
                ->get();
        } catch (\Throwable $e) {
            $destacados = collect();
        }

        try {
            $nuevaColeccion = Producto::where('es_nueva_coleccion', true)
                ->where('activo', true)
                ->select('id','categoria_id','nombre','precio_unitario','imagen_url','stock_actual','activo','destacado','es_nueva_coleccion')
                ->with(['categoria:id,nombre'])
                ->limit(8)
                ->get();
        } catch (\Throwable $e) {
            $nuevaColeccion = collect();
        }

        try {
            $promociones = Promocion::where('activo', true)
                ->where('fecha_inicio', '<=', now()->toDateString())
                ->where('fecha_fin', '>=', now()->toDateString())
                ->with(['productos' => fn($q) => $q
                    ->select('producto.id','producto.nombre','producto.precio_unitario','producto.imagen_url')
                    ->limit(4)
                ])
                ->limit(3)
                ->get();
        } catch (\Throwable $e) {
            $promociones = collect();
        }

        return Inertia::render('Home/Index', compact('destacados', 'nuevaColeccion', 'promociones'));
    }
}
