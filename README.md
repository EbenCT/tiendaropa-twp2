# TiendaRopa — Sistema de Gestión (INF-513)

Sistema de e-commerce y gestión interna para tienda de ropa, construido sobre una base de datos heredada del proyecto Java anterior. Arquitectura de tres capas: Laravel 9 + Inertia.js v1 + Vue 3.

---

## Índice

1. [Stack y versiones](#1-stack-y-versiones)
2. [Conexión a la base de datos](#2-conexión-a-la-base-de-datos)
3. [Instalación y arranque](#3-instalación-y-arranque)
4. [Roles del sistema](#4-roles-del-sistema)
5. [Base de datos](#5-base-de-datos)
6. [Arquitectura del proyecto](#6-arquitectura-del-proyecto)
7. [Sistema de temas CSS](#7-sistema-de-temas-css)
8. [Pagos con Stripe](#8-pagos-con-stripe)
9. [Pagos con PagoFácil Bolivia](#9-pagos-con-pagofácil-bolivia)
10. [Estado de fases](#10-estado-de-fases)
11. [Errores detectados y corregidos](#11-errores-detectados-y-corregidos)
12. [Plan de pruebas](#12-plan-de-pruebas)
13. [Guía de pruebas manuales por rol](#13-guía-de-pruebas-manuales-por-rol)
14. [Changelog de mejoras](#14-changelog-de-mejoras)

---

## 1. Stack y versiones

| Componente | Versión |
|---|---|
| PHP | ^8.0 |
| Laravel Framework | ^9.0 |
| inertiajs/inertia-laravel | ^1.3 |
| @inertiajs/vue3 | ^1.3.0 |
| Vue | ^3.5.38 |
| Vite | ^8.0.16 |
| @vitejs/plugin-vue | ^6.0.7 |
| laravel-vite-plugin | ^3.1.0 |
| tightenco/ziggy | ^1.8 |
| stripe/stripe-php | última |
| PostgreSQL (remoto) | db.tecnoweb.org.bo:5432 |
| Font Awesome | 6 Free (CDN) |

---

## 2. Conexión a la base de datos

```
Database: db_grupo21sa
Host:     db.tecnoweb.org.bo
Port:     5432
User:     grupo21sa
Password: grup021grup021*
```

---

## 3. Instalación y arranque

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate

# Migraciones y seeders contra la BD remota
php artisan migrate
php artisan db:seed

# Desarrollo
php artisan serve          # http://127.0.0.1:8000
npm run dev                # Vite HMR

# Producción
npm run build
```

**Variables de entorno requeridas** (además de la BD):
```
STRIPE_KEY=pk_test_...
STRIPE_SECRET=sk_test_...
STRIPE_WEBHOOK_SECRET=whsec_...   ← obtener con: stripe listen --forward-to localhost:8000/stripe/webhook
STRIPE_BOB_USD_RATE=6.96

PAGOFACIL_URL=https://masterqr.pagofacil.com.bo/api/services/v2
PAGOFACIL_TOKEN_SERVICE=...
PAGOFACIL_TOKEN_SECRET=...
PAGOFACIL_PAYMENT_METHOD_ID=34
PAGOFACIL_CURRENCY=2
```

---

## 4. Roles del sistema

| Nivel | Rol | Permisos |
|-------|-----|----------|
| 4 | **Administrador** | Control total. Único que gestiona el menú dinámico. |
| 3 | **Propietario** | Todo excepto menú. Gestiona usuarios, reportes y estadísticas. |
| 2 | **Vendedor** | Gestión de productos, inventario, pedidos. |
| 1 | **Cliente** | Catálogo público, carrito, favoritos, pedidos propios, historial. |
| 0 | **Invitado** | Solo rutas públicas: home, catálogo, búsqueda, login/registro. |

El nivel proviene del accessor `getNivelRolAttribute()` en `User.php` y se comparte a todos los componentes Vue vía `HandleInertiaRequests::share()` como `auth.user.nivel`.

**Nota de constraint heredado:** La columna `usuario.rol` original tiene un CHECK que solo acepta `'PROPIETARIO'`, `'VENDEDOR'`, `'CLIENTE'` (el proyecto Java no tenía admin). El sistema mapea internamente `admin` → `'PROPIETARIO'` en la columna legada vía `rolLegado()`.

---

## 5. Base de datos

> Estrategia: cero eliminaciones, solo migraciones aditivas sobre la BD heredada del proyecto Java.

### 5.1 Tablas originales (13)

| Tabla | Filas aprox. | Estado | Trampas heredadas |
|-------|-------------|--------|-------------------|
| `catalogo` | 3 | Reutilizada | — |
| `catalogo_producto` | 12 | Reutilizada (pivot M:M) | — |
| `categoria` | 36 | Reutilizada | — |
| `cuota` | variable | Reutilizada | `estado` CHECK: solo `'PENDIENTE'`/`'PAGADO'` (sin `'FALLIDA'`) |
| `detalle_pedido` | variable | Reutilizada | **No tiene columna `subtotal`** — calcular siempre como `cantidad * precio_unitario` |
| `inventario` | variable | Reutilizada | `tipo` CHECK: solo `'INGRESO'`/`'SALIDA'`; `tecnica` CHECK: solo `'PEPS'`/`'UEPS'`/`'PROMEDIO'`; sin `created_at` (usa `fecha`) |
| `pago` | variable | Reutilizada (+columnas Stripe+PagoFácil) | `modalidad` CHECK: solo `'CONTADO'`/`'CREDITO'`; `venta_id` UNIQUE (usar `updateOrCreate`) |
| `pedido` | variable | Reutilizada | Sin `created_at`/`updated_at` — usar `fecha`; sin columna `total` |
| `producto` | 21 | Adaptada (+`destacado`, +`es_nueva_coleccion`) | — |
| `producto_promocion` | 6 | Reutilizada | — |
| `promocion` | 3 | Reutilizada | — |
| `usuario` | variable | Adaptada (+`email_verified_at`, +`remember_token`, +`rol_nuevo`) | `rol` CHECK: ver sección 4; sin timestamps; `$table = 'usuario'` (singular) |
| `venta` | variable | Reutilizada | — |

### 5.2 Tablas nuevas (8)

| Tabla | Propósito | Migración |
|---|---|---|
| `roles` | 4 roles normalizados con nivel numérico | `100001_create_roles_table` |
| `talla` | 27 tallas (adulto XS-XXL, niño 2-14, calzado, pantalón 28-38) | `100004_create_talla_table` |
| `producto_talla` | Pivot producto ↔ talla con stock | `100005_create_producto_talla_table` |
| `producto_imagen` | Múltiples imágenes por producto | `100006_create_producto_imagen_table` |
| `carrito_item` | Carrito persistente por usuario | `100007_create_carrito_item_table` |
| `favorito` | Productos favoritos (UNIQUE usuario+producto) | `100008_create_favorito_table` |
| `menu_item` | Menú dinámico con jerarquía padre-hijo y nivel mínimo | `100009_create_menu_item_table` |
| `page_visit` | Contador de visitas por URL (UNIQUE page_url) | `100010_create_page_visit_table` |
| `metodo_pago_usuario` | Métodos de pago Stripe (tarjetas guardadas) | `100011_create_metodo_pago_usuario_table` |

### 5.3 Columnas agregadas por Stripe (Fase 13)

Migración `2026_06_18_100012_add_stripe_columns_to_pago_table.php`:
```
pago.stripe_payment_intent_id  varchar(255) nullable
pago.stripe_status             varchar(50)  nullable
pago.metodo                    varchar(30)  nullable  -- 'tarjeta_unico' / 'tarjeta_cuotas'
```

### 5.4 Columnas agregadas por PagoFácil (Fase 14)

Migraciones `2026_06_29_100013_...` (pago) y `2026_06_29_100014_...` (cuota):
```
pago/cuota.gateway                varchar(20)  nullable  -- 'stripe' / 'pagofacil'
pago/cuota.pagofacil_transaction_id varchar(100) nullable
pago/cuota.pagofacil_status       varchar(60)  nullable
pago/cuota.pagofacil_qr_base64    text         nullable
pago/cuota.pagofacil_expira_en    timestamp    nullable
```

### 5.5 Ítems del menú dinámico (sembrados por `MenuItemSeeder`)

| ID | Label | Ruta | Nivel mín | Parent |
|----|-------|------|-----------|--------|
| 1 | Inicio | `home` | 0 | — |
| 2 | Catálogo | `catalogo` | 0 | — |
| 3 | Promociones | `promociones` | 0 | — |
| 4 | Mi Carrito | `carrito.index` | 1 | — |
| 5 | Favoritos | `favoritos` | 1 | — |
| 6 | Mis Pedidos | `pedidos.historial` | 1 | — |
| 7 | Gestión | — (padre) | 2 | — |
| 8 | Reportes | `admin.reportes` | 3 | — |
| 9 | Sistema | — (padre) | 4 | — |
| 10 | Productos | `admin.productos.index` | 2 | 7 |
| 11 | Inventario | `admin.inventario.index` | 2 | 7 |
| 12 | Pedidos | `admin.pedidos.index` | 2 | 7 |
| 13 | Usuarios | `admin.usuarios.index` | 3 | 7 |
| 14 | Menú Dinámico | `admin.menu.index` | 4 | 9 |
| 15 | Estadísticas | `admin.estadisticas` | 4 | 9 |

### 5.6 Seeders ejecutados

| Seeder | Datos |
|---|---|
| `RolSeeder` | 4 roles: admin(4), propietario(3), vendedor(2), cliente(1) |
| `TallaSeeder` | 27 tallas (adulto, niño, calzado, pantalón 28-38) |
| `MenuItemSeeder` | Ítems con niveles y jerarquía padre-hijo |
| `AsignarRolesUsuariosSeeder` | Asigna `rol_nuevo` a usuarios existentes |
| `HashPasswordsSeeder` | Hashea con bcrypt los passwords en texto plano heredados de Java |
| `ProductoTallaSeeder` | Migra `producto.talla` (texto libre legado) → pivot `producto_talla` |
| `DestacadosSeeder` | Marca productos como `destacado`/`es_nueva_coleccion` |

---

## 6. Arquitectura del proyecto

### 6.1 Layout principal (`AppLayout.vue`)

El layout usa un patrón de altura fija para que **solo el contenido central haga scroll**, manteniendo sidebar y footer siempre visibles:

```
app-shell  →  height: 100vh; overflow: hidden
  topbar   →  height: 60px; flex-shrink: 0
  app-body →  height: calc(100vh - 60px); overflow: hidden
    sidebar    →  height: 100%; overflow-y: auto (scroll propio si es largo)
    app-main   →  flex: 1; overflow: hidden
      flash-container  →  flex-shrink: 0
      page-content     →  flex: 1; overflow-y: auto  ← ÚNICA zona de scroll con la rueda
      footer           →  flex-shrink: 0  (siempre visible)
```

**Sidebar colapsable:**
- Desktop (≥900px): clic en hamburger → colapsa a 60px (solo iconos con tooltips `title`). Estado persiste en `localStorage.sidebarCollapsed`.
- Mobile (<900px): clic → abre/cierra con overlay. CSS overrides `!important` garantizan sidebar completo independientemente del estado colapsado.

**Buscador multifuncional** — filtra resultados por rol:

| Tipo de resultado | Nivel mínimo |
|---|---|
| Productos y categorías | todos (invitados incluidos) |
| Usuarios (clientes) | vendedor (nivel 2) |
| Usuarios (todos los roles) | propietario (nivel 3) |
| Acciones del sistema | según acción (nivel 2–4) |

### 6.2 Controladores (21)

```
app/Http/Controllers/
├── Auth/
│   ├── LoginController.php
│   └── RegisterController.php
├── Cliente/
│   ├── CarritoController.php
│   ├── FavoritoController.php
│   ├── PedidoController.php
│   ├── PagoController.php          ← Stripe + PagoFácil
│   └── MetodoPagoController.php    ← Tarjetas Stripe guardadas
├── Admin/
│   ├── ProductoAdminController.php
│   ├── InventarioController.php
│   ├── PedidoAdminController.php
│   ├── UsuarioAdminController.php
│   ├── MenuAdminController.php
│   ├── DestacadoController.php
│   ├── EstadisticaController.php
│   └── ReporteController.php
├── HomeController.php
├── CatalogoController.php
├── BuscadorController.php
├── StripeWebhookController.php
└── PagoFacilCallbackController.php
```

### 6.3 Modelos Eloquent (20)

`User`, `Role`, `Producto`, `Categoria`, `Catalogo`, `Talla`, `ProductoTalla`, `ProductoImagen`, `CarritoItem`, `Favorito`, `Pedido`, `DetallePedido`, `Venta`, `Pago`, `Cuota`, `Inventario`, `Promocion`, `MenuItem`, `PageVisit`, `MetodoPagoUsuario`

### 6.4 Servicios

```
app/Services/
├── Stripe/
│   ├── PagoUnicoService.php        ← Stripe Checkout hospedado
│   ├── CuotasService.php           ← 2/3/6 cuotas, cobro off-session
│   ├── MetodoPagoService.php       ← Customer + SetupIntent + tarjetas guardadas
│   └── WebhookHandlerService.php   ← Fuente de verdad de éxito/fracaso Stripe
└── PagoFacil/
    ├── PagoFacilClient.php         ← login+caché token, generateQr, queryTransaction
    ├── QrPagoService.php           ← QR de pago único y por cuota
    ├── CuotasPagoFacilService.php  ← Plan de cuotas (QR por cuota)
    └── CallbackHandlerService.php  ← Fuente de verdad de éxito/fracaso PagoFácil
```

### 6.5 Middleware personalizado

| Middleware | Función |
|---|---|
| `CheckRole` | Verifica `rol_nuevo` contra nivel mínimo requerido |
| `TrackPageVisit` | Upsert en `page_visit` por cada request GET |
| `HandleInertiaRequests` | Comparte globalmente: auth, menu, pageVisits, carritoCount, flash, stripe.publishableKey |

### 6.6 Comandos Artisan

| Comando | Descripción | Schedule |
|---|---|---|
| `pagos:cobrar-cuotas` | Cobra cuotas Stripe vencidas off-session | `->daily()` |
| `pagos:sincronizar-pagofacil` | Pre-genera QR de cuotas vencidas + sincroniza estado vía `query-transaction` | `->everyFifteenMinutes()` |

### 6.7 Vistas Vue (24 páginas + 1 layout + 2 componentes + 1 composable)

```
resources/js/
├── app.js                           ← Punto de entrada Inertia + fallback global imágenes rotas
├── Layouts/AppLayout.vue            ← Header + sidebar colapsable + buscador + temas + footer
├── Components/
│   ├── ProductoCard.vue
│   └── QrPagoFacil.vue             ← QR reutilizable con verificación de estado
├── composables/useTema.js           ← Temas, modo día/noche, escala fuente
└── Pages/
    ├── Home/Index.vue
    ├── Auth/Login.vue
    ├── Auth/Register.vue
    ├── Catalogo/Index.vue + Show.vue
    ├── Carrito/Index.vue
    ├── Favoritos/Index.vue
    ├── Pedidos/Create.vue + Historial.vue + Show.vue + Pagar.vue
    ├── MetodosPago/Index.vue
    ├── Admin/Productos/Index.vue + Form.vue
    ├── Admin/Inventario/Index.vue + Create.vue
    ├── Admin/Pedidos/Index.vue + Show.vue
    ├── Admin/Usuarios/Index.vue + Form.vue
    ├── Admin/Menu/Index.vue + Form.vue
    ├── Admin/Estadisticas/Index.vue
    └── Admin/Reportes/Index.vue
```

### 6.8 Rutas (70+)

| Grupo | Middleware | Ejemplos |
|-------|-----------|---------|
| Públicas | ninguno | home, catalogo, buscar |
| Guest | `guest` | login, registro |
| Auth | `auth` | logout, carrito, favoritos, pedidos, pagar, metodos-pago |
| Vendedor+ | `auth, role:vendedor` | admin.productos, admin.inventario, admin.pedidos |
| Propietario+ | `auth, role:propietario` | admin.usuarios, admin.reportes, admin.estadisticas |
| Admin | `auth, role:admin` | admin.menu |
| Públicas (webhooks) | ninguno | stripe/webhook, pagofacil/callback |

---

## 7. Sistema de temas CSS

**3 temas × 2 modos = 6 combinaciones.** Clases aplicadas al elemento `#app-body`.

| Tema | Clase | Paleta | Tipografía |
|------|-------|--------|------------|
| Niños | `.tema-ninos` | Coral `#FF6B6B`, turquesa, amarillo | Nunito |
| Jóvenes | `.tema-jovenes` | Violeta `#7C3AED`, cian, negro | Orbitron + Poppins |
| Adultos | `.tema-adultos` | Carbón `#1C1C1C`, dorado `#C9A84C`, crema | Playfair Display + Poppins |

**`useTema.js` composable:**
- `modo-auto`: detecta día (07:00–19:00) / noche automáticamente según hora del cliente
- Override manual con persistencia en `localStorage`
- Escala de fuente: botones A-/A/A+, rango 0.8×–1.4×, base `14px`

**Regla crítica de visibilidad:** `.topbar .btn-outline` usa color y borde blancos (no `--color-primary`) porque el topbar siempre tiene `--bg-header` oscuro en los 3 temas — sin esto el botón "Ingresar" sería invisible en `tema-adultos.modo-dia` (ambos `#1C1C1C`).

---

## 8. Pagos con Stripe

> Bolivia no está en la lista de países soportados por Stripe para cuentas live/payouts reales. Esta integración es **modo TEST permanente** — correcto para una entrega académica.

### Flujos implementados

- **Pago único**: Stripe Checkout hospedado + código QR (generado con `qrcode`) que apunta al mismo Checkout para pagar desde celular.
- **Plan de cuotas** (2/3/6): mínimo Bs. 50/cuota, cobro off-session con tarjeta guardada, cuota 1 cobrada de inmediato.
- **Métodos de pago guardados**: Stripe Customer + SetupIntent (`usage: 'off_session'`), Stripe Elements en la UI.
- **Webhook** (`/stripe/webhook`): única fuente de verdad. Maneja `checkout.session.completed`, `payment_intent.succeeded/failed`, `setup_intent.succeeded`.

### Constraints heredados que afectan Stripe

- `pago.modalidad`: pago único → `'CONTADO'`, cuotas → `'CREDITO'`
- `pago.venta_id`: UNIQUE → usar `Pago::updateOrCreate(['venta_id' => ...], [...])`
- `cuota.estado`: solo `'PENDIENTE'`/`'PAGADO'` — cuota fallida queda `PENDIENTE` y el comando la reintenta al día siguiente

### Pendiente

- Correr `stripe login` + `stripe listen --forward-to localhost:8000/stripe/webhook` para obtener `STRIPE_WEBHOOK_SECRET` real (actualmente placeholder)
- Sin ese paso, ningún pago se refleja como exitoso en la app

---

## 9. Pagos con PagoFácil Bolivia

> Pasarela principal (requisito del docente). API MasterQR v2. Probado end-to-end contra el sandbox real (2026-06-29).

### Flujos implementados

- **Pago único vía QR**: `POST /generate-qr` → imagen QR + fecha vencimiento. Botón "Ya pagué, verificar" consulta estado manualmente.
- **Plan de cuotas vía QR**: N cuotas independientes, cada una con su propio QR. Sin cobro automático (PagoFácil no tiene tarjetas guardadas). Botón "Pagar esta cuota" en `Pedidos/Show.vue`.
- **Callback** (`/pagofacil/callback`): ruta pública excluida de CSRF. Parsea `PedidoID` (`"P{id}-U"` = pago único, `"P{id}-C{n}"` = cuota n) y confirma el pedido/cuota.
- **Comando programado**: pre-genera QR de cuotas que vencen pronto + sincroniza estado de transacciones pendientes.

### Identificadores de correlación

```
Pago único:  paymentNumber = "P{pedido_id}-U"
Cuota n:     paymentNumber = "P{pedido_id}-C{num_cuota}"
```

El `paymentNumber` se reconstruye determinísticamente — no se persiste como columna propia.

### Hallazgos de sandbox confirmados

- `paymentMethodId = 34` ("QR ATC", BOB): confirmado real vía `/list-enabled-services`
- `Estado = 2` = PAGADO (mismo que el Botón de Pago CheckOut, funcionó correctamente en simulación de callback)
- `callbackUrl` debe ser dominio público resoluble — rechaza `localhost`/IPs privadas con `"Invalid Url Callback"`
- SSL en esta instalación de PHP en Windows: resuelto con `storage/app/cacert.pem` (bundle CA Mozilla) pasado vía `withOptions(['verify' => ...])` en `PagoFacilClient::http()`

### Pendiente

- Probar la entrega real del callback requiere un túnel público (`ngrok` u otro) que exponga el servidor local. No ejecutado: requiere autorización explícita para exponer el servidor a internet. La red de seguridad (botón manual + comando programado) es la fuente de verdad funcional actual.

---

## 10. Estado de fases

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
| 13 | Pagos con Stripe (modo TEST) | ✅ Completa (pendiente webhook secret real) |
| 14 | Pagos con PagoFácil Bolivia | ✅ Implementada y probada contra sandbox real |
| 15 | Mejoras UI/UX (layout fijo, sidebar colapsable, buscador multifuncional, fuente) | ✅ Completa |
| 16 | Carga de archivos de imagen en productos (local disk + URL externa) | ✅ Completa |

**Métricas:**

| Métrica | Valor |
|---------|-------|
| Controladores | 21 |
| Modelos Eloquent | 20 |
| Servicios | 8 (4 Stripe + 4 PagoFácil) |
| Migraciones | 14 |
| Seeders | 7 |
| Comandos Artisan | 2 |
| Vistas Vue | 24 páginas + 1 layout + 2 componentes |
| Composables | 1 (useTema.js) |
| Middleware custom | 3 |
| Rutas | 70+ |
| Temas CSS | 3 × 2 modos = 6 combinaciones |

---

## 11. Errores detectados y corregidos

> Todos verificados con Playwright (Chromium real con clics reales) contra la BD remota.

### Error 1 — Columna `updated_at` inexistente al registrar usuario
- **Estado:** ✅ Corregido
- **Error:** `SQLSTATE[42703]: Undefined column «updated_at» en relación «usuario»`
- **Causa:** La tabla `usuario` heredada de Java no tiene `created_at`/`updated_at`.
- **Fix:** `public $timestamps = false;` en `User.php` (ya estaba en el código).

### Error 2 — Navegación SPA rota (ningún Link de Inertia disparaba)
- **Estado:** ✅ Corregido
- **Causa real:** `menu_item` tenía `route_name='carrito'` (inexistente). Ziggy lanzaba una excepción JS al montar el layout, dejando la SPA en estado roto.
- **Fix:** `MenuItemSeeder` corregido a `route_name='carrito.index'`.

### Error 3 — Seeder de tallas fallaba con ON CONFLICT
- **Estado:** ✅ Corregido
- **Fix:** `TallaSeeder` usa `updateOrInsert()` (sin depender de constraint única). Añadidas tallas de pantalón 28–38.

### Error 4 — Contraseñas en texto plano (usuarios heredados de Java)
- **Estado:** ✅ Corregido
- **Fix:** `HashPasswordsSeeder` registrado en `DatabaseSeeder` y ejecutado contra la BD.

### Error 5 — Redirección a `/home` daba 404
- **Estado:** ✅ Corregido
- **Fix:** `RouteServiceProvider::HOME = '/'`

### Error 6 — Ziggy error: route 'carrito' not in route list
- **Estado:** ✅ Corregido (mismo que Error 2, detallado aquí como causa aislada)

### Error 7 — Selector de tallas no aparecía en detalle de producto
- **Estado:** ✅ Corregido
- **Causa:** `producto_talla` estaba vacío — tallas heredadas nunca migradas del campo libre `producto.talla`.
- **Fix:** `ProductoTallaSeeder` migra `producto.talla` → pivot `producto_talla`.

### Error 8 — Home mostraba secciones vacías (destacados/nueva colección)
- **Estado:** ✅ Corregido
- **Causa:** Ningún producto tenía `destacado=true` ni `es_nueva_coleccion=true`.
- **Fix:** `DestacadosSeeder` registrado en `DatabaseSeeder`.

### Error 9 — "Agregar al carrito" no agregaba nada
- **Estado:** ✅ Corregido
- **Causa:** `Catalogo/Show.vue` usaba `formCarrito.post(url, { data: {...} })` — la opción `data` no existe en `useForm().post()` de Inertia. Payload enviado = `{}`.
- **Fix:** `formCarrito.transform(() => ({ producto_id, talla_id, cantidad })).post(url, ...)`.

### Error 10 — Crear pedido (checkout) rompía con Error 500
- **Estado:** ✅ Corregido
- **Causa:** `PedidoController@store` insertaba `'total' => $total` pero la tabla `pedido` no tiene columna `total`.
- **Fix:** Eliminado de `Pedido::create()` y de `$fillable`.

### Error 11 — Registrar movimiento de inventario fallaba con Error 500
- **Estado:** ✅ Corregido
- **Causa:** `inventario.usuario_id` es `NOT NULL` sin default pero nunca se asignaba.
- **Fix:** `'usuario_id' => $request->user()->id` añadido a `Inventario::create()`.

### Error 12 — Crear usuario con rol "admin" violaba CHECK constraint
- **Estado:** ✅ Corregido
- **Causa:** `usuario.rol` CHECK no permite `'ADMIN'`.
- **Fix:** Método `rolLegado()` mapea `admin` → `'PROPIETARIO'` para la columna heredada.

### Error 13 — Página para crear/editar ítems del menú dinámico no existía
- **Estado:** ✅ Implementado
- **Causa:** `Admin/Menu/Form.vue` no existía — las rutas y controlador existían pero el componente Vue faltaba.
- **Fix:** Creado `Form.vue` + botones en `Index.vue`.

### Error 14 — Imágenes de producto rotas en todo el sitio
- **Estado:** ✅ Mitigado
- **Causa:** URLs heredadas de Java (`first.com.bo`) devuelven 404. El placeholder `/img/placeholder.jpg` tampoco existía.
- **Fix:** Listener global `error` en `app.js` que reemplaza cualquier `<img>` roto por SVG inline data-URI.

### Error 15 — Badge del carrito en header siempre en 0
- **Estado:** ✅ Corregido
- **Causa:** `carritoCount` estaba hardcodeado a `ref(0)` y nunca se actualizaba.
- **Fix:** `HandleInertiaRequests` comparte `carritoCount` (suma de `cantidad` del carrito del usuario). `AppLayout.vue` lo lee como `computed(() => usePage().props.carritoCount ?? 0)`.

### Error 16 — Historial de pedidos, lista admin pedidos e inventario fallaban con Error 500
- **Estado:** ✅ Corregido
- **Error:** `SQLSTATE[42703]: Undefined column «created_at»`
- **Causa:** Tres controladores usaban `orderByDesc('created_at')` sobre tablas sin esa columna.
- **Fix:** `orderByDesc('fecha')` en `Cliente/PedidoController@historial`, `Admin/PedidoAdminController@index`, `Admin/InventarioController@index`.

### Error 17 — Registrar cualquier movimiento de inventario siempre fallaba
- **Estado:** ✅ Corregido
- **Error:** `SQLSTATE[23514]: Check violation: inventario_tipo_check`
- **Causa:** El formulario enviaba `'entrada'`/`'salida'` pero el CHECK solo acepta `'INGRESO'`/`'SALIDA'`. Igualmente `tecnica` enviaba `'FIFO'` pero el CHECK exige `'PEPS'`.
- **Fix:** Método `tipoLegado()` en `InventarioController` mapea los valores. Opciones en `Create.vue` cambiadas a `PEPS`/`UEPS`/`PROMEDIO`.

### Error 18 — Una salida mayor al stock disponible se aceptaba (stock negativo)
- **Estado:** ✅ Corregido
- **Fix:** `InventarioController@store` valida que la cantidad de salida no supere `stock_actual` antes de insertar.

### Error 19 — Plan de cuotas PagoFácil mostraba el QR equivocado al cambiar de pestaña
- **Estado:** ✅ Corregido
- **Causa:** `Pedidos/Pagar.vue` usaba `qrPf` compartido entre "Pago único" y "Plan de cuotas". Generar el QR de pago único y luego cambiar a cuotas mostraba ese mismo QR con texto roto `"QR de la cuota 1 de ."`.
- **Fix:** Estado separado en `qrUnico`/`qrCuotas` y `mensajeEstadoUnico`/`mensajeEstadoCuotas`, con función `verificarEstadoQr(paymentNumber, mensajeRef)` parametrizada.

### Error 20 — SQL error en página de reportes (`detalle_pedido.subtotal` inexistente)
- **Estado:** ✅ Corregido
- **Error:** `SQLSTATE[42703]: Undefined column: detalle_pedido.subtotal`
- **Causa:** `ReporteController.php` usaba `SUM(detalle_pedido.subtotal)` en dos queries. La tabla solo tiene `cantidad` y `precio_unitario`.
- **Fix:** Reemplazado por `SUM(detalle_pedido.cantidad * detalle_pedido.precio_unitario)` en ambas queries.

### Error 21 — Botón "Ingresar" invisible en modo luz (tema adultos)
- **Estado:** ✅ Corregido
- **Causa:** En `tema-adultos.modo-dia`, `--color-primary: #1C1C1C` y `--bg-header: #1C1C1C` son idénticos. El `.btn-outline` era invisible.
- **Fix:** `.topbar .btn-outline { border-color: rgba(255,255,255,0.5); color: white; }` scoped al topbar.

### Error 22 — Reset de escala de fuente no persistía entre navegaciones
- **Estado:** ✅ Corregido
- **Causa:** `resetFuente()` en `useTema.js` actualizaba `fontScale.value = 1` pero nunca llamaba a `localStorage.setItem()`. Al navegar, la escala se re-leía del localStorage con el valor anterior.
- **Fix:** Añadido `localStorage.setItem('fontScale', '1')` dentro de `resetFuente()`.

### Error 23 — `Storage` facade importado pero sin uso — imágenes solo por URL
- **Estado:** ✅ Resuelto (nueva funcionalidad)
- **Causa:** El formulario de productos solo tenía un `<input type="text">` para URL. No había forma de subir un archivo real.
- **Fix:** Implementada carga de archivos en `ProductoAdminController` (store/update) usando `Storage::disk('public')`. Formulario `Form.vue` actualizado con file input + preview + campo URL como alternativa. Symlink `public/storage → storage/app/public` creado con `php artisan storage:link`.

---

## 12. Plan de pruebas

**Total: 208 pruebas + 6 flujos E2E**

> Verificados con Playwright (Chromium real) contra la BD remota.  
> Usuarios de prueba disponibles: `test.admin@local.test`, `test.propietario@local.test`, `test.vendedor@local.test` (password `TestPass123!`), `qa.pagofacil@test.local` (password `Test1234!`).

### PR-01: Servidor y conexión (5 pruebas) — ✅

| ID | Caso | Resultado esperado |
|----|------|--------------------|
| PR-01.1 | `php artisan serve` | Server running en 127.0.0.1:8000 |
| PR-01.2 | `npm run dev` | Vite HMR habilitado |
| PR-01.3 | Home | Página renderiza con hero |
| PR-01.4 | Conexión BD | Al menos 1 promoción activa |
| PR-01.5 | Sin errores JS | DevTools Console limpia |

### PR-02: Autenticación (10 pruebas) — ✅

Login exitoso, fallido, campos vacíos, logout, registro exitoso, email duplicado, acceso a /login estando autenticado. Todo verificado con clic real en Playwright.

### PR-03: Roles y permisos (9 pruebas) — ✅

Cliente bloqueado de `/admin/*`. Vendedor bloqueado de `/admin/usuarios`. Propietario bloqueado de `/admin/menu`. Admin accede a todo. Invitado redirigido a `/login`.

### PR-04: Sistema de temas CSS (10 pruebas) — ✅

3 temas, persistencia en localStorage, variables CSS correctas verificadas con `getComputedStyle`.

### PR-05: Modo día/noche (9 pruebas) — ✅

Detección automática, toggle manual, persistencia, 3 temas × modo-noche verificados con `getPropertyValue('--bg-primary')`.

### PR-06: Accesibilidad / escala de fuente (8 pruebas) — ✅

A+/A-/A, rango 0.8×–1.4×, persistencia en localStorage.

### PR-07: Menú dinámico (8 pruebas) — ✅

Cada rol ve su subconjunto de ítems. Submenús dropdown funcionales. Links navegan correctamente (verificado con clic real, causa raíz ruta Ziggy rota ya corregida).

### PR-08: Home y destacados (8 pruebas) — ✅

Hero, CTAs, productos destacados, nueva colección, promociones activas.

### PR-09: Catálogo y filtros (10 pruebas) — ✅

Filtros por catálogo, categoría, talla, precio, búsqueda, combinaciones, paginación, sin resultados.

### PR-10: Detalle de producto (7 pruebas) — ✅

Información, tallas disponibles, métricas de ventas, agregar al carrito (corregido error #9), favorito, login requerido.

### PR-11: Buscador global (12 pruebas) — ✅

Productos, categorías, acciones del sistema. Filtrado por rol. Debounce 300ms, limpiar, cerrar al clic fuera, navegar a resultado.

### PR-12: Carrito de compras (8 pruebas) — ✅

Agregar, modificar cantidad, eliminar, total correcto, badge en header, persistencia en BD, checkout. Badge corregido (error #15).

### PR-13: Favoritos (5 pruebas) — ✅

Agregar, quitar, agregar al carrito desde favoritos, solo autenticados.

### PR-14: Pedidos (cliente) (7 pruebas) — ✅

Crear pedido (corregido error #10), historial (corregido error #16), detalle, estado inicial PENDIENTE, solo pedidos propios.

### PR-15: Gestión de pedidos admin (6 pruebas) — ✅

Listar (corregido error #16), ver detalle, flujo completo PENDIENTE → CONFIRMADO → ENVIADO → ENTREGADO.

### PR-16: Gestión de productos admin (9 pruebas) — ✅

CRUD completo, asignación de tallas y catálogos, toggle destacado/nueva colección, validaciones.

### PR-17: Inventario (5 pruebas) — ✅

Listar (corregido error #16), registrar entrada/salida (corregido errores #17 y #11), técnica PEPS/UEPS/PROMEDIO, validación de stock (error #18).

### PR-18: Gestión de usuarios admin (6 pruebas) — ✅

CRUD completo, restricción de nivel (propietario no puede crear admin), filtrar por rol.

### PR-19: Menú dinámico admin (6 pruebas) — ✅

Ver árbol, crear/editar/eliminar ítem (corregido error #13), caché invalidado, nivel mínimo funcional.

### PR-20: Estadísticas (5 pruebas) — ✅

Dashboard con top productos, pedidos por estado, páginas visitadas, resumen general.

### PR-21: Reportes (4 pruebas) — ✅

Gráfico barras mensuales, filtro por año, ganancias del periodo.

### PR-22: Contador de visitas (6 pruebas) — ✅

Visible en footer, incrementa por visita, contadores independientes por URL, singular/plural.

### PR-23: Validaciones en español (7 pruebas) — ✅

Todos los formularios con mensajes de error en español. Nota: los mensajes son por controlador (no hay `lang/es/validation.php`), riesgo futuro si se agregan reglas sin mensaje personalizado.

### PR-24: Flujos completos E2E (6 flujos) — ✅ (compuesto de pasos ya verificados)

- CU-01: Flujo completo de compra (invitado → registro → carrito → pedido)
- CU-02: Gestión de pedido por admin (PENDIENTE → ENTREGADO)
- CU-03: Gestión completa de producto (crear → catálogo → destacado → inventario)
- CU-04: Cambio visual completo de temas (tema + modo + escala + persistencia)
- CU-05: Buscador global con diferentes roles
- CU-06: Flujo de favoritos (agregar → carrito → quitar)

### PR-25: Responsive (5 pruebas) — ✅

Header, grid productos, formularios, tablas admin en 375px verificados con Playwright.

### PR-26: Pagos con Stripe (10 pruebas) — ⏸️ Pendiente

Pendiente de `STRIPE_WEBHOOK_SECRET` real. Tarjetas de prueba: `4242 4242 4242 4242` (éxito), `4000 0027 6000 3184` (3DS), `4000 0000 0000 9995` (fondos insuficientes).

### PR-27: Pagos con PagoFácil (14 pruebas) — ✅ (excepto PR-27.12)

| ID | Caso | Estado |
|----|------|--------|
| PR-27.1 | Login sandbox | ✅ |
| PR-27.2 | paymentMethodId=34 confirmado | ✅ |
| PR-27.3 | Generar QR pago único | ✅ |
| PR-27.4 | Consultar estado manual | ✅ |
| PR-27.5 | Plan 3 cuotas con montos correctos | ✅ |
| PR-27.6 | Callback confirma pago único | ✅ |
| PR-27.7 | Callback confirma una cuota | ✅ |
| PR-27.8 | Comando sincronización | ✅ |
| PR-27.9 | callbackUrl inválido rechazado | ✅ |
| PR-27.10 | Selector de pasarela en UI | ✅ (Playwright) |
| PR-27.11 | Botón "Pagar esta cuota" en Show.vue | ✅ (Playwright) |
| PR-27.12 | Callback real desde servidores PagoFácil | ⏸️ (requiere túnel público) |
| PR-27.13 | QR renderizado correctamente | ✅ (Playwright) |
| PR-27.14 | Estado de QR separado entre pestañas | ✅ (bug #19 encontrado y corregido) |

### PR-28: Layout fijo y sidebar colapsable (9 pruebas) — ✅

Solo `page-content` scrollea. Footer siempre visible. Sidebar colapsa a 60px en desktop, tooltips funcionan, estado persiste, mobile siempre full-width, submenús se ocultan al colapsar.

### PR-29: Buscador con usuarios por rol (6 pruebas) — ✅

Invitado/cliente no ven usuarios. Vendedor ve solo clientes. Propietario+ ve todos. Clic en resultado navega a `/admin/usuarios/{id}/edit`. Placeholder "Buscar en el sistema...".

### PR-30: Visibilidad elementos en todos los temas+modos (5 pruebas) — ✅

Botón "Ingresar" visible en los 3 temas × 2 modos. Fuente base 14px. Reset de escala persiste.

---

## 13. Guía de pruebas manuales por rol

> Para probar el sistema completo en el navegador paso a paso.

### Cuentas de prueba

| Rol | Email | Password |
|-----|-------|----------|
| Administrador | `admin@tiendaropa.test` | `Admin123!` |
| Vendedor | `vendedor@tiendaropa.test` | `Vendedor123!` |
| Cliente | `cliente@tiendaropa.test` | `Cliente123!` |

### Antes de empezar

```bash
php artisan serve   # Terminal 1
npm run dev         # Terminal 2 (opcional si ya hiciste npm run build)
```

Abre una ventana incógnito por cada rol para comparar menús lado a lado.

**Nota sobre pagos:**
- **PagoFácil**: el QR es real (sandbox), no se puede pagar con una app bancaria real aquí. "Ya pagué, verificar" mostrará "Estado: En Proceso" — correcto.
- **Stripe**: usar tarjeta `4242 4242 4242 4242`, pero el pedido no pasará a CONFIRMADO sin `stripe listen` activo.

---

### Parte 0 — Como Invitado

- [ ] Probar los 3 botones de tema — paleta y tipografía cambian en toda la página
- [ ] Botón día/noche — fondos cambian
- [ ] Botones A-/A/A+ — tamaño de letra escala
- [ ] Botón hamburger en desktop — sidebar colapsa a solo iconos
- [ ] Buscador del header — dropdown con resultados de productos
- [ ] Catálogo, filtrar por categoría/talla/precio
- [ ] Detalle de producto → "Agregar al Carrito" → redirige a `/login`
- [ ] Intentar entrar a `http://127.0.0.1:8000/admin/productos` → redirige a `/login`
- [ ] Footer muestra "Esta página ha sido visitada N veces"

---

### Parte 1 — Como Cliente

- [ ] Login → menú muestra Mi Carrito, Favoritos, Mis Pedidos — **sin nada de "Gestión"**
- [ ] Catálogo → filtro combinado → detalle de producto → Agregar al Carrito → confirmación
- [ ] Corazón favorito → ❤️ → ir a Favoritos → producto aparece ahí
- [ ] Mi Carrito → cambiar cantidad → verificar total recalculado → "Realizar Pedido"
- [ ] Llenar dirección y teléfono → confirmar → llegar a pantalla de pago

**PagoFácil (pago único):**
- [ ] Pestaña "Pago único" → "Generar QR" → imagen QR real aparece
- [ ] "Ya pagué, verificar" → muestra "Estado: En Proceso"
- [ ] Cambiar a "Plan de cuotas" → elegir 2/3/6 cuotas → "QR de la cuota 1 de N"

**Stripe (tarjeta):**
- [ ] Botón "Tarjeta (Stripe)" → "Pagar ahora" → tarjeta `4242 4242 4242 4242` → completar

- [ ] Mis Pedidos → pedido recién creado con estado PENDIENTE
- [ ] Intentar `/admin/productos` → acceso denegado

---

### Parte 2 — Como Vendedor

- [ ] Login → menú muestra "Gestión" con Productos / Inventario / Pedidos — **sin** Usuarios/Reportes
- [ ] Gestión → Productos → Crear producto → llenar formulario → guardar
- [ ] Editar producto → cambiar precio → guardar
- [ ] Toggle estrella (⭐) → producto se marca como destacado → verificar en Home público
- [ ] Gestión → Inventario → "Crear" → tipo Entrada → cantidad → guardar → stock sube
- [ ] Crear Salida → stock baja
- [ ] Intentar salida mayor al stock (ej. 99999) → error en español
- [ ] Gestión → Pedidos → entrar al pedido del cliente → PENDIENTE → CONFIRMADO → ENVIADO → ENTREGADO
- [ ] Sección "Pago" en detalle del pedido → **visible pero sin botones de acción**
- [ ] Verificar en cuenta cliente que el estado cambió
- [ ] Intentar `/admin/usuarios` → acceso denegado

---

### Parte 3 — Como Admin

- [ ] Login → menú completo: Gestión + Usuarios + Reportes + Sistema (Estadísticas + Menú Dinámico)
- [ ] Usuarios → tabla con todos los usuarios → crear usuario desechable → editar → desactivar
- [ ] El selector de rol al crear/editar **nunca muestra "Admin"** — restricción intencional
- [ ] Estadísticas → Top 10 productos, pedidos por estado, páginas más visitadas
- [ ] Reportes → gráfico mensual → cambiar año → verificar actualización
- [ ] Menú Dinámico → "+ Nuevo Ítem" → crear con nivel 3 → verificar que vendedor (nivel 2) **no** lo ve
- [ ] Eliminar el ítem de prueba
- [ ] En detalle de pedido: sección "Pago" visible en solo lectura, **sin botones**

---

### (Opcional) Simular callback de PagoFácil

Para ver un pago como CONFIRMADO sin pagar de verdad:

```bash
# Reemplazar 15 con el ID real de tu pedido
curl -X POST http://127.0.0.1:8000/pagofacil/callback \
  -H "Content-Type: application/json" \
  -d '{"PedidoID":"P15-U","Fecha":"2026-06-30","Hora":"10:00:00","MetodoPago":4,"Estado":2}'
```

Recargar `/pedidos/15` → estado pasa a CONFIRMADO.  
Para cuota: usar `"PedidoID":"P15-C1"` (cuota 1), `"P15-C2"` (cuota 2), etc.

---

### Checklist de diferencias entre roles

| Funcionalidad | Cliente | Vendedor | Admin |
|---|---|---|---|
| Catálogo, carrito, favoritos, pedidos propios | ✅ | — | — |
| Pagar (PagoFácil/Stripe) | ✅ | — | — |
| Ver/editar **todos** los pedidos | ❌ | ✅ | ✅ |
| CRUD de productos, inventario | ❌ | ✅ | ✅ |
| CRUD de usuarios + asignar roles | ❌ | ❌ | ✅ |
| Reportes y estadísticas | ❌ | ❌ | ✅ |
| Menú dinámico | ❌ | ❌ | ✅ (único) |
| Marcar un pago como "pagado" manualmente | ❌ | ❌ | ❌ (nadie — solo la pasarela real) |

---

## 14. Changelog de mejoras

### 2026-06-30 — Carga de archivos de imagen en productos (Fase 16)

**Archivos modificados:** `ProductoAdminController.php`, `resources/js/Pages/Admin/Productos/Form.vue`

- `php artisan storage:link` ejecutado — `public/storage` apunta a `storage/app/public`
- `store()` y `update()`: nueva validación `imagen` (jpg/png/webp, máx 2 MB); si llega archivo se guarda en `storage/app/public/productos/` y la URL resultante (`/storage/productos/...`) se escribe en `imagen_url`
- `update()`: elimina el archivo local anterior si la imagen previa era de almacenamiento local
- Formulario: preview de imagen actual, botón "Subir archivo" (file input oculto), campo URL externa como alternativa — ambas opciones son mutuamente exclusivas
- **Seeders**: usar `https://picsum.photos/seed/producto{$i}/400/400` directamente en `imagen_url` — no requiere subir archivos

---

### 2026-06-30 — Mejoras UI/UX (Fase 15)

**Correcciones de bugs:**
- **SQL reportes**: `SUM(detalle_pedido.subtotal)` → `SUM(cantidad * precio_unitario)` (columna `subtotal` no existe en la tabla)
- **Botón "Ingresar" invisible en modo luz**: `.topbar .btn-outline` ahora usa color/borde blancos
- **Reset de escala de fuente no persistía**: `resetFuente()` en `useTema.js` ahora guarda en `localStorage`

**Layout fijo (sidebar + footer siempre visibles):**
- `app-shell`: `height: 100vh; overflow: hidden`
- `app-body`: `height: calc(100vh - 60px); overflow: hidden`
- Solo `page-content` hace scroll con la rueda del mouse

**Sidebar colapsable:**
- Desktop: colapsa a 60px (iconos + tooltips), estado persiste en `localStorage.sidebarCollapsed`
- Mobile: abre/cierra con overlay, CSS overrides garantizan sidebar completo

**Buscador multifuncional:**
- Nuevo: búsqueda de usuarios filtrada por rol
- Acciones del sistema filtradas por nivel
- Clientes/invitados nunca ven resultados administrativos

**Tipografía:**
- Fuente base reducida de 16px a 14px

### 2026-06-29 — Fase 14: Pagos con PagoFácil

- Integración completa de PagoFácil Bolivia (API MasterQR v2)
- Pago único QR + plan de cuotas QR
- Callback, comando programado, selector de pasarela en UI
- Bug encontrado y corregido: estado de QR compartido entre pestañas

### 2026-06-28 — Fase 13: Pagos con Stripe

- Pago único (Checkout hospedado + QR)
- Plan de cuotas 2/3/6, cobro off-session
- Métodos de pago guardados (Stripe Elements)
- Webhooks, comando `pagos:cobrar-cuotas`

### 2026-06-26 — Testing con Playwright (sesión 2)

Bugs encontrados y corregidos: payload carrito vacío (#9), columna `total` en pedido (#10), `usuario_id` en inventario (#11), constraint rol admin (#12), `Form.vue` menú faltante (#13), imágenes rotas (#14), badge carrito en 0 (#15), `created_at` inexistente (#16), constraints `tipo`/`tecnica` inventario (#17), stock negativo (#18).

### 2026-06-26 — Testing inicial (sesión 1)

Bugs corregidos: `updated_at` (#1), ruta Ziggy `carrito` (#2 y #6), seeder tallas (#3), passwords planos (#4), redirección HOME (#5), `producto_talla` vacío (#7), destacados vacíos (#8).
