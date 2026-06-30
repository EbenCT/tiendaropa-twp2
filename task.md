# Tareas – Proyecto Final Tienda de Ropa (INF-513)

## FASE 1 – Setup del Proyecto Laravel ✅
- [x] Crear proyecto Laravel 9 (compatible PHP 8.0)
- [x] Instalar Inertia server-side (`inertiajs/inertia-laravel ^1.3`)
- [x] Instalar Inertia client (`@inertiajs/vue3 ^1.3.0`) + Vue 3.5 + plugin Vite
- [x] Configurar `vite.config.js` con plugin Vue + alias `@`
- [x] Configurar `.env` con credenciales PostgreSQL remotas
- [x] Verificar conexión a BD (15 usuarios, 13 tablas originales)

## FASE 2 – Modelos Eloquent + Migraciones aditivas ✅
- [x] Crear tabla `roles` (4 roles con nivel)
- [x] Adaptar tabla `usuario` (`email_verified_at`, `remember_token`, `rol_nuevo`)
- [x] Adaptar tabla `producto` (`destacado`, `es_nueva_coleccion`)
- [x] Crear tabla `talla`
- [x] Crear tabla `producto_talla`
- [x] Crear tabla `producto_imagen`
- [x] Crear tabla `carrito_item`
- [x] Crear tabla `favorito`
- [x] Crear tabla `menu_item`
- [x] Crear tabla `page_visit`
- [x] Crear tabla `metodo_pago_usuario`
- [x] Crear 20 Modelos Eloquent con relaciones
- [x] Seeders: `RolSeeder`, `TallaSeeder`, `MenuItemSeeder`, `AsignarRolesUsuariosSeeder`, `HashPasswordsSeeder`, `ProductoTallaSeeder`, `DestacadosSeeder`

## FASE 3 – Autenticación y Roles ✅
- [x] `LoginController` (login + logout)
- [x] `RegisterController` (registro de clientes)
- [x] Middleware `CheckRole` (verifica `rol_nuevo` vs nivel requerido)
- [x] Grupos de rutas por rol en `web.php` (45+ rutas)
- [x] `Auth/Login.vue`
- [x] `Auth/Register.vue`

## FASE 4 – Middleware Visitas + Inertia Global ✅
- [x] Middleware `TrackPageVisit` (upsert en `page_visit` por GET)
- [x] `HandleInertiaRequests` con datos globales: auth, menu, pageVisits, flash
- [x] Menú dinámico filtrado por `role_nivel_minimo` con caché de 5 minutos

## FASE 5 – Layout Global y Sistema de Temas ✅
- [x] `AppLayout.vue` (header + buscador global + menú dinámico + carrito badge + temas + footer con visitas)
- [x] `app.css` (390 líneas) con 3 temas × 2 modos = 6 combinaciones de variables CSS
- [x] Tema Niños: Nunito, coral/turquesa/amarillo
- [x] Tema Jóvenes: Orbitron+Poppins, violeta/cian/negro
- [x] Tema Adultos: Playfair+Poppins, crema/gris/dorado
- [x] `useTema.js` composable: detección automática día/noche por hora + override manual + localStorage
- [x] Selector de temas en header (3 botones circulares con gradiente)
- [x] Toggle modo día/noche (emoji 🌙/☀️)
- [x] Accesibilidad: botones A-/A/A+ para escala de fuente (0.8x–1.4x)
- [x] `ProductoCard.vue` componente reutilizable

## FASE 6 – Home y Destacados ✅
- [x] `HomeController` (destacados, nueva colección, promociones activas con productos)
- [x] `Home/Index.vue` (hero section + grid destacados + grid nueva colección + promociones con descuento)

## FASE 7 – Catálogo, Filtros y Búsqueda Global ✅
- [x] `CatalogoController` (filtros: catálogo, categoría, talla, precio, búsqueda, paginación)
- [x] `BuscadorController` (búsqueda global → productos, categorías, acciones del sistema filtradas por rol)
- [x] `Catalogo/Index.vue` (grid + filtros laterales + paginación)
- [x] `Catalogo/Show.vue` (galería imágenes, tallas disponibles, agregar al carrito, favoritos, métricas de ventas)
- [x] Buscador siempre visible en header con dropdown de resultados agrupados

## FASE 8 – Carrito de Compras ✅
- [x] `CarritoController` (index, agregar, actualizar cantidad, eliminar)
- [x] `Carrito/Index.vue` (lista items + cantidades + subtotales + resumen + botón checkout)

## FASE 9 – Favoritos ✅
- [x] `FavoritoController` (toggle insert/delete + listado)
- [x] `Favoritos/Index.vue` (grid favoritos + acción agregar al carrito)

## FASE 10 – Pedidos ✅
- [x] `PedidoController` (cliente: create, store, historial, show)
- [x] `PedidoAdminController` (admin: index, show, cambiarEstado)
- [x] `Pedidos/Create.vue` (formulario dirección/teléfono/referencia + resumen desde carrito)
- [x] `Pedidos/Historial.vue` (lista paginada con estados coloreados)
- [x] `Pedidos/Show.vue` (detalle completo del pedido con productos)
- [x] `Admin/Pedidos/Index.vue` (tabla todos los pedidos + cambio estado inline)
- [x] `Admin/Pedidos/Show.vue` (detalle completo con datos cliente y productos)
- [x] Estados: PENDIENTE → CONFIRMADO → ENVIADO → ENTREGADO

## FASE 11 – Gestión Admin ✅
- [x] `ProductoAdminController` (CRUD completo + tallas + catálogos)
- [x] `InventarioController` (listar movimientos + registrar entrada/salida)
- [x] `MenuAdminController` (CRUD + invalidación de caché)
- [x] `UsuarioAdminController` (CRUD + restricción por nivel de rol)
- [x] `DestacadoController` (toggle destacado / nueva colección)
- [x] `Admin/Productos/Index.vue` (tabla + filtros + acciones)
- [x] `Admin/Productos/Form.vue` (crear/editar + gestión tallas + catálogos)
- [x] `Admin/Inventario/Index.vue` (listado movimientos)
- [x] `Admin/Inventario/Create.vue` (formulario movimiento con técnica FIFO/Promedio)
- [x] `Admin/Usuarios/Index.vue` (tabla + filtros por rol)
- [x] `Admin/Usuarios/Form.vue` (crear/editar con asignación de rol)
- [x] `Admin/Menu/Index.vue` (árbol de menú dinámico)

## FASE 12 – Estadísticas y Reportes ✅
- [x] `EstadisticaController` (top productos vendidos, pedidos por estado, visitas, resumen)
- [x] `ReporteController` (ventas por mes, ganancias por periodo, filtro por año)
- [x] `Admin/Estadisticas/Index.vue` (dashboard con barras CSS)
- [x] `Admin/Reportes/Index.vue` (gráfico barras mensuales + resumen anual)

## FASE 14 – Pagos con PagoFácil (pasarela boliviana, principal) ✅ Implementada y probada contra sandbox real
- [x] Documentación de PagoFácil revisada (`InfoPagoFacil/md/`: Botón de Pago CheckOut v2.1, Flujo QR, API MasterQR v1.1.0, Postman)
- [x] Decisiones confirmadas: API MasterQR como base, dual gateway con Stripe (selector en UI), cuotas vía QR por cuota sin cobro automático
- [x] Credenciales de sandbox cargadas en `.env`/`.env.example` y **validadas contra la API real** (login, list-enabled-services, generate-qr, query-transaction)
- [x] `PagoFacilClient` (login + caché de token, reintento en 401) — `paymentMethodId=34` confirmado real vía `list-enabled-services`
- [x] Migraciones aditivas en `pago` y `cuota` (columnas `gateway`, `pagofacil_*`) aplicadas en BD remota
- [x] `QrPagoService` (pago único) + rutas + bloque QR en `Pedidos/Pagar.vue` (selector Stripe/PagoFácil, QR + verificación automática)
- [x] Callback público (`PagoFacilCallbackController` + exclusión CSRF) + `CallbackHandlerService` — simulado y verificado (confirma pedido y cuotas)
- [x] `CuotasPagoFacilService` (plan de cuotas vía QR por cuota) + botón "Pagar esta cuota" en `Pedidos/Show.vue`
- [x] Comando `pagos:sincronizar-pagofacil` (pre-generación de QR + consulta de respaldo) + `Kernel::schedule()->everyFifteenMinutes()` — probado contra datos reales
- [x] Visibilidad admin de solo lectura con `gateway` + selector de pasarela visible para el cliente
- [x] Fix de SSL local (`storage/app/cacert.pem` + `PagoFacilClient::http()`) — sin tocar configuración fuera del proyecto
- [x] Actualizar `db_analysis.md`, `plan_pagos_pagofacil.md` y agregar `PR-27` a `plan_de_pruebas.md`
- [x] Verificación visual con clics reales en navegador (Playwright + Chromium instalados temporalmente): selector de pasarela, QR de pago único, "Ya pagué, verificar", plan de cuotas, botón "Pagar esta cuota", sección de pago en Show.vue — todo correcto. Se encontró y corrigió un bug real (estado de QR compartido entre pestañas "Pago único"/"Plan de cuotas")
- [ ] Pendiente (no bloqueante, requiere autorización para exponer el servidor local): probar la entrega real del callback con un túnel público (ngrok/similar); hoy la red de seguridad (consulta manual + comando programado) es la fuente de verdad probada

Ver `plan_pagos_pagofacil.md` para el detalle completo (arquitectura, hallazgos de sandbox, riesgos).

## FASE 13 – Pagos con Stripe ✅ (modo TEST)
- [x] Instalar `stripe/stripe-php` + `@stripe/stripe-js` + `qrcode`
- [x] Migración aditiva en tabla `pago` (stripe_payment_intent_id, stripe_status, metodo)
- [x] Capa de servicios `App\Services\Stripe\` (PagoUnicoService, CuotasService, MetodoPagoService, WebhookHandlerService)
- [x] `Cliente\PagoController` (pago único con QR + plan de cuotas 2/3/6) y `Cliente\MetodoPagoController`
- [x] `StripeWebhookController` + exclusión CSRF (`stripe/webhook`)
- [x] `Pedidos/Pagar.vue` + `MetodosPago/Index.vue` (Stripe Elements)
- [x] Secciones de pago en `Pedidos/Show.vue`, `Pedidos/Historial.vue`, `Admin/Pedidos/Show.vue` (solo lectura)
- [x] Comando programado `pagos:cobrar-cuotas` (cobro off-session de cuotas vencidas) + `Kernel::schedule()`
- [x] Vincular tabla `metodo_pago_usuario` (modelo reescrito, en uso)
- [x] `STRIPE_KEY`/`STRIPE_SECRET` reales puestos en `.env` (cuenta Stripe de prueba)
- [x] Stripe CLI v1.43.2 instalado (`winget install Stripe.StripeCli`)
- [ ] Pendiente del usuario: correr `stripe login` + `stripe listen --forward-to localhost:8000/stripe/webhook` y completar `STRIPE_WEBHOOK_SECRET` real en `.env` (sigue con placeholder)
- [ ] Pendiente: verificar end-to-end en navegador los 10 casos de `PR-26` en `plan_de_pruebas.md`

---

## Resumen de progreso

| Métrica | Valor |
|---------|-------|
| Fases completadas | 14 de 14 |
| Controladores | 21 |
| Modelos Eloquent | 20 |
| Servicios (`App\Services\Stripe` + `App\Services\PagoFacil`) | 8 (4 + 4) |
| Migraciones | 14 |
| Seeders | 7 |
| Comandos Artisan | 2 (`pagos:cobrar-cuotas`, `pagos:sincronizar-pagofacil`) |
| Vistas Vue | 24 páginas + 1 layout + 2 componentes |
| Composables | 1 (useTema.js) |
| Middleware custom | 3 (CheckRole, TrackPageVisit, HandleInertiaRequests) |
| Rutas | 70+ |
| Temas CSS | 3 × 2 modos = 6 combinaciones |
