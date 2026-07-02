<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bitacora;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BitacoraController extends Controller
{
    public function index(Request $request)
    {
        $query = Bitacora::with('usuario')
            ->orderByDesc('created_at');

        if ($request->filled('modulo')) {
            $query->where('modulo', $request->modulo);
        }

        if ($request->filled('accion')) {
            $query->where('accion', $request->accion);
        }

        if ($request->filled('usuario_id')) {
            $query->where('usuario_id', $request->usuario_id);
        }

        $registros = $query->paginate(50)->withQueryString();

        $modulos = Bitacora::select('modulo')->distinct()->pluck('modulo')->filter()->values();
        $acciones = Bitacora::select('accion')->distinct()->pluck('accion')->filter()->values();

        return Inertia::render('Admin/Bitacora/Index', [
            'registros' => $registros,
            'modulos'   => $modulos,
            'acciones'  => $acciones,
            'filtros'   => $request->only('modulo', 'accion', 'usuario_id'),
        ]);
    }
}
