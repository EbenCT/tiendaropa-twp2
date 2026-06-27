# Proyecto Final – Tienda de Ropa (INF-513 Tecnología Web)

**Stack:** Laravel 9 · Inertia.js v1.3 · Vue 3.5 · PostgreSQL · CSS Vanilla (3 temas)  
**BD:** `db_grupo21sa` en `db.tecnoweb.org.bo:5432`  
**Deploy:** `https://www.tecnoweb.org.bo/inf513/grupoXXsa/proyecto2`  
**Entrega:** 30 de junio de 2026

---

## Versiones exactas del stack

| Componente | Versión |
|---|---|
| PHP | ^8.0 |
| Laravel Framework | ^9.0 |
| inertiajs/inertia-laravel (server) | ^1.3 |
| @inertiajs/vue3 (client) | ^1.3.0 |
| Vue | ^3.5.38 |
| Vite | ^8.0.16 |
| @vitejs/plugin-vue | ^6.0.7 |
| laravel-vite-plugin | ^3.1.0 |
| tightenco/ziggy | ^1.8 |
| PostgreSQL (remoto) | db.tecnoweb.org.bo:5432 |

---

## Resumen

Sistema de e-commerce de ropa (hombre, mujer, niños) sobre una BD existente del proyecto Java anterior. **No se elimina la BD** — se adapta con migraciones aditivas. Arquitectura Laravel 9 + Inertia v1 + Vue 3, menú y vistas 100% dinámicos por rol, 3 temas visuales diferenciados + día/noche automático con override manual, buscador global siempre visible con filtrado por rol, contador de visitas en tabla propia, estadísticas y reportes.

---

## Jerarquía de Roles (4 niveles)

| Nivel | Rol | Permisos |
|-------|-----|----------|
| 4 | **Administrador** | Control total. Único que puede gestionar el menú dinámico. |
| 3 | **Propietario** | Todo lo del Admin excepto menú. Gestiona usuarios, reportes y estadísticas. |
| 2 | **Vendedor** | Gestión de productos, inventario, pedidos. |
| 1 | **Cliente** | Catálogo público + carrito, favoritos, pedidos propios, historial. |
| 0 | **Invitado** | Solo rutas públicas: home, catálogo, búsqueda, login/registro. |

> El menú y las opciones visibles en cada vista se generan **dinámicamente** según el nivel de rol activo.

---

## Base de Datos

> **Estrategia: cero eliminaciones, solo migraciones aditivas.**

### Tablas originales (13 tablas del proyecto Java)

| Tabla | Filas | Estado |
|-------|-------|--------|
| `catalogo` | 3 | ✅ Reutilizada |
| `catalogo_producto` | 11 | ✅ Reutilizada |
| `categoria` | 15 | ✅ Reutilizada |
| `cuota` | 7 | ✅ Reutilizada |
| `detalle_pedido` | 16 | ✅ Reutilizada |
| `inventario` | 12 | ✅ Reutilizada |
| `pago` | 4 | ✅ Reutilizada (Stripe pendiente) |
| `pedido` | 6 | ✅ Reutilizada |
| `producto` | 20 | ✅ Adaptada (columnas `destacado`, `es_nueva_coleccion`) |
| `producto_promocion` | 6 | ✅ Reutilizada |
| `promocion` | 3 | ✅ Reutilizada |
| `usuario` | 15 | ✅ Adaptada (columnas auth + `rol_nuevo`) |
| `venta` | 4 | ✅ Reutilizada |

### Tablas nuevas creadas (8 tablas)

| Tabla | Propósito | Migración |
|---|---|---|
| `roles` | 4 roles normalizados con nivel | `100001_create_roles_table` |
| `talla` | Catálogo de tallas (21 registros) | `100004_create_talla_table` |
| `producto_talla` | Pivot producto ↔ talla con stock | `100005_create_producto_talla_table` |
| `producto_imagen` | Múltiples imágenes por producto | `100006_create_producto_imagen_table` |
| `carrito_item` | Carrito persistente por usuario | `100007_create_carrito_item_table` |
| `favorito` | Productos favoritos por usuario | `100008_create_favorito_table` |
| `menu_item` | Menú dinámico filtrado por rol (15 ítems) | `100009_create_menu_item_table` |
| `page_visit` | Contador de visitas por URL | `100010_create_page_visit_table` |
| `metodo_pago_usuario` | Métodos de pago (Stripe, futuro) | `100011_create_metodo_pago_usuario_table` |

### Seeders ejecutados

| Seeder | Datos |
|---|---|
| `RolSeeder` | 4 roles: admin(4), propietario(3), vendedor(2), cliente(1) |
| `TallaSeeder` | 27 tallas (adulto XS-XXL, niño 2-14, calzado, pantalón 28-38) |
| `MenuItemSeeder` | Ítems de menú con niveles y jerarquía padre-hijo |
| `AsignarRolesUsuariosSeeder` | Asigna `rol_nuevo` a los usuarios existentes |
| `HashPasswordsSeeder` | Hashea con bcrypt los passwords en texto plano heredados de Java |
| `ProductoTallaSeeder` | Migra `producto.talla` (texto libre legado) → pivot `producto_talla` |
| `DestacadosSeeder` | Marca productos como `destacado`/`es_nueva_coleccion` |

---

## Estructura del Proyecto

### Backend – Controladores (17 controladores)

```
app/Http/Controllers/
├── Auth/
│   ├── LoginController.php          ← Login + Logout
│   └── RegisterController.php       ← Registro de clientes
├── Cliente/
│   ├── CarritoController.php        ← CRUD carrito (index, agregar, actualizar, eliminar)
│   ├── FavoritoController.php       ← Toggle favoritos + listado
│   └── PedidoController.php         ← Crear pedido, historial, detalle
├── Admin/
│   ├── ProductoAdminController.php  ← CRUD completo productos
│   ├── InventarioController.php     ← Listar + registrar movimientos stock
│   ├── PedidoAdminController.php    ← Listar pedidos + cambiar estado
│   ├── UsuarioAdminController.php   ← CRUD usuarios con restricción por nivel
│   ├── MenuAdminController.php      ← CRUD menú dinámico + cache invalidation
│   ├── DestacadoController.php      ← Toggle destacado/nueva colección
│   ├── EstadisticaController.php    ← Dashboard estadísticas
│   └── ReporteController.php        ← Ventas por mes, ganancias
├── HomeController.php               ← Home: destacados, nueva colección, promociones
├── CatalogoController.php           ← Listado filtrado + detalle producto
├── BuscadorController.php           ← Búsqueda global filtrada por rol
└── Controller.php                   ← Base
```

### Backend – Modelos Eloquent (20 modelos)

```
app/Models/
├── User.php              ← tabla 'usuario', auth Laravel
├── Role.php              ← tabla 'roles'
├── Producto.php          ← con relaciones a categoría, catálogos, tallas, imágenes, promociones
├── Categoria.php
├── Catalogo.php
├── Talla.php
├── ProductoTalla.php
├── ProductoImagen.php
├── CarritoItem.php
├── Favorito.php
├── Pedido.php
├── DetallePedido.php
├── Venta.php
├── Pago.php
├── Cuota.php
├── Inventario.php
├── Promocion.php
├── MenuItem.php          ← con relación self-referencial (children)
├── PageVisit.php
└── MetodoPagoUsuario.php
```

### Backend – Middleware personalizado

| Middleware | Función |
|---|---|
| `CheckRole` | Verifica `rol_nuevo` del usuario contra nivel mínimo requerido |
| `TrackPageVisit` | Upsert en `page_visit` por cada request GET |
| `HandleInertiaRequests` | Comparte datos globales: auth, menu, pageVisits, flash |

### Frontend – Vistas Vue (21 páginas + 1 layout + 1 componente + 1 composable)

```
resources/js/
├── app.js                           ← Punto de entrada Inertia
├── Layouts/
│   └── AppLayout.vue                ← Header + footer + menú dinámico + buscador + temas
├── Components/
│   └── ProductoCard.vue             ← Tarjeta de producto reutilizable
├── composables/
│   └── useTema.js                   ← Gestión de temas, modo día/noche, accesibilidad
└── Pages/
    ├── Home/Index.vue               ← Hero + destacados + nueva colección + promociones
    ├── Auth/Login.vue               ← Formulario login
    ├── Auth/Register.vue            ← Formulario registro
    ├── Catalogo/Index.vue           ← Grid productos + filtros + paginación
    ├── Catalogo/Show.vue            ← Detalle producto + galería + tallas + métricas
    ├── Carrito/Index.vue            ← Ítems + cantidades + subtotales + resumen
    ├── Favoritos/Index.vue          ← Grid favoritos + agregar al carrito
    ├── Pedidos/Create.vue           ← Formulario de envío + resumen
    ├── Pedidos/Historial.vue        ← Lista paginada con estados
    ├── Pedidos/Show.vue             ← Detalle del pedido
    ├── Admin/Productos/Index.vue    ← Tabla + filtros + acciones
    ├── Admin/Productos/Form.vue     ← Crear/editar + tallas + catálogos
    ├── Admin/Inventario/Index.vue   ← Movimientos de stock
    ├── Admin/Inventario/Create.vue  ← Registrar movimiento
    ├── Admin/Pedidos/Index.vue      ← Todos los pedidos + cambio estado inline
    ├── Admin/Pedidos/Show.vue       ← Detalle completo del pedido
    ├── Admin/Usuarios/Index.vue     ← Tabla usuarios + filtros
    ├── Admin/Usuarios/Form.vue      ← Crear/editar con roles
    ├── Admin/Menu/Index.vue         ← Árbol de menú dinámico
    ├── Admin/Estadisticas/Index.vue ← Dashboard con barras CSS
    └── Admin/Reportes/Index.vue     ← Gráfico barras mensuales + resumen anual
```

### Estilos – Sistema de Temas CSS

```
resources/css/app.css  (390 líneas)
```

| Tema | Clase | Paleta | Tipografía |
|------|-------|--------|------------|
| Niños | `.tema-ninos` | Coral, turquesa, amarillo vibrante | Nunito (redondeada) |
| Jóvenes | `.tema-jovenes` | Negro, violeta neón, cian eléctrico | Orbitron + Poppins |
| Adultos | `.tema-adultos` | Crema, gris carbón, dorado | Playfair Display + Poppins |

- Variantes `.modo-dia` / `.modo-noche` por cada tema (6 combinaciones)
- Detección automática por hora del cliente (7:00–19:00 = día)
- Override manual con persistencia en `localStorage`
- Accesibilidad: botones A-/A/A+ para escala de fuente (0.8x–1.4x)

### Rutas (45+ rutas en web.php)

| Grupo | Prefijo | Middleware | Rutas |
|-------|---------|-----------|-------|
| Públicas | `/` | ninguno | home, catalogo, promociones, catalogo/{id}, buscar |
| Auth (guest) | `/` | `guest` | login (GET/POST), registro (GET/POST) |
| Auth (logout) | `/` | `auth` | logout (POST) |
| Cliente | `/carrito`, `/favoritos`, `/pedidos` | `auth` | 9 rutas |
| Vendedor+ | `/admin` | `auth, role:vendedor` | productos (resource), inventario, pedidos admin, destacados |
| Propietario+ | `/admin` | `auth, role:propietario` | usuarios (resource), reportes, estadísticas |
| Admin | `/admin` | `auth, role:admin` | menu (resource) |

---

## Estado de las Fases

| Fase | Descripción | Estado |
|------|-------------|--------|
| 1 | Setup Laravel + Inertia + Vue + PostgreSQL | ✅ Completa |
| 2 | Modelos Eloquent + Migraciones aditivas + Seeders | ✅ Completa |
| 3 | Autenticación y Roles | ✅ Completa |
| 4 | Middleware Visitas + Inertia Global | ✅ Completa |
| 5 | Layout Global y Sistema de Temas | ✅ Completa |
| 6 | Home y Destacados | ✅ Completa |
| 7 | Catálogo, Filtros y Búsqueda Global | ✅ Completa |
| 8 | Carrito de Compras | ✅ Completa |
| 9 | Favoritos | ✅ Completa |
| 10 | Pedidos (cliente + admin) | ✅ Completa |
| 11 | Gestión Admin (productos, inventario, menú, usuarios) | ✅ Completa |
| 12 | Estadísticas y Reportes | ✅ Completa |
| 13 | Pagos con Stripe | ⏸️ Pendiente |

---

## Pendiente – Fase 13: Pagos con Stripe

- Instalar `stripe/stripe-php` + `@stripe/stripe-js`
- `PagoController` (pago único + plan de cuotas)
- Migración aditiva en tabla `pago` (stripe_payment_intent_id, stripe_status, metodo)
- `Pagos/Checkout.vue` con Stripe Elements
- Webhooks Stripe para confirmar pagos asíncronos
- Tabla `metodo_pago_usuario` ya existe en BD (vacía)

---

## Cómo levantar el proyecto

```bash
# 1. Instalar dependencias
composer install
npm install

# 2. Configurar .env (ya configurado con PostgreSQL remoto)
cp .env.example .env
php artisan key:generate

# 3. Ejecutar migraciones y seeders
php artisan migrate
php artisan db:seed

# 4. Levantar servidores de desarrollo
php artisan serve          # → http://127.0.0.1:8000
npm run dev                # → Vite HMR en puerto 5173
```

---

## Deploy en producción

```bash
npm run build              # Genera assets en public/build/
# Configurar APP_URL en .env para tecnoweb.org.bo
# Subir a: https://www.tecnoweb.org.bo/inf513/grupoXXsa/proyecto2
```
