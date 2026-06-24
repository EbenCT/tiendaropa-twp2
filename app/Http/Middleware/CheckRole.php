<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Middleware para proteger rutas según el nivel del rol del usuario.
 * Uso en rutas: ->middleware('role:vendedor') o ->middleware('role:admin')
 */
class CheckRole
{
    /**
     * Niveles de rol:
     *   admin       = 4
     *   propietario = 3
     *   vendedor    = 2
     *   cliente     = 1
     */
    protected array $niveles = [
        'admin'       => 4,
        'propietario' => 3,
        'vendedor'    => 2,
        'cliente'     => 1,
    ];

    public function handle(Request $request, Closure $next, string $rolRequerido): mixed
    {
        if (!$request->user()) {
            return redirect()->route('login')
                ->with('error', 'Debes iniciar sesión para acceder a esta sección.');
        }

        $rolUsuario = $request->user()->rol_nuevo ?? strtolower($request->user()->rol ?? 'cliente');
        $nivelUsuario = $this->niveles[$rolUsuario] ?? 0;
        $nivelRequerido = $this->niveles[$rolRequerido] ?? 99;

        if ($nivelUsuario < $nivelRequerido) {
            abort(403, 'No tienes permisos para acceder a esta sección.');
        }

        return $next($request);
    }
}
