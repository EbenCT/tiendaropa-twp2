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
- [x] Seeders: `RolSeeder`, `TallaSeeder`, `MenuItemSeeder`, `AsignarRolesUsuariosSeeder`

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

## FASE 13 – Pagos con Stripe ⏸️ PENDIENTE
- [ ] Instalar `stripe/stripe-php` + `@stripe/stripe-js`
- [ ] `PagoController` (pago único + plan de cuotas)
- [ ] Migración aditiva en tabla `pago` (stripe_payment_intent_id, stripe_status, metodo)
- [ ] `Pagos/Checkout.vue` con Stripe Elements
- [ ] Webhooks Stripe para confirmar pagos asíncronos
- [ ] Vincular tabla `metodo_pago_usuario` (ya existe, vacía)

---

## Resumen de progreso

| Métrica | Valor |
|---------|-------|
| Fases completadas | 12 de 13 |
| Controladores | 17 |
| Modelos Eloquent | 20 |
| Migraciones | 11 |
| Seeders | 4 |
| Vistas Vue | 21 páginas + 1 layout + 1 componente |
| Composables | 1 (useTema.js) |
| Middleware custom | 3 (CheckRole, TrackPageVisit, HandleInertiaRequests) |
| Rutas | 45+ |
| Temas CSS | 3 × 2 modos = 6 combinaciones |
