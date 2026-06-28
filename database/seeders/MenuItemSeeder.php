<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MenuItemSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('menu_item')->truncate();

        // nivel 0 = público, 1 = cliente, 2 = vendedor, 3 = propietario, 4 = admin
        $items = [
            // ── Público ──────────────────────────────────────────────
            ['label' => 'Inicio',      'route_name' => 'home',         'icon' => 'home',       'role_nivel_minimo' => 0, 'orden' => 1],
            ['label' => 'Catálogo',    'route_name' => 'catalogo',     'icon' => 'shirt',      'role_nivel_minimo' => 0, 'orden' => 2],
            ['label' => 'Promociones', 'route_name' => 'promociones',  'icon' => 'tag',        'role_nivel_minimo' => 0, 'orden' => 3],

            // ── Cliente ───────────────────────────────────────────────
            ['label' => 'Mi Carrito',  'route_name' => 'carrito.index', 'icon' => 'shopping-cart', 'role_nivel_minimo' => 1, 'orden' => 4],
            ['label' => 'Favoritos',   'route_name' => 'favoritos.index', 'icon' => 'heart',      'role_nivel_minimo' => 1, 'orden' => 5],
            ['label' => 'Mis Pedidos', 'route_name' => 'pedidos.historial', 'icon' => 'package', 'role_nivel_minimo' => 1, 'orden' => 6],
            ['label' => 'Métodos de Pago', 'route_name' => 'metodos-pago.index', 'icon' => 'credit-card', 'role_nivel_minimo' => 1, 'orden' => 7],

            // ── Vendedor ──────────────────────────────────────────────
            ['label' => 'Gestión',     'route_name' => null,           'icon' => 'settings',   'role_nivel_minimo' => 2, 'orden' => 8],

            // ── Admin ─────────────────────────────────────────────────
            ['label' => 'Reportes',    'route_name' => 'admin.reportes', 'icon' => 'bar-chart', 'role_nivel_minimo' => 3, 'orden' => 9],
            ['label' => 'Sistema',     'route_name' => null,           'icon' => 'sliders',    'role_nivel_minimo' => 4, 'orden' => 10],
        ];

        $insertados = [];
        foreach ($items as $item) {
            $id = DB::table('menu_item')->insertGetId(array_merge($item, [
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]));
            $insertados[$item['label']] = $id;
        }

        // ── Submenú Gestión (vendedor+) ───────────────────────────────
        $subGestion = [
            ['label' => 'Productos',  'route_name' => 'admin.productos.index',  'icon' => 'shirt',      'role_nivel_minimo' => 2, 'orden' => 1],
            ['label' => 'Inventario', 'route_name' => 'admin.inventario.index', 'icon' => 'archive',    'role_nivel_minimo' => 2, 'orden' => 2],
            ['label' => 'Pedidos',    'route_name' => 'admin.pedidos.index',    'icon' => 'clipboard',  'role_nivel_minimo' => 2, 'orden' => 3],
            ['label' => 'Usuarios',   'route_name' => 'admin.usuarios.index',   'icon' => 'users',      'role_nivel_minimo' => 3, 'orden' => 4],
        ];
        foreach ($subGestion as $sub) {
            DB::table('menu_item')->insert(array_merge($sub, [
                'parent_id'  => $insertados['Gestión'],
                'activo'     => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }

        // ── Submenú Sistema (admin) ────────────────────────────────────
        $subSistema = [
            ['label' => 'Menú Dinámico',  'route_name' => 'admin.menu.index',       'icon' => 'menu',       'role_nivel_minimo' => 4, 'orden' => 1],
            ['label' => 'Estadísticas',   'route_name' => 'admin.estadisticas',     'icon' => 'trending-up','role_nivel_minimo' => 4, 'orden' => 2],
        ];
        foreach ($subSistema as $sub) {
            DB::table('menu_item')->insert(array_merge($sub, [
                'parent_id'  => $insertados['Sistema'],
                'activo'     => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }
}
