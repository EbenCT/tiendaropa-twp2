# Proyecto Final – Tienda de Ropa (INF-513 Tecnología Web)

**Stack:** Laravel 11 · Inertia.js · Vue 3 · PostgreSQL · CSS Vanilla (3 temas)  
**BD:** `db_grupo21sa` en `db.tecnoweb.org.bo:5432`  
**Deploy:** `https://www.tecnoweb.org.bo/inf513/grupoXXsa/proyecto2`  
**Entrega:** 30 de junio de 2026

---

## Resumen

Sistema de e-commerce de ropa (hombre, mujer, niños) sobre una BD existente del proyecto Java anterior. **No se elimina la BD** — se adapta con migraciones quirúrgicas. Arquitectura Laravel 11 + Inertia + Vue 3, menú y vistas 100% dinámicos por rol, 3 temas visuales diferenciados + día/noche automático con override manual, buscador global siempre visible con filtrado por rol, contador de visitas en tabla propia, estadísticas, reportes y Stripe al final.

---

## Jerarquía de Roles

| Rol | Permisos |
|-----|----------|
| **Administrador** | Control total. Único que puede crear Propietarios. |
| **Propietario** | Todo lo del Admin excepto crear Propietarios. |
| **Vendedor** | Todo lo del Propietario excepto reportes y gestionar Vendedores. |
| **Cliente** | Catálogo público + carrito, favoritos, pedidos propios, historial, pagos. |

> El menú y las opciones visibles en cada vista se generan **dinámicamente** según el rol activo.

---

## Estado Real de la Base de Datos

> [!NOTE]
> La BD fue inspeccionada. Tiene **13 tablas con datos reales** del proyecto Java.  
> **Estrategia: cero eliminaciones, solo migraciones aditivas.**

### Tablas que se reutilizan sin cambios

| Tabla | Filas | Descripción |
|-------|-------|-------------|
| `catalogo` | 3 | Secciones: hombre, mujer, niños |
| `catalogo_producto` | 11 | Pivot catálogo ↔ producto |
| `categoria` | 15 | Subcategorías de productos |
| `detalle_pedido` | 16 | Líneas de cada pedido |
| `inventario` | 12 | Movimientos de stock (entrada/salida, técnica FIFO/Promedio) |
| `pedido` | 6 | Pedidos con dirección, teléfono, estado, referencia |
| `venta` | 4 | Venta vinculada a pedido y usuario |
| `cuota` | 7 | Cuotas de plan de pagos |
| `promocion` | 3 | Promociones con % descuento y fechas |
| `producto_promocion` | 6 | Pivot producto ↔ promoción |

### Tablas que se adaptan (migraciones aditivas)

#### `usuario` → adaptar para Laravel Auth
```
AGREGAR:  email_verified_at (timestamp, nullable)
AGREGAR:  remember_token (varchar 100, nullable)
AGREGAR:  role_id (integer FK → roles)
MANTENER: ci, nombre, apellido, email, telefono, password, activo
DEPRECAR: campo rol (varchar libre) → reemplazado por role_id
```
> Laravel trabajará con esta tabla renombrada a `users` mediante `$table = 'usuario'` en el modelo.

#### `producto` → agregar columnas faltantes
```
AGREGAR:  destacado (boolean, default false)
AGREGAR:  es_nueva_coleccion (boolean, default false)
MANTENER: id, categoria_id, nombre, descripcion, precio_unitario,
          talla(*), imagen_url(*), qr_code, stock_actual, activo
```
> `talla` e `imagen_url` quedan como respaldo; las nuevas tablas normalizadas las complementan.

#### `pago` → agregar columnas Stripe (Fase final)
```
AGREGAR:  stripe_payment_intent_id (varchar, nullable)
AGREGAR:  stripe_status (varchar, nullable)
AGREGAR:  metodo (varchar: tarjeta/transferencia, nullable)
MANTENER: id, venta_id, modalidad, monto_total, num_cuotas, fecha_pago, activo
```

### Tablas nuevas a crear

| Tabla nueva | Propósito |
|-------------|-----------|
| `roles` | 4 roles normalizados: admin, propietario, vendedor, cliente |
| `talla` | Catálogo de tallas (XS, S, M, L, XL, XXL, 6, 8, 10…) |
| `producto_talla` | Pivot producto ↔ talla con `stock` por talla |
| `producto_imagen` | Múltiples imágenes por producto (url, orden, principal) |
| `carrito_item` | Carrito persistente en BD por usuario |
| `favorito` | Productos favoritos por usuario |
| `menu_item` | Menú dinámico: label, route, ícono, rol_minimo, orden, activo |
| `page_visit` | Contador de visitas: page_url, page_name, visit_count, last_visited_at |
| `metodo_pago_usuario` | Métodos de pago guardados (Stripe customer_id, last4, brand) |

---

## Estructura de Tablas Nuevas

### `roles`
```sql
id, slug (admin|propietario|vendedor|cliente), nombre, nivel (int),
created_at, updated_at
```

### `talla`
```sql
id, codigo (S|M|L|XL|6|8|10…), descripcion, tipo (ropa_adulto|ropa_nino|calzado),
created_at, updated_at
```

### `producto_talla` (pivot)
```sql
id, producto_id FK, talla_id FK, stock (int default 0),
created_at, updated_at
```

### `producto_imagen`
```sql
id, producto_id FK, url (varchar 500), es_principal (bool default false),
orden (int default 0), created_at, updated_at
```

### `carrito_item`
```sql
id, usuario_id FK, producto_id FK, talla_id FK (nullable),
cantidad (int), created_at, updated_at
```

### `favorito`
```sql
id, usuario_id FK, producto_id FK,
created_at, updated_at
UNIQUE(usuario_id, producto_id)
```

### `menu_item`
```sql
id, label (varchar), route_name (varchar), icon (varchar nullable),
role_nivel_minimo (int),  -- nivel mínimo de rol para ver este ítem
parent_id (int nullable, FK self), orden (int), activo (bool),
created_at, updated_at
```

### `page_visit`
```sql
id, page_url (varchar 500), page_name (varchar 200),
visit_count (bigint default 0), last_visited_at (timestamp),
created_at, updated_at
UNIQUE(page_url)
```

### `metodo_pago_usuario`
```sql
id, usuario_id FK, stripe_customer_id, stripe_pm_id,
brand (visa|mastercard…), last4 (char 4), es_principal (bool),
activo (bool), created_at, updated_at
```

---

## Estructura de Carpetas

```
proyecto/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Auth/
│   │   │   ├── Public/         ← Catálogo, home, búsqueda pública
│   │   │   ├── Cliente/        ← Carrito, favoritos, pedidos, pagos
│   │   │   ├── Vendedor/       ← Productos, inventario, pedidos
│   │   │   └── Admin/          ← Todo + reportes, menú, usuarios, temas
│   │   ├── Middleware/
│   │   │   ├── CheckRole.php
│   │   │   └── TrackPageVisit.php
│   │   └── Requests/           ← FormRequests, mensajes en español
│   └── Models/
│       ├── User.php            ← tabla 'usuario'
│       ├── Role.php
│       ├── Producto.php
│       ├── Talla.php
│       ├── Categoria.php
│       ├── Catalogo.php
│       ├── Pedido.php
│       ├── DetallePedido.php
│       ├── Venta.php
│       ├── Pago.php
│       ├── Cuota.php
│       ├── Inventario.php
│       ├── Promocion.php
│       ├── CarritoItem.php
│       ├── Favorito.php
│       ├── MenuItem.php
│       ├── PageVisit.php
│       └── MetodoPagoUsuario.php
├── database/
│   ├── migrations/             ← SOLO para tablas nuevas y columnas añadidas
│   └── seeders/
│       ├── RolSeeder.php
│       ├── TallaSeeder.php
│       ├── MenuItemSeeder.php
│       └── PageVisitSeeder.php
├── resources/
│   ├── css/app.css             ← Variables CSS, 3 temas, día/noche, accesibilidad
│   └── js/
│       ├── app.js
│       ├── Layouts/
│       │   └── AppLayout.vue
│       ├── Pages/
│       │   ├── Home/Index.vue
│       │   ├── Catalogo/Index.vue
│       │   ├── Catalogo/Show.vue
│       │   ├── Carrito/Index.vue
│       │   ├── Favoritos/Index.vue
│       │   ├── Pedidos/Create.vue
│       │   ├── Pedidos/Historial.vue
│       │   ├── Pagos/Checkout.vue
│       │   └── Admin/
│       │       ├── Productos/
│       │       ├── Inventario/
│       │       ├── Pedidos/
│       │       ├── Usuarios/
│       │       ├── Menu/
│       │       ├── Estadisticas/
│       │       └── Reportes/
│       └── composables/
│           ├── useTema.js
│           └── useBuscador.js
└── routes/web.php
```

---

## Plan de Ejecución por Fases

### 🔴 FASE 1 – Setup del Proyecto Laravel
- `composer create-project laravel/laravel .` en directorio del proyecto
- Instalar Inertia server-side: `composer require inertiajs/inertia-laravel`
- Instalar Inertia client + Vue 3: `npm install @inertiajs/vue3 vue @vitejs/plugin-vue`
- Configurar `vite.config.js` con plugin Vue
- Configurar `.env` con las credenciales PostgreSQL
- Verificar conexión a `db.tecnoweb.org.bo`

---

### 🔴 FASE 2 – Modelos Eloquent + Migraciones (solo aditivas)

**Migraciones a crear:**
1. `create_roles_table`
2. `add_auth_columns_to_usuario_table` (email_verified_at, remember_token, role_id)
3. `add_destacado_to_producto_table` (destacado, es_nueva_coleccion)
4. `create_talla_table`
5. `create_producto_talla_table`
6. `create_producto_imagen_table`
7. `create_carrito_item_table`
8. `create_favorito_table`
9. `create_menu_item_table`
10. `create_page_visit_table`
11. `create_metodo_pago_usuario_table`
12. `add_stripe_columns_to_pago_table` *(Fase 13, Stripe)*

**Seeders:**
- `RolSeeder` → 4 roles con niveles (admin=4, propietario=3, vendedor=2, cliente=1)
- `TallaSeeder` → tallas adulto (XS-XXL), niño (2-14), calzado
- `MenuItemSeeder` → menú inicial con nivel de acceso por ítem
- Asignar `role_id` a los 15 usuarios existentes en `usuario`

**Modelos Eloquent:** un modelo por cada tabla, con `$table` explícito para las tablas en español.

---

### 🔴 FASE 3 – Autenticación y Roles
- Controladores `Auth/LoginController`, `Auth/RegisterController`
- Usar tabla `usuario` existente (`$table = 'usuario'` en `User.php`)
- Middleware `CheckRole` que verifica `role.nivel >= nivel_requerido`
- Métodos en `User`: `hasRole()`, `canAccess(nivel)`
- Grupos de rutas en `web.php`:
  - Público: home, catálogo, búsqueda
  - `auth + cliente`: carrito, favoritos, pedidos, pagos
  - `auth + vendedor`: gestión productos, inventario, pedidos
  - `auth + propietario`: vendedores + todo lo anterior
  - `auth + admin`: propietarios + reportes + menú + temas

---

### 🔴 FASE 4 – Middleware de Visitas + Inertia Global
- `TrackPageVisit`: hace `upsert` en `page_visit` por cada request GET
- `HandleInertiaRequests` comparte:
  - `auth.user` + rol
  - `menu` → ítems de `menu_item` filtrados por `role_nivel_minimo <= user.role.nivel`
  - `pageVisits` → count de la URL actual
  - `temaActivo` → desde cookie/sesión

---

### 🔴 FASE 5 – Layout Global y Sistema de Temas
- `AppLayout.vue`: header (logo + buscador global + menú dinámico + carrito badge + usuario) + footer (contador de visitas)
- `app.css` con 3 temas aplicados como clases en `<body>`:

| Tema | Paleta | Tipografía | Estilo |
|------|--------|------------|--------|
| `.tema-ninos` | Colores vibrantes (coral, turquesa, amarillo) | Redondeada, grande | Lúdico, ilustrativo |
| `.tema-jovenes` | Oscuro con neón (negro, violeta, cian) | Sans-serif bold | Moderno, urbano |
| `.tema-adultos` | Neutros elegantes (crema, gris, dorado) | Serif refinada | Clásico, premium |

- Variantes `.modo-dia` / `.modo-noche` aplicadas sobre el tema
- `useTema.js`: detecta hora del cliente → aplica día/noche automático, permite override manual → guarda en `localStorage`
- Controles accesibilidad: botones `A-` / `A` / `A+` para tamaño de fuente + toggle de alto contraste

---

### 🟠 FASE 6 – Home y Destacados
- `HomeController`: consulta `producto WHERE destacado = true`, `producto WHERE es_nueva_coleccion = true`, promociones activas
- `Home/Index.vue`: secciones dinámicas con carrusel de destacados, grid de nuevas colecciones, banner de promociones
- Admin puede gestionar qué productos van en cada sección

---

### 🟠 FASE 7 – Catálogo, Filtros y Búsqueda Global
- `CatalogoController`: listado paginado con filtros (catálogo/categoría, talla, rango precio, búsqueda por nombre)
- `Catalogo/Show.vue`: múltiples imágenes desde `producto_imagen`, tallas disponibles desde `producto_talla`, descripción, stock, métricas de ventas (unidades vendidas desde `detalle_pedido`)
- `BuscadorController`: busca en productos, categorías, funcionalidades del sistema → filtra resultados por `role.nivel` del usuario activo
- Buscador siempre en header → dropdown con resultados agrupados por tipo

---

### 🟠 FASE 8 – Carrito de Compras
- `CarritoController`: agregar (con talla), modificar cantidad, eliminar ítem, ver resumen + total
- Carrito en tabla `carrito_item` (persistente entre sesiones)
- `Carrito/Index.vue` con actualización reactiva

---

### 🟠 FASE 9 – Favoritos
- `FavoritoController`: toggle (insert/delete en `favorito`), solo clientes autenticados
- `Favoritos/Index.vue`: grid de productos favoritos con acceso rápido al carrito

---

### 🟠 FASE 10 – Pedidos
- `PedidoController` (cliente): crear pedido desde carrito → tabla `pedido` + `detalle_pedido` + `venta`
- Ingresar dirección, teléfono, referencia
- `PedidoAdminController` (vendedor+): listar todos los pedidos, cambiar estado
- `Pedidos/Create.vue`, `Pedidos/Historial.vue`, `Admin/Pedidos/Index.vue`
- Estados: `PENDIENTE` → `CONFIRMADO` → `ENVIADO` → `ENTREGADO`

---

### 🟠 FASE 11 – Gestión Admin (Productos, Inventario, Menú, Usuarios)
- `ProductoAdminController`: CRUD completo, upload múltiples imágenes a Storage, gestión tallas/stock por talla
- `InventarioController`: registrar movimientos de entrada/salida de stock con técnica (FIFO/Promedio)
- `MenuAdminController`: CRUD de `menu_item` con drag-and-drop de orden
- `UsuarioAdminController`:
  - Admin crea/gestiona Propietarios
  - Propietario crea/gestiona Vendedores
- `DestacadoController`: marcar productos como destacados / nueva colección / en promoción

---

### 🟡 FASE 12 – Estadísticas y Reportes
- `EstadisticaController`: productos más vendidos (sum de `detalle_pedido.cantidad`), visitas por página (desde `page_visit`), pedidos por estado
- `ReporteController` (Admin + Propietario): ventas filtradas por mes, ganancias del período, exportable
- Gráficas con **Chart.js** via npm
- `Admin/Estadisticas/Index.vue`, `Admin/Reportes/Index.vue`

---

### 🟡 FASE 13 – Pagos con Stripe (última)
- `composer require stripe/stripe-php`
- `npm install @stripe/stripe-js`
- `PagoController`: crear Payment Intent, confirmar pago único, crear plan de pagos (tabla `cuota`)
- Agregar columnas Stripe a tabla `pago` (migración aditiva)
- `metodo_pago_usuario`: guardar métodos del cliente (Stripe Customer + PaymentMethod)
- `Pagos/Checkout.vue` con **Stripe Elements**
- Webhooks para confirmar pagos asíncronos
- Pago único y plan de cuotas (ya estructurado en BD con tabla `cuota`)

---

## Validaciones (transversal a todas las fases)

- Backend: **FormRequest** de Laravel con `messages()` en **español**
- Frontend: validación en Vue antes de submit (errores inline en formulario)
- Campos clave: registro, login, productos, pedidos, pagos, búsqueda, carrito

---

## Verificación Final

### Flujos completos a probar
- Registro → login → catálogo → filtros → detalle producto → carrito → pedido → pago Stripe
- Cambio de tema (3 temas) + modo día/noche + override manual + accesibilidad A-/A/A+
- Acceso a rutas protegidas con cada uno de los 4 roles
- Contador de visitas incrementando correctamente en footer
- Buscador global retornando resultados filtrados por rol
- Admin: CRUD productos con imágenes, gestión de menú dinámico, reportes por mes

### Deploy
- `npm run build` para producción
- Configurar URL base para servidor de la cátedra (`APP_URL`)
- Subir a `tecnoweb.org.bo/inf513/grupoXXsa/proyecto2`
- Exportar BD con `pg_dump` para incluir en entrega final (`.tar.gz`)
