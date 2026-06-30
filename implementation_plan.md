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
| 13 | Pagos con Stripe | ✅ Completa (modo TEST) |
| 14 | Pagos con PagoFácil (pasarela boliviana, principal) | ✅ Implementada y probada contra sandbox real |

---

## Fase 13: Pagos con Stripe — completada

Integración Stripe **en modo TEST permanente** (Bolivia no es país soportado por Stripe para
cuentas live/payouts reales — ver `plan_pagos_stripe.md` para el detalle de la investigación).
Cubre pago único, plan de cuotas y métodos de pago guardados, vía una capa de servicios nueva
`App\Services\Stripe\` (primera vez que el proyecto usa este patrón):

- **Migración** `2026_06_18_100012_add_stripe_columns_to_pago_table.php`: agrega
  `stripe_payment_intent_id`, `stripe_status`, `metodo` a `pago` (ALTER aditivo).
- **Modelo `MetodoPagoUsuario`** reescrito (antes era un stub vacío).
- **Servicios**: `PagoUnicoService` (Stripe Checkout hospedado), `CuotasService` (2/3/6 cuotas sin
  interés, mínimo Bs. 50/cuota, cobro off-session de cuotas futuras), `MetodoPagoService`
  (Customer + SetupIntent + tarjetas guardadas), `WebhookHandlerService` (única fuente de verdad
  de éxito/fracaso de pagos).
- **Controladores**: `Cliente/PagoController`, `Cliente/MetodoPagoController`,
  `StripeWebhookController` (ruta pública `/stripe/webhook`, excluida de CSRF).
- **Comando programado** `pagos:cobrar-cuotas` (`app/Console/Commands/CobrarCuotasVencidas.php`),
  registrado en `Kernel::schedule()->daily()`, cobra cuotas vencidas con la tarjeta principal.
- **Frontend**: `Pedidos/Pagar.vue` (toggle pago único/cuotas + QR del Checkout vía librería
  `qrcode`), `MetodosPago/Index.vue` (gestión de tarjetas vía Stripe Elements), secciones de pago
  agregadas en `Pedidos/Show.vue`, `Pedidos/Historial.vue` y `Admin/Pedidos/Show.vue` (solo lectura).
- **`Pedido.estado`** se mantiene en `PENDIENTE` durante el pago, pasa a `CONFIRMADO` solo cuando
  el webhook confirma éxito (`Pedido::confirmarPorPago()`).

**Hallazgos de CHECK constraints aplicados** (ver `db_analysis.md`): `pago.modalidad` solo acepta
`CONTADO`/`CREDITO` (no `unico`/`cuotas`), `pago.venta_id` es UNIQUE (una sola fila `Pago` por
venta, vía `updateOrCreate`), `cuota.estado` solo acepta `PENDIENTE`/`PAGADO` (sin `FALLIDA` — una
cuota fallida queda `PENDIENTE` para reintento automático al día siguiente).

**Estado de credenciales (2026-06-28)**: `STRIPE_KEY`/`STRIPE_SECRET` reales ya están en `.env`
(cuenta Stripe de prueba) y Stripe CLI v1.43.2 ya está instalado. **Falta** correr `stripe login`
+ `stripe listen --forward-to localhost:8000/stripe/webhook` para obtener el `whsec_...` real y
completar `STRIPE_WEBHOOK_SECRET` (sigue con placeholder) — sin esto el webhook rechaza la firma y
ningún pago se refleja como exitoso en la app. Una vez resuelto, queda pendiente la verificación
end-to-end en navegador de los 10 casos de `PR-26` en `plan_de_pruebas.md`.
Ver `plan_pagos_stripe.md` para el detalle completo de la arquitectura y la investigación de
alternativas bolivianas (PagosNet/EBANX, Circle.bo, dLocal, PagoFácil Bolivia) para un eventual
modo producción real.

---

## Fase 14: Pagos con PagoFácil — implementada y probada contra sandbox real

El docente pidió explícitamente integrar **PagoFácil Bolivia** (pasarela local que sí liquida en
BOB, a diferencia de Stripe). Se investigó la documentación provista en `InfoPagoFacil/md/` (API
MasterQR v2: login, generate-qr, query-transaction, callback), se recibieron credenciales de
sandbox y se implementó la integración completa: capa de servicios `App\Services\PagoFacil\`
(`PagoFacilClient`, `QrPagoService`, `CuotasPagoFacilService`, `CallbackHandlerService`),
migraciones aditivas en `pago`/`cuota`, controlador ampliado (`Cliente\PagoController`), callback
público (`PagoFacilCallbackController`), comando programado `pagos:sincronizar-pagofacil`, y
frontend con selector de pasarela (PagoFácil/Stripe) en `Pedidos/Pagar.vue` + componente
`Components/QrPagoFacil.vue`.

Decisiones confirmadas: PagoFácil se integra vía la **API MasterQR** (no el Botón de Pago CheckOut,
doc más vieja), se mantiene **Stripe intacto** (Fase 13) con un selector de pasarela en la UI, y el
plan de cuotas con PagoFácil genera **un QR nuevo por cada cuota** (sin cobro automático, a
diferencia de Stripe, porque PagoFácil no soporta tarjetas guardadas ni off-session).

**Pruebas end-to-end (2026-06-29)** contra el sandbox real de PagoFácil confirmaron: login,
`list-enabled-services` (`paymentMethodId=34` real), generación de QR de pago único a través de
todo el stack (HTTP → controlador → servicio → API real → BD), plan de cuotas (3 cuotas con montos
correctos), simulación de callback (confirma `Pedido` y `Cuota`), consulta manual de estado, y
ejecución limpia del comando programado. Se encontraron y resolvieron dos problemas de entorno no
anticipados en el plan original: falta de certificados SSL en esta instalación de PHP en Windows
(resuelto con un bundle CA dentro del proyecto, sin tocar configuración del sistema) y la exigencia
de PagoFácil de que `callbackUrl` sea un dominio público resoluble (confirmado empíricamente; en
local se usa un placeholder, la fuente de verdad real en desarrollo es la consulta manual + el
comando programado).

**Verificación visual con Playwright** (clic real en navegador, mismo método usado en las sesiones
de testing previas — ver `errores_detectados.md`): selector de pasarela, generación y render del QR,
botón "Ya pagué, verificar", plan de cuotas y botón "Pagar esta cuota" en `Pedidos/Show.vue`, todo
confirmado funcionando. Se encontró y corrigió un bug real (error #19 en `errores_detectados.md`):
`Pedidos/Pagar.vue` compartía una sola variable de estado de QR entre las pestañas "Pago único" y
"Plan de cuotas", mostrando el QR equivocado al cambiar de pestaña.

Ver `plan_pagos_pagofacil.md` para el detalle completo de arquitectura, hallazgos de sandbox y
riesgos pendientes (probar la entrega real del callback requiere un túnel público, no ejecutado por
requerir autorización explícita para exponer el servidor local a internet).

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
