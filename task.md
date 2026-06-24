# Tareas – Proyecto Final Tienda de Ropa (INF-513)

## FASE 1 – Setup del Proyecto Laravel ✅
- [x] Crear proyecto Laravel 9 (compatible PHP 8.0) en C:\proyectos\tiendaropa
- [x] Instalar Inertia server-side (v1.3.4)
- [x] Instalar Inertia client + Vue 3 + plugin Vite
- [x] Configurar `vite.config.js`
- [x] Configurar `.env` con credenciales PostgreSQL
- [x] Verificar conexión a BD (15 usuarios, 13 tablas originales)

## FASE 2 – Modelos Eloquent + Migraciones aditivas ✅
- [x] Crear tabla `roles`
- [x] Adaptar tabla `usuario` (email_verified_at, remember_token, rol_nuevo)
- [x] Adaptar tabla `producto` (destacado, es_nueva_coleccion)
- [x] Crear tabla `talla`
- [x] Crear tabla `producto_talla`
- [x] Crear tabla `producto_imagen`
- [x] Crear tabla `carrito_item`
- [x] Crear tabla `favorito`
- [x] Crear tabla `menu_item`
- [x] Crear tabla `page_visit`
- [x] Crear tabla `metodo_pago_usuario`
- [x] Crear todos los Modelos Eloquent (20 modelos)
- [x] Seeders: roles (4), tallas (21), menu_items, roles asignados a 15 usuarios

## FASE 3 – Autenticación y Roles ✅
- [x] LoginController + RegisterController
- [x] Middleware CheckRole (corregido: usa rol_nuevo)
- [x] Grupos de rutas por rol en web.php (54 rutas)
- [x] Auth/Login.vue
- [x] Auth/Register.vue

## FASE 4 – Middleware Visitas + Inertia Global ✅
- [x] Middleware TrackPageVisit
- [x] HandleInertiaRequests con datos globales (auth, menu, pageVisits, flash)

## FASE 5 – Layout Global y Sistema de Temas ✅
- [x] AppLayout.vue (header + footer + menú dinámico + buscador + carrito badge)
- [x] app.css con 3 temas (niños/jóvenes/adultos) + día/noche
- [x] useTema.js composable
- [x] Selector de temas, modo día/noche, accesibilidad A-/A/A+

## FASE 6 – Home y Destacados ✅
- [x] HomeController
- [x] Home/Index.vue (hero, destacados, nueva colección, promociones)

## FASE 7 – Catálogo, Filtros y Búsqueda Global ✅
- [x] CatalogoController (filtros: catálogo, categoría, talla, precio, búsqueda)
- [x] BuscadorController (búsqueda global filtrada por rol)
- [x] Catalogo/Index.vue (filtros + grid + paginación)
- [x] Catalogo/Show.vue (galería imágenes, tallas, carrito, favoritos, métricas)

## FASE 8 – Carrito de Compras ✅
- [x] CarritoController (CRUD: listar, agregar, actualizar, eliminar)
- [x] Carrito/Index.vue (items, cantidades, subtotales, resumen, checkout)

## FASE 9 – Favoritos ✅
- [x] FavoritoController (toggle + listado)
- [x] Favoritos/Index.vue (grid favoritos + agregar al carrito)

## FASE 10 – Pedidos ✅
- [x] PedidoController (cliente: crear, store, historial, show)
- [x] PedidoAdminController (admin: listar, ver, cambiar estado)
- [x] Pedidos/Create.vue (formulario envío + resumen)
- [x] Pedidos/Historial.vue (lista paginada con estados)
- [x] Pedidos/Show.vue (detalle pedido)
- [x] Admin/Pedidos/Index.vue (tabla + cambio estado inline)
- [x] Admin/Pedidos/Show.vue (detalle completo)

## FASE 11 – Gestión Admin ✅
- [x] ProductoAdminController (CRUD + tallas + catálogos)
- [x] InventarioController (listar + registrar movimientos)
- [x] MenuAdminController (CRUD + cache invalidation)
- [x] UsuarioAdminController (CRUD + restricción por nivel)
- [x] DestacadoController (toggle destacado/nueva colección)
- [x] Admin/Productos/Index.vue (tabla + filtros + acciones)
- [x] Admin/Productos/Form.vue (crear/editar + tallas + catálogos)
- [x] Admin/Inventario/Index.vue (movimientos)
- [x] Admin/Inventario/Create.vue (formulario movimiento)
- [x] Admin/Usuarios/Index.vue (tabla + filtros)
- [x] Admin/Usuarios/Form.vue (crear/editar con roles)
- [x] Admin/Menu/Index.vue (árbol de menú)

## FASE 12 – Estadísticas y Reportes ✅
- [x] EstadisticaController (top productos, pedidos por estado, visitas, resumen)
- [x] ReporteController (ventas por mes, ganancias, filtro por año)
- [x] Admin/Estadisticas/Index.vue (dashboard con barras CSS)
- [x] Admin/Reportes/Index.vue (gráfico barras mensuales + resumen anual)

## FASE 13 – Pagos con Stripe ⏸️ PENDIENTE (según indicación del usuario)
- [ ] Instalar stripe/stripe-php + @stripe/stripe-js
- [ ] PagoController (pago único + plan de cuotas)
- [ ] Migración aditiva en tabla pago (Stripe columns)
- [ ] Pagos/Checkout.vue con Stripe Elements
- [ ] Webhooks Stripe
