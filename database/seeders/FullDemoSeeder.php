<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

/**
 * Seeder completo: borra todo y recarga datos realistas para demo.
 * Incluye 1 año de pedidos, ventas y estadísticas.
 */
class FullDemoSeeder extends Seeder
{
    public function run(): void
    {
        // ─── 1. TRUNCAR TODAS LAS TABLAS ────────────────────────────────────
        DB::statement('
            TRUNCATE TABLE
                carrito_item, favorito, metodo_pago_usuario,
                cuota, pago, venta, detalle_pedido, pedido,
                inventario,
                producto_talla, producto_imagen,
                catalogo_producto, producto_promocion,
                producto, promocion, catalogo, categoria,
                usuario, roles, talla, menu_item, page_visit
            RESTART IDENTITY CASCADE
        ');

        // ─── 2. ROLES ────────────────────────────────────────────────────────
        DB::table('roles')->insert([
            ['slug' => 'admin',       'nombre' => 'Administrador', 'nivel' => 4, 'created_at' => now(), 'updated_at' => now()],
            ['slug' => 'propietario', 'nombre' => 'Propietario',   'nivel' => 3, 'created_at' => now(), 'updated_at' => now()],
            ['slug' => 'vendedor',    'nombre' => 'Vendedor',      'nivel' => 2, 'created_at' => now(), 'updated_at' => now()],
            ['slug' => 'cliente',     'nombre' => 'Cliente',       'nivel' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // ─── 3. TALLAS ───────────────────────────────────────────────────────
        $tallasDef = [
            ['codigo' => 'XS',  'descripcion' => 'Extra Small',       'tipo' => 'ropa_adulto'],
            ['codigo' => 'S',   'descripcion' => 'Small',             'tipo' => 'ropa_adulto'],
            ['codigo' => 'M',   'descripcion' => 'Medium',            'tipo' => 'ropa_adulto'],
            ['codigo' => 'L',   'descripcion' => 'Large',             'tipo' => 'ropa_adulto'],
            ['codigo' => 'XL',  'descripcion' => 'Extra Large',       'tipo' => 'ropa_adulto'],
            ['codigo' => 'XXL', 'descripcion' => 'Double XL',         'tipo' => 'ropa_adulto'],
            ['codigo' => '2',   'descripcion' => 'Talla 2 (2 años)',  'tipo' => 'ropa_nino'],
            ['codigo' => '4',   'descripcion' => 'Talla 4 (4 años)',  'tipo' => 'ropa_nino'],
            ['codigo' => '6',   'descripcion' => 'Talla 6 (6 años)',  'tipo' => 'ropa_nino'],
            ['codigo' => '8',   'descripcion' => 'Talla 8 (8 años)',  'tipo' => 'ropa_nino'],
            ['codigo' => '10',  'descripcion' => 'Talla 10 (10 años)','tipo' => 'ropa_nino'],
            ['codigo' => '12',  'descripcion' => 'Talla 12 (12 años)','tipo' => 'ropa_nino'],
            ['codigo' => '14',  'descripcion' => 'Talla 14 (14 años)','tipo' => 'ropa_nino'],
            ['codigo' => '36',  'descripcion' => 'Calzado talla 36',  'tipo' => 'calzado'],
            ['codigo' => '37',  'descripcion' => 'Calzado talla 37',  'tipo' => 'calzado'],
            ['codigo' => '38',  'descripcion' => 'Calzado talla 38',  'tipo' => 'calzado'],
            ['codigo' => '39',  'descripcion' => 'Calzado talla 39',  'tipo' => 'calzado'],
            ['codigo' => '40',  'descripcion' => 'Calzado talla 40',  'tipo' => 'calzado'],
            ['codigo' => '41',  'descripcion' => 'Calzado talla 41',  'tipo' => 'calzado'],
            ['codigo' => '42',  'descripcion' => 'Calzado talla 42',  'tipo' => 'calzado'],
            ['codigo' => '43',  'descripcion' => 'Calzado talla 43',  'tipo' => 'calzado'],
            ['codigo' => '28',  'descripcion' => 'Pantalón talla 28', 'tipo' => 'pantalon'],
            ['codigo' => '30',  'descripcion' => 'Pantalón talla 30', 'tipo' => 'pantalon'],
            ['codigo' => '32',  'descripcion' => 'Pantalón talla 32', 'tipo' => 'pantalon'],
            ['codigo' => '34',  'descripcion' => 'Pantalón talla 34', 'tipo' => 'pantalon'],
            ['codigo' => '36',  'descripcion' => 'Pantalón talla 36', 'tipo' => 'pantalon'],
            ['codigo' => '38',  'descripcion' => 'Pantalón talla 38', 'tipo' => 'pantalon'],
        ];
        foreach ($tallasDef as $t) {
            DB::table('talla')->insert(array_merge($t, ['created_at' => now(), 'updated_at' => now()]));
        }
        // Mapas de IDs por (codigo, tipo)
        $tallas = DB::table('talla')->get()->keyBy(fn($r) => "{$r->codigo}|{$r->tipo}");
        $tallasAdulto = ['XS','S','M','L','XL','XXL'];
        $tallasPantalon = ['28','30','32','34','36','38'];
        $tallasCalzado  = ['36','37','38','39','40','41','42','43'];
        $tallasNino     = ['2','4','6','8','10','12','14'];

        // ─── 4. MENÚ ─────────────────────────────────────────────────────────
        $menuItems = [
            ['label' => 'Inicio',         'route_name' => 'home',                   'icon' => 'home',          'role_nivel_minimo' => 0, 'orden' => 1],
            ['label' => 'Catálogo',       'route_name' => 'catalogo',               'icon' => 'shirt',         'role_nivel_minimo' => 0, 'orden' => 2],
            ['label' => 'Promociones',    'route_name' => 'promociones',            'icon' => 'tag',           'role_nivel_minimo' => 0, 'orden' => 3],
            ['label' => 'Mi Carrito',     'route_name' => 'carrito.index',          'icon' => 'shopping-cart', 'role_nivel_minimo' => 1, 'orden' => 4],
            ['label' => 'Favoritos',      'route_name' => 'favoritos.index',        'icon' => 'heart',         'role_nivel_minimo' => 1, 'orden' => 5],
            ['label' => 'Mis Pedidos',    'route_name' => 'pedidos.historial',      'icon' => 'package',       'role_nivel_minimo' => 1, 'orden' => 6],
            ['label' => 'Métodos de Pago','route_name' => 'metodos-pago.index',     'icon' => 'credit-card',   'role_nivel_minimo' => 1, 'orden' => 7],
            ['label' => 'Gestión',        'route_name' => null,                     'icon' => 'settings',      'role_nivel_minimo' => 2, 'orden' => 8],
            ['label' => 'Reportes',       'route_name' => 'admin.reportes',         'icon' => 'bar-chart',     'role_nivel_minimo' => 3, 'orden' => 9],
            ['label' => 'Sistema',        'route_name' => null,                     'icon' => 'sliders',       'role_nivel_minimo' => 4, 'orden' => 10],
        ];
        $menuIds = [];
        foreach ($menuItems as $item) {
            $id = DB::table('menu_item')->insertGetId(array_merge($item, ['activo' => true, 'created_at' => now(), 'updated_at' => now()]));
            $menuIds[$item['label']] = $id;
        }
        $subGestion = [
            ['label' => 'Productos',  'route_name' => 'admin.productos.index',  'icon' => 'shirt',     'role_nivel_minimo' => 2, 'orden' => 1],
            ['label' => 'Inventario', 'route_name' => 'admin.inventario.index', 'icon' => 'archive',   'role_nivel_minimo' => 2, 'orden' => 2],
            ['label' => 'Pedidos',    'route_name' => 'admin.pedidos.index',    'icon' => 'clipboard', 'role_nivel_minimo' => 2, 'orden' => 3],
            ['label' => 'Usuarios',   'route_name' => 'admin.usuarios.index',   'icon' => 'users',     'role_nivel_minimo' => 3, 'orden' => 4],
        ];
        foreach ($subGestion as $sub) {
            DB::table('menu_item')->insert(array_merge($sub, ['parent_id' => $menuIds['Gestión'], 'activo' => true, 'created_at' => now(), 'updated_at' => now()]));
        }
        $subSistema = [
            ['label' => 'Menú Dinámico', 'route_name' => 'admin.menu.index',   'icon' => 'menu',       'role_nivel_minimo' => 4, 'orden' => 1],
            ['label' => 'Estadísticas',  'route_name' => 'admin.estadisticas', 'icon' => 'trending-up','role_nivel_minimo' => 4, 'orden' => 2],
        ];
        foreach ($subSistema as $sub) {
            DB::table('menu_item')->insert(array_merge($sub, ['parent_id' => $menuIds['Sistema'], 'activo' => true, 'created_at' => now(), 'updated_at' => now()]));
        }

        // ─── 5. CATEGORÍAS ───────────────────────────────────────────────────
        $catIds = [];
        $categorias = [
            ['nombre' => 'Camisetas y Polos',    'descripcion' => 'Camisetas básicas, polos y blusas para hombre y mujer'],
            ['nombre' => 'Pantalones y Jeans',   'descripcion' => 'Jeans, pantalones chinos, palazzo y de vestir'],
            ['nombre' => 'Vestidos y Faldas',    'descripcion' => 'Vestidos casuales, midi, de noche y faldas'],
            ['nombre' => 'Chaquetas y Abrigos',  'descripcion' => 'Chaquetas de jean, abrigos de lana, cazadoras y parkas'],
            ['nombre' => 'Calzado',              'descripcion' => 'Zapatillas, botas, sandalias y zapatos para toda ocasión'],
            ['nombre' => 'Ropa Deportiva',       'descripcion' => 'Camisetas deportivas, leggings, shorts y sudaderas'],
            ['nombre' => 'Ropa Niños',           'descripcion' => 'Ropa cómoda y divertida para niños de 2 a 14 años'],
            ['nombre' => 'Accesorios',           'descripcion' => 'Gorras, bufandas, bolsos y complementos de moda'],
        ];
        foreach ($categorias as $cat) {
            $catIds[$cat['nombre']] = DB::table('categoria')->insertGetId(array_merge($cat, ['activo' => true]));
        }

        // ─── 6. CATÁLOGOS ────────────────────────────────────────────────────
        $catalogoIds = [];
        $catalogos = [
            ['nombre' => 'Verano 2025',        'tipo' => 'TEMPORADA', 'descripcion' => 'Colección verano 2025 con telas frescas y colores vibrantes'],
            ['nombre' => 'Invierno 2025',      'tipo' => 'TEMPORADA', 'descripcion' => 'Abrigos, sweaters y ropa de abrigo para el invierno'],
            ['nombre' => 'Nueva Colección 2026','tipo' => 'NUEVO',    'descripcion' => 'Lo último en moda para el año 2026'],
            ['nombre' => 'Outlet - Ofertas',   'tipo' => 'PROMOCION','descripcion' => 'Productos con descuento de temporadas anteriores'],
        ];
        foreach ($catalogos as $c) {
            $catalogoIds[$c['nombre']] = DB::table('catalogo')->insertGetId(array_merge($c, ['activo' => true]));
        }

        // ─── 7. PRODUCTOS ────────────────────────────────────────────────────
        // imagen_url: fotos reales de moda en Unsplash (accesibles públicamente)
        $productosDef = [
            // CAMISETAS Y POLOS
            [
                'nombre'           => 'Camiseta Básica Blanca',
                'categoria'        => 'Camisetas y Polos',
                'descripcion'      => 'Camiseta de algodón 100% peinado, corte recto, cuello redondo. Ideal para el día a día.',
                'precio_unitario'  => 89.00,
                'talla'            => 'M',
                'destacado'        => true,
                'es_nueva_coleccion' => false,
                'tipo_talla'       => 'adulto',
                'imagen_url'       => 'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?w=500&h=600&fit=crop',
                'imagenes_extra'   => [
                    'https://images.unsplash.com/photo-1503341504253-dff4815485f1?w=500&h=600&fit=crop',
                    'https://images.unsplash.com/photo-1562157873-818bc0726f68?w=500&h=600&fit=crop',
                ],
                'catalogos'        => ['Verano 2025', 'Outlet - Ofertas'],
            ],
            [
                'nombre'           => 'Polo Clásico Hombre Azul',
                'categoria'        => 'Camisetas y Polos',
                'descripcion'      => 'Polo de piqué con cuello y botones, manga corta. Elegante y versátil para oficina o casual.',
                'precio_unitario'  => 120.00,
                'talla'            => 'L',
                'destacado'        => true,
                'es_nueva_coleccion' => false,
                'tipo_talla'       => 'adulto',
                'imagen_url'       => 'https://images.unsplash.com/photo-1571945153237-4929e783af4a?w=500&h=600&fit=crop',
                'imagenes_extra'   => [
                    'https://images.unsplash.com/photo-1598033129183-c4f50c736f10?w=500&h=600&fit=crop',
                ],
                'catalogos'        => ['Verano 2025'],
            ],
            [
                'nombre'           => 'Blusa Floral Mujer',
                'categoria'        => 'Camisetas y Polos',
                'descripcion'      => 'Blusa liviana con estampado floral, escote en V y mangas cortas con volado. Perfecta para verano.',
                'precio_unitario'  => 145.00,
                'talla'            => 'S',
                'destacado'        => true,
                'es_nueva_coleccion' => true,
                'tipo_talla'       => 'adulto',
                'imagen_url'       => 'https://images.unsplash.com/photo-1594938298603-5ba0e25dba85?w=500&h=600&fit=crop',
                'imagenes_extra'   => [
                    'https://images.unsplash.com/photo-1554568218-0f1715e72254?w=500&h=600&fit=crop',
                ],
                'catalogos'        => ['Verano 2025', 'Nueva Colección 2026'],
            ],
            [
                'nombre'           => 'Camiseta Oversize Negra',
                'categoria'        => 'Camisetas y Polos',
                'descripcion'      => 'Camiseta oversize de algodón pesado, corte urbano. Combina con todo.',
                'precio_unitario'  => 99.00,
                'talla'            => 'M',
                'destacado'        => false,
                'es_nueva_coleccion' => true,
                'tipo_talla'       => 'adulto',
                'imagen_url'       => 'https://images.unsplash.com/photo-1583743814966-8d4d0ec3de43?w=500&h=600&fit=crop',
                'imagenes_extra'   => [],
                'catalogos'        => ['Nueva Colección 2026'],
            ],
            [
                'nombre'           => 'Polo Rayado Hombre Verde',
                'categoria'        => 'Camisetas y Polos',
                'descripcion'      => 'Polo casual a rayas horizontales, tela jersey suave, corte slim fit.',
                'precio_unitario'  => 135.00,
                'talla'            => 'L',
                'destacado'        => false,
                'es_nueva_coleccion' => false,
                'tipo_talla'       => 'adulto',
                'imagen_url'       => 'https://images.unsplash.com/photo-1618354691373-d851c5c3a990?w=500&h=600&fit=crop',
                'imagenes_extra'   => [],
                'catalogos'        => ['Outlet - Ofertas'],
            ],
            // PANTALONES Y JEANS
            [
                'nombre'           => 'Jeans Slim Fit Azul',
                'categoria'        => 'Pantalones y Jeans',
                'descripcion'      => 'Jeans de denim rígido 98% algodón, corte slim fit, lavado clásico. 5 bolsillos.',
                'precio_unitario'  => 250.00,
                'talla'            => '32',
                'destacado'        => true,
                'es_nueva_coleccion' => false,
                'tipo_talla'       => 'pantalon',
                'imagen_url'       => 'https://images.unsplash.com/photo-1542272604-787c3835535d?w=500&h=600&fit=crop',
                'imagenes_extra'   => [
                    'https://images.unsplash.com/photo-1473966968600-fa801b869a1a?w=500&h=600&fit=crop',
                ],
                'catalogos'        => ['Verano 2025', 'Outlet - Ofertas'],
            ],
            [
                'nombre'           => 'Pantalón Chino Beige',
                'categoria'        => 'Pantalones y Jeans',
                'descripcion'      => 'Pantalón chino de gabardina elástica, corte recto, cintura ajustable. Ideal para oficina.',
                'precio_unitario'  => 220.00,
                'talla'            => '34',
                'destacado'        => false,
                'es_nueva_coleccion' => false,
                'tipo_talla'       => 'pantalon',
                'imagen_url'       => 'https://images.unsplash.com/photo-1490481651871-ab68de25d43d?w=500&h=600&fit=crop',
                'imagenes_extra'   => [],
                'catalogos'        => ['Verano 2025'],
            ],
            [
                'nombre'           => 'Jeans Mom Mujer Azul Claro',
                'categoria'        => 'Pantalones y Jeans',
                'descripcion'      => 'Jeans estilo mom de tiro alto, lavado acid, con acabados rotos en rodillas. Muy de moda.',
                'precio_unitario'  => 280.00,
                'talla'            => '30',
                'destacado'        => true,
                'es_nueva_coleccion' => true,
                'tipo_talla'       => 'pantalon',
                'imagen_url'       => 'https://images.unsplash.com/photo-1541099649105-f69ad21f3246?w=500&h=600&fit=crop',
                'imagenes_extra'   => [
                    'https://images.unsplash.com/photo-1598554747436-c9293d6a588f?w=500&h=600&fit=crop',
                ],
                'catalogos'        => ['Nueva Colección 2026'],
            ],
            [
                'nombre'           => 'Pantalón Palazzo Negro',
                'categoria'        => 'Pantalones y Jeans',
                'descripcion'      => 'Pantalón palazzo de tela fluida, pierna amplia y cintura elástica. Elegante y cómodo.',
                'precio_unitario'  => 195.00,
                'talla'            => 'M',
                'destacado'        => false,
                'es_nueva_coleccion' => true,
                'tipo_talla'       => 'adulto',
                'imagen_url'       => 'https://images.unsplash.com/photo-1624378439575-d8705ad7ae80?w=500&h=600&fit=crop',
                'imagenes_extra'   => [],
                'catalogos'        => ['Nueva Colección 2026'],
            ],
            [
                'nombre'           => 'Jeans Skinny Oscuro Mujer',
                'categoria'        => 'Pantalones y Jeans',
                'descripcion'      => 'Jeans skinny de denim con elastano, lavado oscuro efecto desgastado. Corte ceñido moderno.',
                'precio_unitario'  => 265.00,
                'talla'            => '28',
                'destacado'        => false,
                'es_nueva_coleccion' => false,
                'tipo_talla'       => 'pantalon',
                'imagen_url'       => 'https://images.unsplash.com/photo-1555689502-c4b22b2af5e0?w=500&h=600&fit=crop',
                'imagenes_extra'   => [],
                'catalogos'        => ['Outlet - Ofertas'],
            ],
            // VESTIDOS Y FALDAS
            [
                'nombre'           => 'Vestido Floral Verano',
                'categoria'        => 'Vestidos y Faldas',
                'descripcion'      => 'Vestido midi con estampado floral sobre fondo blanco, tela viscosa fresca, tirantes finos.',
                'precio_unitario'  => 320.00,
                'talla'            => 'S',
                'destacado'        => true,
                'es_nueva_coleccion' => true,
                'tipo_talla'       => 'adulto',
                'imagen_url'       => 'https://images.unsplash.com/photo-1515372039744-b8f02a3ae446?w=500&h=600&fit=crop',
                'imagenes_extra'   => [
                    'https://images.unsplash.com/photo-1572804013309-59a88b7e92d1?w=500&h=600&fit=crop',
                ],
                'catalogos'        => ['Verano 2025', 'Nueva Colección 2026'],
            ],
            [
                'nombre'           => 'Vestido Midi Elegante Negro',
                'categoria'        => 'Vestidos y Faldas',
                'descripcion'      => 'Vestido midi de crepé, sin mangas, escote en V, con abertura lateral. Perfecto para eventos.',
                'precio_unitario'  => 450.00,
                'talla'            => 'M',
                'destacado'        => true,
                'es_nueva_coleccion' => false,
                'tipo_talla'       => 'adulto',
                'imagen_url'       => 'https://images.unsplash.com/photo-1539008835657-9e8e9680c956?w=500&h=600&fit=crop',
                'imagenes_extra'   => [
                    'https://images.unsplash.com/photo-1496747488130-0bce4b5e8a1a?w=500&h=600&fit=crop',
                ],
                'catalogos'        => ['Nueva Colección 2026'],
            ],
            [
                'nombre'           => 'Falda A-Line Plisada Rosa',
                'categoria'        => 'Vestidos y Faldas',
                'descripcion'      => 'Falda plisada de tul con forro interior, cintura elástica. Corte A-line por encima de la rodilla.',
                'precio_unitario'  => 185.00,
                'talla'            => 'S',
                'destacado'        => false,
                'es_nueva_coleccion' => true,
                'tipo_talla'       => 'adulto',
                'imagen_url'       => 'https://images.unsplash.com/photo-1583496661160-fb5974ca31c4?w=500&h=600&fit=crop',
                'imagenes_extra'   => [],
                'catalogos'        => ['Nueva Colección 2026'],
            ],
            [
                'nombre'           => 'Vestido Casual Lino Crema',
                'categoria'        => 'Vestidos y Faldas',
                'descripcion'      => 'Vestido corto de lino natural, cuello redondo, mangas cortas, bolsillos laterales. Fresco y cómodo.',
                'precio_unitario'  => 295.00,
                'talla'            => 'M',
                'destacado'        => false,
                'es_nueva_coleccion' => false,
                'tipo_talla'       => 'adulto',
                'imagen_url'       => 'https://images.unsplash.com/photo-1585386959984-a4155224a1ad?w=500&h=600&fit=crop',
                'imagenes_extra'   => [],
                'catalogos'        => ['Verano 2025'],
            ],
            [
                'nombre'           => 'Mini Falda Denim Azul',
                'categoria'        => 'Vestidos y Faldas',
                'descripcion'      => 'Mini falda de denim con botones frontales y cintura alta. Tendencia retro actualizada.',
                'precio_unitario'  => 175.00,
                'talla'            => 'S',
                'destacado'        => false,
                'es_nueva_coleccion' => false,
                'tipo_talla'       => 'adulto',
                'imagen_url'       => 'https://images.unsplash.com/photo-1518895949257-7621c3c786d7?w=500&h=600&fit=crop',
                'imagenes_extra'   => [],
                'catalogos'        => ['Outlet - Ofertas'],
            ],
            // CHAQUETAS Y ABRIGOS
            [
                'nombre'           => 'Chaqueta Jean Hombre',
                'categoria'        => 'Chaquetas y Abrigos',
                'descripcion'      => 'Chaqueta de jean 100% algodón, corte clásico, lavado medio. Versátil para todo el año.',
                'precio_unitario'  => 350.00,
                'talla'            => 'L',
                'destacado'        => true,
                'es_nueva_coleccion' => false,
                'tipo_talla'       => 'adulto',
                'imagen_url'       => 'https://images.unsplash.com/photo-1578932750294-f5075e85f44a?w=500&h=600&fit=crop',
                'imagenes_extra'   => [
                    'https://images.unsplash.com/photo-1551537482-f2075a1d41f2?w=500&h=600&fit=crop',
                ],
                'catalogos'        => ['Invierno 2025'],
            ],
            [
                'nombre'           => 'Abrigo Lana Mujer Camel',
                'categoria'        => 'Chaquetas y Abrigos',
                'descripcion'      => 'Abrigo largo de mezcla de lana, solapa amplia, forro interior satinado. Elegante y cálido.',
                'precio_unitario'  => 580.00,
                'talla'            => 'M',
                'destacado'        => true,
                'es_nueva_coleccion' => false,
                'tipo_talla'       => 'adulto',
                'imagen_url'       => 'https://images.unsplash.com/photo-1548624149-f6e4b0300a72?w=500&h=600&fit=crop',
                'imagenes_extra'   => [
                    'https://images.unsplash.com/photo-1539533018257-f974c0967d75?w=500&h=600&fit=crop',
                ],
                'catalogos'        => ['Invierno 2025'],
            ],
            [
                'nombre'           => 'Cazadora Cuero Marrón',
                'categoria'        => 'Chaquetas y Abrigos',
                'descripcion'      => 'Cazadora de cuero sintético color marrón, cremallera frontal y laterales, bolsillos con solapa.',
                'precio_unitario'  => 520.00,
                'talla'            => 'L',
                'destacado'        => false,
                'es_nueva_coleccion' => true,
                'tipo_talla'       => 'adulto',
                'imagen_url'       => 'https://images.unsplash.com/photo-1551028719-00167b16eac5?w=500&h=600&fit=crop',
                'imagenes_extra'   => [],
                'catalogos'        => ['Invierno 2025', 'Nueva Colección 2026'],
            ],
            [
                'nombre'           => 'Parka Invierno Verde Militar',
                'categoria'        => 'Chaquetas y Abrigos',
                'descripcion'      => 'Parka con capucha desmontable, relleno de plumón sintético, bolsillos cargo laterales. Muy abrigada.',
                'precio_unitario'  => 680.00,
                'talla'            => 'M',
                'destacado'        => false,
                'es_nueva_coleccion' => false,
                'tipo_talla'       => 'adulto',
                'imagen_url'       => 'https://images.unsplash.com/photo-1553754538-466add009c05?w=500&h=600&fit=crop',
                'imagenes_extra'   => [],
                'catalogos'        => ['Invierno 2025'],
            ],
            [
                'nombre'           => 'Cardigan Tejido Beige',
                'categoria'        => 'Chaquetas y Abrigos',
                'descripcion'      => 'Cardigan de punto grueso con botones de madera, bolsillos frontales. Suave al tacto, muy cómodo.',
                'precio_unitario'  => 280.00,
                'talla'            => 'M',
                'destacado'        => false,
                'es_nueva_coleccion' => true,
                'tipo_talla'       => 'adulto',
                'imagen_url'       => 'https://images.unsplash.com/photo-1576566588028-4147f3842f27?w=500&h=600&fit=crop',
                'imagenes_extra'   => [],
                'catalogos'        => ['Invierno 2025', 'Nueva Colección 2026'],
            ],
            // CALZADO
            [
                'nombre'           => 'Zapatillas Blancas Clásicas',
                'categoria'        => 'Calzado',
                'descripcion'      => 'Zapatillas blancas de cuero sintético, suela de goma vulcanizada. El básico que nunca pasa de moda.',
                'precio_unitario'  => 420.00,
                'talla'            => '40',
                'destacado'        => true,
                'es_nueva_coleccion' => false,
                'tipo_talla'       => 'calzado',
                'imagen_url'       => 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=500&h=600&fit=crop',
                'imagenes_extra'   => [
                    'https://images.unsplash.com/photo-1600185365926-3a2ce3cdb9eb?w=500&h=600&fit=crop',
                ],
                'catalogos'        => ['Verano 2025', 'Outlet - Ofertas'],
            ],
            [
                'nombre'           => 'Botas Cuero Marrón',
                'categoria'        => 'Calzado',
                'descripcion'      => 'Botas de cuero genuino hasta el tobillo, suela antideslizante, cordones de cuero. Resistentes y elegantes.',
                'precio_unitario'  => 550.00,
                'talla'            => '39',
                'destacado'        => false,
                'es_nueva_coleccion' => false,
                'tipo_talla'       => 'calzado',
                'imagen_url'       => 'https://images.unsplash.com/photo-1543163521-1bf539c55dd2?w=500&h=600&fit=crop',
                'imagenes_extra'   => [],
                'catalogos'        => ['Invierno 2025'],
            ],
            [
                'nombre'           => 'Sandalias Verano Mujer',
                'categoria'        => 'Calzado',
                'descripcion'      => 'Sandalias planas con tiras de cuero trenzado y planta acolchada. Cómodas para el verano.',
                'precio_unitario'  => 180.00,
                'talla'            => '37',
                'destacado'        => false,
                'es_nueva_coleccion' => false,
                'tipo_talla'       => 'calzado',
                'imagen_url'       => 'https://images.unsplash.com/photo-1512374382149-233c42bd6e35?w=500&h=600&fit=crop',
                'imagenes_extra'   => [],
                'catalogos'        => ['Verano 2025', 'Outlet - Ofertas'],
            ],
            [
                'nombre'           => 'Zapatos Casuales Hombre Café',
                'categoria'        => 'Calzado',
                'descripcion'      => 'Zapatos derby de cuero con detalles brogue, suela de cuero. Elegantes para oficina o reuniones.',
                'precio_unitario'  => 380.00,
                'talla'            => '41',
                'destacado'        => false,
                'es_nueva_coleccion' => true,
                'tipo_talla'       => 'calzado',
                'imagen_url'       => 'https://images.unsplash.com/photo-1560769629-975ec94e6a86?w=500&h=600&fit=crop',
                'imagenes_extra'   => [],
                'catalogos'        => ['Nueva Colección 2026'],
            ],
            [
                'nombre'           => 'Zapatillas Running Azul',
                'categoria'        => 'Calzado',
                'descripcion'      => 'Zapatillas de running con tecnología de amortiguación en talón, upper de mesh transpirable.',
                'precio_unitario'  => 480.00,
                'talla'            => '41',
                'destacado'        => true,
                'es_nueva_coleccion' => true,
                'tipo_talla'       => 'calzado',
                'imagen_url'       => 'https://images.unsplash.com/photo-1595950653106-6c9ebd614d3a?w=500&h=600&fit=crop',
                'imagenes_extra'   => [
                    'https://images.unsplash.com/photo-1491553895911-0055eca6402d?w=500&h=600&fit=crop',
                ],
                'catalogos'        => ['Nueva Colección 2026'],
            ],
            // ROPA DEPORTIVA
            [
                'nombre'           => 'Camiseta Deportiva Dry-Fit',
                'categoria'        => 'Ropa Deportiva',
                'descripcion'      => 'Camiseta deportiva de tejido dry-fit que aleja la humedad del cuerpo. Corte ergonómico.',
                'precio_unitario'  => 125.00,
                'talla'            => 'M',
                'destacado'        => false,
                'es_nueva_coleccion' => false,
                'tipo_talla'       => 'adulto',
                'imagen_url'       => 'https://images.unsplash.com/photo-1574180566232-aaad1b5b8450?w=500&h=600&fit=crop',
                'imagenes_extra'   => [],
                'catalogos'        => ['Verano 2025'],
            ],
            [
                'nombre'           => 'Shorts Deportivo Negro',
                'categoria'        => 'Ropa Deportiva',
                'descripcion'      => 'Short deportivo de poliéster con cintura elástica y bolsillos laterales. Para gym, running o fútbol.',
                'precio_unitario'  => 95.00,
                'talla'            => 'M',
                'destacado'        => false,
                'es_nueva_coleccion' => false,
                'tipo_talla'       => 'adulto',
                'imagen_url'       => 'https://images.unsplash.com/photo-1591195853828-11db59a44f43?w=500&h=600&fit=crop',
                'imagenes_extra'   => [],
                'catalogos'        => ['Verano 2025', 'Outlet - Ofertas'],
            ],
            [
                'nombre'           => 'Leggings Deportivos Mujer',
                'categoria'        => 'Ropa Deportiva',
                'descripcion'      => 'Leggings de alto rendimiento con cintura alta y compresión suave. Tela opaca y resistente.',
                'precio_unitario'  => 155.00,
                'talla'            => 'S',
                'destacado'        => false,
                'es_nueva_coleccion' => true,
                'tipo_talla'       => 'adulto',
                'imagen_url'       => 'https://images.unsplash.com/photo-1506629082955-511b1aa562c8?w=500&h=600&fit=crop',
                'imagenes_extra'   => [],
                'catalogos'        => ['Nueva Colección 2026'],
            ],
            [
                'nombre'           => 'Sudadera con Capucha Gris',
                'categoria'        => 'Ropa Deportiva',
                'descripcion'      => 'Hoodie de algodón francés con bolsillo canguro, capucha regulable y puños acanalados.',
                'precio_unitario'  => 285.00,
                'talla'            => 'L',
                'destacado'        => true,
                'es_nueva_coleccion' => false,
                'tipo_talla'       => 'adulto',
                'imagen_url'       => 'https://images.unsplash.com/photo-1556821840-3a63f8a3900c?w=500&h=600&fit=crop',
                'imagenes_extra'   => [
                    'https://images.unsplash.com/photo-1618354691373-d851c5c3a990?w=500&h=600&fit=crop',
                ],
                'catalogos'        => ['Invierno 2025'],
            ],
            [
                'nombre'           => 'Conjunto Deportivo Azul',
                'categoria'        => 'Ropa Deportiva',
                'descripcion'      => 'Set deportivo de 2 piezas (camiseta y pantalón) en tejido de alta elasticidad. Ideal para yoga o pilates.',
                'precio_unitario'  => 340.00,
                'talla'            => 'M',
                'destacado'        => false,
                'es_nueva_coleccion' => true,
                'tipo_talla'       => 'adulto',
                'imagen_url'       => 'https://images.unsplash.com/photo-1571019613454-1cb2f99b2d8b?w=500&h=600&fit=crop',
                'imagenes_extra'   => [],
                'catalogos'        => ['Nueva Colección 2026'],
            ],
            // ROPA NIÑOS
            [
                'nombre'           => 'Camiseta Niño Dinosaurio',
                'categoria'        => 'Ropa Niños',
                'descripcion'      => 'Camiseta infantil de algodón suave con estampado de dinosaurio, colores vivos y lavable a máquina.',
                'precio_unitario'  => 75.00,
                'talla'            => '6',
                'destacado'        => false,
                'es_nueva_coleccion' => true,
                'tipo_talla'       => 'nino',
                'imagen_url'       => 'https://images.unsplash.com/photo-1519238263530-99bdd11df2ea?w=500&h=600&fit=crop',
                'imagenes_extra'   => [],
                'catalogos'        => ['Nueva Colección 2026'],
            ],
            [
                'nombre'           => 'Vestido Niña Flores Rojo',
                'categoria'        => 'Ropa Niños',
                'descripcion'      => 'Vestido con estampado de flores para niña, tela 100% algodón, lazo en la cintura. Cómodo y adorable.',
                'precio_unitario'  => 110.00,
                'talla'            => '8',
                'destacado'        => true,
                'es_nueva_coleccion' => true,
                'tipo_talla'       => 'nino',
                'imagen_url'       => 'https://images.unsplash.com/photo-1617331721458-bd3bd3f9c7f8?w=500&h=600&fit=crop',
                'imagenes_extra'   => [],
                'catalogos'        => ['Nueva Colección 2026'],
            ],
            [
                'nombre'           => 'Jeans Niño Elástico Azul',
                'categoria'        => 'Ropa Niños',
                'descripcion'      => 'Jeans infantil con cintura elástica interior y rodillas reforzadas. Resistente y cómodo para el juego.',
                'precio_unitario'  => 130.00,
                'talla'            => '10',
                'destacado'        => false,
                'es_nueva_coleccion' => false,
                'tipo_talla'       => 'nino',
                'imagen_url'       => 'https://images.unsplash.com/photo-1503944583220-79d4dd712a05?w=500&h=600&fit=crop',
                'imagenes_extra'   => [],
                'catalogos'        => ['Outlet - Ofertas'],
            ],
            [
                'nombre'           => 'Conjunto Pijama Niña Estrellas',
                'categoria'        => 'Ropa Niños',
                'descripcion'      => 'Pijama de 2 piezas (remera y pantalón) con estampado de estrellas, tela interlock suave y cálida.',
                'precio_unitario'  => 120.00,
                'talla'            => '6',
                'destacado'        => false,
                'es_nueva_coleccion' => false,
                'tipo_talla'       => 'nino',
                'imagen_url'       => 'https://images.unsplash.com/photo-1515488042361-ee00e0ddd4e4?w=500&h=600&fit=crop',
                'imagenes_extra'   => [],
                'catalogos'        => ['Outlet - Ofertas'],
            ],
            [
                'nombre'           => 'Zapatillas Niño Velcro Azul',
                'categoria'        => 'Ropa Niños',
                'descripcion'      => 'Zapatillas infantiles con cierre de velcro doble, suela antideslizante y puntera reforzada.',
                'precio_unitario'  => 185.00,
                'talla'            => '33',
                'destacado'        => false,
                'es_nueva_coleccion' => true,
                'tipo_talla'       => 'calzado',
                'imagen_url'       => 'https://images.unsplash.com/photo-1491553895911-0055eca6402d?w=500&h=600&fit=crop',
                'imagenes_extra'   => [],
                'catalogos'        => ['Nueva Colección 2026'],
            ],
        ];

        $productoIds = [];
        foreach ($productosDef as $pd) {
            $catId = $catIds[$pd['categoria']];
            $stockInicial = rand(80, 200);
            $pid = DB::table('producto')->insertGetId([
                'categoria_id'      => $catId,
                'nombre'            => $pd['nombre'],
                'descripcion'       => $pd['descripcion'],
                'precio_unitario'   => $pd['precio_unitario'],
                'talla'             => $pd['talla'],
                'imagen_url'        => $pd['imagen_url'],
                'qr_code'           => null,
                'stock_actual'      => $stockInicial,
                'activo'            => true,
                'destacado'         => $pd['destacado'],
                'es_nueva_coleccion'=> $pd['es_nueva_coleccion'],
            ]);

            // Imagen principal
            DB::table('producto_imagen')->insert([
                'producto_id' => $pid,
                'url'         => $pd['imagen_url'],
                'es_principal'=> true,
                'orden'       => 0,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
            // Imágenes extra
            foreach ($pd['imagenes_extra'] as $i => $url) {
                DB::table('producto_imagen')->insert([
                    'producto_id' => $pid,
                    'url'         => $url,
                    'es_principal'=> false,
                    'orden'       => $i + 1,
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]);
            }

            // Tallas con stock
            $tallasParaProducto = match ($pd['tipo_talla']) {
                'adulto'   => $tallasAdulto,
                'pantalon' => $tallasPantalon,
                'calzado'  => $tallasCalzado,
                'nino'     => $tallasNino,
            };
            $tipoTallaKey = match ($pd['tipo_talla']) {
                'adulto'   => 'ropa_adulto',
                'pantalon' => 'pantalon',
                'calzado'  => 'calzado',
                'nino'     => 'ropa_nino',
            };
            foreach ($tallasParaProducto as $codigo) {
                $clave = "{$codigo}|{$tipoTallaKey}";
                if (!isset($tallas[$clave])) continue;
                $tallaId = $tallas[$clave]->id;
                $stockTalla = match ($codigo) {
                    'M', 'L', '32', '34', '40', '41', '8', '10' => rand(25, 50),
                    'S', 'XL', '30', '36', '38', '39', '6', '12' => rand(15, 30),
                    default => rand(5, 20),
                };
                DB::table('producto_talla')->insert([
                    'producto_id' => $pid,
                    'talla_id'    => $tallaId,
                    'stock'       => $stockTalla,
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]);
            }

            // Catálogos
            foreach ($pd['catalogos'] as $catNombre) {
                if (isset($catalogoIds[$catNombre])) {
                    DB::table('catalogo_producto')->insert([
                        'catalogo_id' => $catalogoIds[$catNombre],
                        'producto_id' => $pid,
                    ]);
                }
            }

            $productoIds[] = $pid;
        }

        // ─── 8. PROMOCIONES ──────────────────────────────────────────────────
        $hoy = Carbon::now();
        $promoData = [
            ['nombre' => 'Rebajas de Verano',     'descripcion' => 'Descuentos especiales en toda la colección de verano 2025', 'descuento' => 20.00, 'inicio' => '2025-11-01', 'fin' => '2025-12-31', 'activo' => false],
            ['nombre' => 'Black Friday 2025',      'descripcion' => 'El evento de descuentos más grande del año', 'descuento' => 30.00, 'inicio' => '2025-11-28', 'fin' => '2025-11-30', 'activo' => false],
            ['nombre' => 'Navidad y Año Nuevo',    'descripcion' => 'Celebra las fiestas con los mejores precios', 'descuento' => 25.00, 'inicio' => '2025-12-15', 'fin' => '2026-01-05', 'activo' => false],
            ['nombre' => 'Liquidación de Invierno','descripcion' => 'Últimas unidades de la colección invierno con descuento', 'descuento' => 35.00, 'inicio' => '2026-03-01', 'fin' => '2026-04-30', 'activo' => false],
            ['nombre' => 'Nueva Colección 2026',   'descripcion' => 'Descuento de lanzamiento en todos los artículos de nueva colección', 'descuento' => 15.00, 'inicio' => '2026-06-01', 'fin' => '2026-07-31', 'activo' => true],
        ];
        $promoIds = [];
        foreach ($promoData as $p) {
            $promoIds[] = DB::table('promocion')->insertGetId([
                'nombre'      => $p['nombre'],
                'descripcion' => $p['descripcion'],
                'descuento'   => $p['descuento'],
                'fecha_inicio' => $p['inicio'],
                'fecha_fin'    => $p['fin'],
                'activo'       => $p['activo'],
            ]);
        }

        // Asignar productos a promociones (algunos productos por promo)
        $promoProductos = [
            $promoIds[0] => array_slice($productoIds, 0, 10),   // Rebajas Verano → primeros 10
            $promoIds[1] => array_slice($productoIds, 0, 20),   // Black Friday → primeros 20
            $promoIds[2] => array_slice($productoIds, 5, 15),   // Navidad → 10 del medio
            $promoIds[3] => array_slice($productoIds, 15, 10),  // Liquidación → 10 invernales
            $promoIds[4] => array_filter($productoIds, fn($id, $i) => $i % 2 === 0, ARRAY_FILTER_USE_BOTH), // NC → pares
        ];
        foreach ($promoProductos as $promoId => $pids) {
            foreach ($pids as $pid) {
                DB::table('producto_promocion')->insert(['promocion_id' => $promoId, 'producto_id' => $pid]);
            }
        }

        // ─── 9. USUARIOS ─────────────────────────────────────────────────────
        $passHash = Hash::make('Password123!');
        $staffIds = [];

        // 3 Administradores
        $admins = [
            ['ci' => '1234567', 'nombre' => 'Carlos',    'apellido' => 'Mendoza',  'email' => 'admin.carlos@tiendaropa.bo'],
            ['ci' => '2345678', 'nombre' => 'Patricia',  'apellido' => 'Salinas',  'email' => 'admin.patricia@tiendaropa.bo'],
            ['ci' => '3456789', 'nombre' => 'Roberto',   'apellido' => 'Torrico',  'email' => 'admin.roberto@tiendaropa.bo'],
        ];
        foreach ($admins as $u) {
            $staffIds[] = DB::table('usuario')->insertGetId([
                'ci'       => $u['ci'],
                'nombre'   => $u['nombre'],
                'apellido' => $u['apellido'],
                'email'    => $u['email'],
                'telefono' => '7' . rand(1000000, 9999999),
                'password' => $passHash,
                'rol'      => 'PROPIETARIO',   // legacy constraint: admin → PROPIETARIO
                'rol_nuevo'=> 'admin',
                'activo'   => true,
                'email_verified_at' => now(),
            ]);
        }

        // 3 Propietarios
        $propietarios = [
            ['ci' => '4567890', 'nombre' => 'Lucía',    'apellido' => 'Vargas',   'email' => 'propietario.lucia@tiendaropa.bo'],
            ['ci' => '5678901', 'nombre' => 'Eduardo',  'apellido' => 'Blanco',   'email' => 'propietario.eduardo@tiendaropa.bo'],
            ['ci' => '6789012', 'nombre' => 'Verónica', 'apellido' => 'Chávez',   'email' => 'propietario.veronica@tiendaropa.bo'],
        ];
        foreach ($propietarios as $u) {
            $staffIds[] = DB::table('usuario')->insertGetId([
                'ci'       => $u['ci'],
                'nombre'   => $u['nombre'],
                'apellido' => $u['apellido'],
                'email'    => $u['email'],
                'telefono' => '7' . rand(1000000, 9999999),
                'password' => $passHash,
                'rol'      => 'PROPIETARIO',
                'rol_nuevo'=> 'propietario',
                'activo'   => true,
                'email_verified_at' => now(),
            ]);
        }

        // 3 Vendedores
        $vendedores = [
            ['ci' => '7890123', 'nombre' => 'Marco',    'apellido' => 'Quispe',   'email' => 'vendedor.marco@tiendaropa.bo'],
            ['ci' => '8901234', 'nombre' => 'Daniela',  'apellido' => 'Mamani',   'email' => 'vendedor.daniela@tiendaropa.bo'],
            ['ci' => '9012345', 'nombre' => 'Fernando', 'apellido' => 'Condori',  'email' => 'vendedor.fernando@tiendaropa.bo'],
        ];
        $vendedorIds = [];
        foreach ($vendedores as $u) {
            $id = DB::table('usuario')->insertGetId([
                'ci'       => $u['ci'],
                'nombre'   => $u['nombre'],
                'apellido' => $u['apellido'],
                'email'    => $u['email'],
                'telefono' => '7' . rand(1000000, 9999999),
                'password' => $passHash,
                'rol'      => 'VENDEDOR',
                'rol_nuevo'=> 'vendedor',
                'activo'   => true,
                'email_verified_at' => now(),
            ]);
            $staffIds[] = $id;
            $vendedorIds[] = $id;
        }

        // 30 Clientes bolivianos
        $clientesData = [
            ['ci' => '10111213', 'nombre' => 'María',     'apellido' => 'Flores Mamani',     'email' => 'maria.flores@gmail.com'],
            ['ci' => '10121314', 'nombre' => 'Juan',      'apellido' => 'Quispe López',       'email' => 'juan.quispe@hotmail.com'],
            ['ci' => '10131415', 'nombre' => 'Ana',       'apellido' => 'García Condori',     'email' => 'ana.garcia@yahoo.com'],
            ['ci' => '10141516', 'nombre' => 'Luis',      'apellido' => 'Pérez Rojas',        'email' => 'luis.perez@gmail.com'],
            ['ci' => '10151617', 'nombre' => 'Carmen',    'apellido' => 'Vargas Huanca',      'email' => 'carmen.vargas@gmail.com'],
            ['ci' => '10161718', 'nombre' => 'Rodrigo',   'apellido' => 'Torres Cruz',        'email' => 'rodrigo.torres@hotmail.com'],
            ['ci' => '10171819', 'nombre' => 'Sofía',     'apellido' => 'Morales Ponce',      'email' => 'sofia.morales@gmail.com'],
            ['ci' => '10181920', 'nombre' => 'Miguel',    'apellido' => 'Herrera Vega',       'email' => 'miguel.herrera@yahoo.com'],
            ['ci' => '10192021', 'nombre' => 'Paola',     'apellido' => 'Salazar Gómez',      'email' => 'paola.salazar@gmail.com'],
            ['ci' => '10202122', 'nombre' => 'Diego',     'apellido' => 'Chávez Blanco',      'email' => 'diego.chavez@hotmail.com'],
            ['ci' => '10212223', 'nombre' => 'Natalia',   'apellido' => 'Ramos Aliaga',       'email' => 'natalia.ramos@gmail.com'],
            ['ci' => '10222324', 'nombre' => 'Pablo',     'apellido' => 'Cuevas Mendoza',     'email' => 'pablo.cuevas@gmail.com'],
            ['ci' => '10232425', 'nombre' => 'Valeria',   'apellido' => 'Solís Arce',         'email' => 'valeria.solis@yahoo.com'],
            ['ci' => '10242526', 'nombre' => 'Oscar',     'apellido' => 'Apaza Limachi',      'email' => 'oscar.apaza@gmail.com'],
            ['ci' => '10252627', 'nombre' => 'Alejandra', 'apellido' => 'Espinoza Marca',     'email' => 'alejandra.espinoza@hotmail.com'],
            ['ci' => '10262728', 'nombre' => 'Sergio',    'apellido' => 'Callisaya Catari',   'email' => 'sergio.callisaya@gmail.com'],
            ['ci' => '10272829', 'nombre' => 'Gabriela',  'apellido' => 'Cáceres Laime',      'email' => 'gabriela.caceres@gmail.com'],
            ['ci' => '10282930', 'nombre' => 'Antonio',   'apellido' => 'Mamani Yanarico',    'email' => 'antonio.mamani@yahoo.com'],
            ['ci' => '10293031', 'nombre' => 'Claudia',   'apellido' => 'Ibáñez Ticona',      'email' => 'claudia.ibanez@gmail.com'],
            ['ci' => '10303132', 'nombre' => 'Rafael',    'apellido' => 'Sirpa Colque',       'email' => 'rafael.sirpa@hotmail.com'],
            ['ci' => '10313233', 'nombre' => 'Isabel',    'apellido' => 'Quisbert Huallpa',   'email' => 'isabel.quisbert@gmail.com'],
            ['ci' => '10323334', 'nombre' => 'Mauricio',  'apellido' => 'Zenteno Baldivia',   'email' => 'mauricio.zenteno@gmail.com'],
            ['ci' => '10333435', 'nombre' => 'Cecilia',   'apellido' => 'Quiroga Pozo',       'email' => 'cecilia.quiroga@yahoo.com'],
            ['ci' => '10343536', 'nombre' => 'Hugo',      'apellido' => 'Marca Coaquira',     'email' => 'hugo.marca@gmail.com'],
            ['ci' => '10353637', 'nombre' => 'Roxana',    'apellido' => 'Aduviri Copa',       'email' => 'roxana.aduviri@hotmail.com'],
            ['ci' => '10363738', 'nombre' => 'Daniel',    'apellido' => 'Llanque Tito',       'email' => 'daniel.llanque@gmail.com'],
            ['ci' => '10373839', 'nombre' => 'Adriana',   'apellido' => 'Calisaya Aro',       'email' => 'adriana.calisaya@gmail.com'],
            ['ci' => '10383940', 'nombre' => 'Ricardo',   'apellido' => 'Poma Chuquimia',     'email' => 'ricardo.poma@yahoo.com'],
            ['ci' => '10394041', 'nombre' => 'Beatriz',   'apellido' => 'Chura Flores',       'email' => 'beatriz.chura@gmail.com'],
            ['ci' => '10404142', 'nombre' => 'Gonzalo',   'apellido' => 'Alanoca Vargas',     'email' => 'gonzalo.alanoca@hotmail.com'],
        ];
        $clienteIds = [];
        $direcciones = [
            'Av. 6 de Agosto #1234, La Paz',
            'C. Comercio #567, Cochabamba',
            'Av. Blanco Galindo Km 4.5, Cochabamba',
            'C. Junín #890, Santa Cruz',
            'Av. Monseñor Rivero #234, Santa Cruz',
            'Calle Potosí #456, Oruro',
            'Av. Camacho #123, La Paz',
            'C. Sucre #789, Potosí',
            'Av. Bolivia #321, Tarija',
            'C. Hernando Siles #654, La Paz',
        ];
        foreach ($clientesData as $u) {
            $id = DB::table('usuario')->insertGetId([
                'ci'       => $u['ci'],
                'nombre'   => $u['nombre'],
                'apellido' => $u['apellido'],
                'email'    => $u['email'],
                'telefono' => '6' . rand(1000000, 9999999),
                'password' => $passHash,
                'rol'      => 'CLIENTE',
                'rol_nuevo'=> 'cliente',
                'activo'   => true,
                'email_verified_at' => now(),
            ]);
            $clienteIds[] = $id;
        }

        // ─── 10. INVENTARIO INICIAL ───────────────────────────────────────────
        $staffResponsable = $staffIds[0]; // admin principal como responsable de inventario inicial
        $baseDate = Carbon::create(2025, 6, 1);
        foreach ($productoIds as $i => $pid) {
            $precio = $productosDef[$i]['precio_unitario'];
            DB::table('inventario')->insert([
                'producto_id'    => $pid,
                'usuario_id'     => $staffResponsable,
                'tipo'           => 'INGRESO',
                'cantidad'       => rand(100, 200),
                'costo_unitario' => round($precio * 0.55, 2),
                'tecnica'        => 'PEPS',
                'fecha'          => $baseDate->copy()->subDays(rand(5, 30)),
                'observacion'    => 'Stock inicial temporada 2025',
            ]);
        }

        // ─── 11. PEDIDOS, VENTAS Y PAGOS (13 meses) ─────────────────────────
        // Junio 2025 → Junio 2026 con volumen creciente
        $mesesConfig = [
            ['anio' => 2025, 'mes' => 6,  'pedidos' => 22, 'estado_dist' => ['ENTREGADO' => 100]],
            ['anio' => 2025, 'mes' => 7,  'pedidos' => 28, 'estado_dist' => ['ENTREGADO' => 100]],
            ['anio' => 2025, 'mes' => 8,  'pedidos' => 32, 'estado_dist' => ['ENTREGADO' => 100]],
            ['anio' => 2025, 'mes' => 9,  'pedidos' => 35, 'estado_dist' => ['ENTREGADO' => 100]],
            ['anio' => 2025, 'mes' => 10, 'pedidos' => 38, 'estado_dist' => ['ENTREGADO' => 100]],
            ['anio' => 2025, 'mes' => 11, 'pedidos' => 45, 'estado_dist' => ['ENTREGADO' => 90, 'ENVIADO' => 10]], // Rebajas
            ['anio' => 2025, 'mes' => 12, 'pedidos' => 55, 'estado_dist' => ['ENTREGADO' => 90, 'ENVIADO' => 10]], // Navidad
            ['anio' => 2026, 'mes' => 1,  'pedidos' => 30, 'estado_dist' => ['ENTREGADO' => 100]],
            ['anio' => 2026, 'mes' => 2,  'pedidos' => 28, 'estado_dist' => ['ENTREGADO' => 100]],
            ['anio' => 2026, 'mes' => 3,  'pedidos' => 35, 'estado_dist' => ['ENTREGADO' => 95, 'ENVIADO' => 5]],
            ['anio' => 2026, 'mes' => 4,  'pedidos' => 38, 'estado_dist' => ['ENTREGADO' => 95, 'ENVIADO' => 5]],
            ['anio' => 2026, 'mes' => 5,  'pedidos' => 42, 'estado_dist' => ['ENTREGADO' => 90, 'ENVIADO' => 10]],
            ['anio' => 2026, 'mes' => 6,  'pedidos' => 38, 'estado_dist' => ['ENTREGADO' => 40, 'ENVIADO' => 25, 'CONFIRMADO' => 20, 'PENDIENTE' => 15]],
        ];

        // Agregar movimientos de inventario en meses con alta demanda
        $inventarioMeses = [11, 12]; // Noviembre y diciembre (Rebajas y Navidad)
        foreach ($productoIds as $i => $pid) {
            foreach ($inventarioMeses as $mes) {
                $precio = $productosDef[$i]['precio_unitario'];
                DB::table('inventario')->insert([
                    'producto_id'    => $pid,
                    'usuario_id'     => $vendedorIds[array_rand($vendedorIds)],
                    'tipo'           => 'INGRESO',
                    'cantidad'       => rand(30, 80),
                    'costo_unitario' => round($precio * 0.52, 2),
                    'tecnica'        => 'PEPS',
                    'fecha'          => Carbon::create(2025, $mes, rand(1, 10)),
                    'observacion'    => 'Reposición para temporada de ofertas',
                ]);
            }
        }

        foreach ($mesesConfig as $mc) {
            $diasEnMes = Carbon::create($mc['anio'], $mc['mes'])->daysInMonth;

            // Distribuir pedidos según pesos del mes
            $estados = [];
            foreach ($mc['estado_dist'] as $est => $peso) {
                for ($w = 0; $w < $peso; $w++) {
                    $estados[] = $est;
                }
            }

            for ($n = 0; $n < $mc['pedidos']; $n++) {
                $dia  = rand(1, $diasEnMes);
                $hora = rand(8, 20);
                $min  = rand(0, 59);
                $fechaPedido = Carbon::create($mc['anio'], $mc['mes'], $dia, $hora, $min, 0);

                $estado      = $estados[array_rand($estados)];
                $clienteId   = $clienteIds[array_rand($clienteIds)];
                $direccion   = $direcciones[array_rand($direcciones)];
                $telefono    = '7' . rand(1000000, 9999999);

                $pedidoId = DB::table('pedido')->insertGetId([
                    'usuario_id' => $clienteId,
                    'fecha'      => $fechaPedido,
                    'estado'     => $estado,
                    'direccion'  => $direccion,
                    'telefono'   => $telefono,
                    'referencia' => 'Cerca de la plaza principal',
                    'activo'     => true,
                ]);

                // Detalles: 1 a 3 productos distintos por pedido
                $numDetalles = rand(1, 3);
                $productosElegidos = (array) array_rand(array_flip($productoIds), min($numDetalles, count($productoIds)));
                $totalPedido = 0;
                foreach ($productosElegidos as $prodId) {
                    $idx = array_search($prodId, $productoIds);
                    $precio = $productosDef[$idx]['precio_unitario'];
                    $cantidad = rand(1, 3);
                    DB::table('detalle_pedido')->insert([
                        'pedido_id'       => $pedidoId,
                        'producto_id'     => $prodId,
                        'cantidad'        => $cantidad,
                        'precio_unitario' => $precio,
                    ]);
                    $totalPedido += $cantidad * $precio;
                }

                // Venta y pago para pedidos que no son PENDIENTE
                if ($estado !== 'PENDIENTE') {
                    $ventaId = DB::table('venta')->insertGetId([
                        'pedido_id'  => $pedidoId,
                        'usuario_id' => $clienteId,
                        'fecha'      => $fechaPedido,
                        'total'      => $totalPedido,
                        'activo'     => true,
                    ]);

                    $esCredito = ($totalPedido >= 200 && rand(1, 10) <= 3); // 30% crédito si supera 200 Bs
                    $modalidad = $esCredito ? 'CREDITO' : 'CONTADO';
                    $numCuotas = $esCredito ? rand(2, 3) : 1;

                    $pagoId = DB::table('pago')->insertGetId([
                        'venta_id'    => $ventaId,
                        'modalidad'   => $modalidad,
                        'monto_total' => $totalPedido,
                        'num_cuotas'  => $numCuotas,
                        'fecha_pago'  => $fechaPedido->toDateString(),
                        'activo'      => true,
                        'gateway'     => (rand(1, 2) === 1) ? 'pagofacil' : 'stripe',
                    ]);

                    if ($esCredito) {
                        $montoCuota = round($totalPedido / $numCuotas, 2);
                        for ($c = 1; $c <= $numCuotas; $c++) {
                            $fechaVenc  = $fechaPedido->copy()->addMonths($c)->toDateString();
                            $esPagado   = ($estado === 'ENTREGADO' || $c === 1);
                            $fechaPagoReal = $esPagado ? $fechaPedido->copy()->addMonths($c)->subDays(rand(0, 5))->toDateString() : null;
                            DB::table('cuota')->insert([
                                'pago_id'           => $pagoId,
                                'num_cuota'         => $c,
                                'monto'             => $montoCuota,
                                'fecha_vencimiento' => $fechaVenc,
                                'estado'            => $esPagado ? 'PAGADO' : 'PENDIENTE',
                                'fecha_pago_real'   => $fechaPagoReal,
                            ]);
                        }
                    }
                }
            }
        }

        // ─── 12. VISITAS A PÁGINAS ────────────────────────────────────────────
        $paginas = [
            ['page_url' => '/',                  'page_name' => 'Inicio',             'visitas' => rand(1200, 2500)],
            ['page_url' => '/catalogo',          'page_name' => 'Catálogo',           'visitas' => rand(900, 1800)],
            ['page_url' => '/promociones',       'page_name' => 'Promociones',        'visitas' => rand(600, 1200)],
            ['page_url' => '/carrito',           'page_name' => 'Mi Carrito',         'visitas' => rand(400, 900)],
            ['page_url' => '/favoritos',         'page_name' => 'Favoritos',          'visitas' => rand(300, 700)],
            ['page_url' => '/pedidos/historial', 'page_name' => 'Mis Pedidos',        'visitas' => rand(350, 800)],
            ['page_url' => '/admin/productos',   'page_name' => 'Gestión Productos',  'visitas' => rand(150, 400)],
            ['page_url' => '/admin/pedidos',     'page_name' => 'Gestión Pedidos',    'visitas' => rand(200, 500)],
            ['page_url' => '/admin/reportes',    'page_name' => 'Reportes',           'visitas' => rand(100, 300)],
            ['page_url' => '/admin/estadisticas','page_name' => 'Estadísticas',       'visitas' => rand(80, 250)],
            ['page_url' => '/admin/inventario',  'page_name' => 'Inventario',         'visitas' => rand(120, 350)],
            ['page_url' => '/admin/usuarios',    'page_name' => 'Gestión Usuarios',   'visitas' => rand(90, 220)],
        ];
        foreach ($paginas as $p) {
            DB::table('page_visit')->insert([
                'page_url'       => $p['page_url'],
                'page_name'      => $p['page_name'],
                'visit_count'    => $p['visitas'],
                'last_visited_at'=> now(),
                'created_at'     => Carbon::create(2025, 6, 1),
                'updated_at'     => now(),
            ]);
        }

        $this->command->info('✓ FullDemoSeeder completado: 35 productos, 39 usuarios, 13 meses de pedidos.');
    }
}
