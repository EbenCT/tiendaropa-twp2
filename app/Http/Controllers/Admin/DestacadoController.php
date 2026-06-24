<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Producto;
use Illuminate\Http\Request;

class DestacadoController extends Controller
{
    public function toggle(Request $request, int $id)
    {
        $producto = Producto::findOrFail($id);
        $campo = $request->get('campo', 'destacado');

        if (!in_array($campo, ['destacado', 'es_nueva_coleccion'])) {
            return back()->with('error', 'Campo no válido.');
        }

        $producto->update([$campo => !$producto->$campo]);
        $estado = $producto->$campo ? 'activado' : 'desactivado';

        return back()->with('success', ucfirst($campo) . " {$estado} para \"{$producto->nombre}\".");
    }
}
