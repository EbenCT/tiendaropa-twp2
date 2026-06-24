<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Models\Categoria;
use App\Models\Catalogo;
use Illuminate\Http\Request;

class BuscadorController extends Controller
{
    /**
     * Buscador global — retorna resultados filtrados por nivel de rol.
     * Siempre presente en el header. Respeta restricciones de acceso.
     */
    public function buscar(Request $request)
    {
        $q         = trim($request->get('q', ''));
        $nivelRol  = $request->user()?->nivel_rol ?? 0;

        if (strlen($q) < 2) {
            return response()->json(['productos' => [], 'categorias' => [], 'acciones' => []]);
        }

        // Productos
        $productos = Producto::where('activo', true)
            ->where(fn($query) => $query
                ->where('nombre', 'ilike', "%{$q}%")
                ->orWhere('descripcion', 'ilike', "%{$q}%")
            )
            ->with('imagenPrincipal')
            ->limit(6)
            ->get()
            ->map(fn($p) => [
                'id'     => $p->id,
                'nombre' => $p->nombre,
                'precio' => $p->precio_unitario,
                'imagen' => $p->imagenPrincipal?->url ?? $p->imagen_url,
                'url'    => route('catalogo.show', $p->id),
            ]);

        // Categorías
        $categorias = Categoria::where('activo', true)
            ->where('nombre', 'ilike', "%{$q}%")
            ->limit(4)
            ->get()
            ->map(fn($c) => [
                'id'     => $c->id,
                'nombre' => $c->nombre,
                'url'    => route('catalogo', ['categoria' => $c->id]),
            ]);

        // Acciones del sistema (filtradas por rol)
        $acciones = $this->accionesSistema($q, $nivelRol);

        return response()->json(compact('productos', 'categorias', 'acciones'));
    }

    private function accionesSistema(string $q, int $nivelRol): array
    {
        $todas = [
            ['label' => 'Gestionar productos',  'url' => route('admin.productos.index'),  'nivel' => 2],
            ['label' => 'Gestionar pedidos',    'url' => route('admin.pedidos.index'),    'nivel' => 2],
            ['label' => 'Gestionar inventario', 'url' => route('admin.inventario.index'), 'nivel' => 2],
            ['label' => 'Gestionar usuarios',   'url' => route('admin.usuarios.index'),   'nivel' => 3],
            ['label' => 'Reportes de ventas',   'url' => route('admin.reportes'),         'nivel' => 3],
            ['label' => 'Estadísticas',         'url' => route('admin.estadisticas'),     'nivel' => 3],
            ['label' => 'Menú dinámico',        'url' => route('admin.menu.index'),       'nivel' => 4],
        ];

        return array_values(array_filter($todas, fn($a) =>
            $a['nivel'] <= $nivelRol &&
            str_contains(mb_strtolower($a['label']), mb_strtolower($q))
        ));
    }
}
