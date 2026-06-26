# Plan de Pruebas – Tienda de Ropa (INF-513)

**Proyecto:** Tienda de Ropa Online  
**Stack:** Laravel 9 · Inertia.js v1.3 · Vue 3.5 · PostgreSQL  
**Fecha:** 25 de junio de 2026  
**Objetivo:** Verificar todas las funcionalidades implementadas en las Fases 1–12 antes del deploy final.

---

## Índice

1. [Requisitos previos](#1-requisitos-previos)
2. [PR-01: Servidor y conexión](#2-pr-01-servidor-y-conexión)
3. [PR-02: Autenticación](#3-pr-02-autenticación)
4. [PR-03: Roles y permisos](#4-pr-03-roles-y-permisos)
5. [PR-04: Sistema de temas CSS](#5-pr-04-sistema-de-temas-css)
6. [PR-05: Modo día/noche](#6-pr-05-modo-díanoche)
7. [PR-06: Accesibilidad (tamaño fuente)](#7-pr-06-accesibilidad)
8. [PR-07: Menú dinámico](#8-pr-07-menú-dinámico)
9. [PR-08: Home y destacados](#9-pr-08-home-y-destacados)
10. [PR-09: Catálogo y filtros](#10-pr-09-catálogo-y-filtros)
11. [PR-10: Detalle de producto](#11-pr-10-detalle-de-producto)
12. [PR-11: Buscador global](#12-pr-11-buscador-global)
13. [PR-12: Carrito de compras](#13-pr-12-carrito-de-compras)
14. [PR-13: Favoritos](#14-pr-13-favoritos)
15. [PR-14: Pedidos (cliente)](#15-pr-14-pedidos-cliente)
16. [PR-15: Gestión de pedidos (admin)](#16-pr-15-gestión-de-pedidos-admin)
17. [PR-16: Gestión de productos (admin)](#17-pr-16-gestión-de-productos-admin)
18. [PR-17: Inventario](#18-pr-17-inventario)
19. [PR-18: Gestión de usuarios (admin)](#19-pr-18-gestión-de-usuarios-admin)
20. [PR-19: Menú dinámico (admin)](#20-pr-19-menú-dinámico-admin)
21. [PR-20: Estadísticas](#21-pr-20-estadísticas)
22. [PR-21: Reportes](#22-pr-21-reportes)
23. [PR-22: Contador de visitas](#23-pr-22-contador-de-visitas)
24. [PR-23: Validaciones en español](#24-pr-23-validaciones-en-español)
25. [PR-24: Flujos completos (casos de uso end-to-end)](#25-pr-24-flujos-completos)
26. [PR-25: Responsive](#26-pr-25-responsive)

---

## 1. Requisitos previos

Antes de iniciar las pruebas:

```bash
# Levantar servidores
php artisan serve          # http://127.0.0.1:8000
npm run dev                # Vite HMR

# Verificar BD conectada
php artisan tinker --execute="echo App\Models\User::count();"
# Esperado: 15 (o más si se han registrado nuevos)
```

### Usuarios de prueba disponibles en BD

| Email | Rol esperado | Para probar |
|-------|-------------|-------------|
| (verificar con `SELECT email, rol_nuevo FROM usuario`) | admin | Todo |
| (verificar) | propietario | Usuarios, reportes |
| (verificar) | vendedor | Productos, inventario, pedidos |
| (verificar) | cliente | Carrito, favoritos, pedidos |

> **IMPORTANTE:** Antes de probar, verificar las credenciales reales de los usuarios con:
> ```bash
> php artisan tinker --execute="App\Models\User::select('id','email','rol_nuevo','nombre')->get()->toJson();"
> ```

---

## 2. PR-01: Servidor y conexión

| ID | Caso de prueba | Pasos | Resultado esperado | ✅/❌ |
|----|---------------|-------|--------------------|----|
| PR-01.1 | Levantar servidor Laravel | `php artisan serve` | Server running en `127.0.0.1:8000` | |
| PR-01.2 | Levantar Vite | `npm run dev` | Vite server corriendo, HMR habilitado | |
| PR-01.3 | Acceder al home | Navegar a `http://127.0.0.1:8000/` | Página renderiza con hero "Tu Estilo, Tu Manera" | |
| PR-01.4 | Conexión BD | Verificar que hay datos en home (promociones) | Muestra al menos 1 promoción activa | |
| PR-01.5 | Sin errores en consola | Abrir DevTools → Console | No hay errores JS (solo warnings de Vite ok) | |

---

## 3. PR-02: Autenticación

| ID | Caso de prueba | Pasos | Resultado esperado | ✅/❌ |
|----|---------------|-------|--------------------|----|
| PR-02.1 | Ver formulario login | Navegar a `/login` | Muestra formulario con email y password | |
| PR-02.2 | Login exitoso | Ingresar credenciales válidas → clic "Ingresar" | Redirige a home con flash "¡Bienvenido, [nombre]!" | |
| PR-02.3 | Login fallido | Ingresar credenciales inválidas → clic "Ingresar" | Muestra error "Las credenciales ingresadas no son correctas." | |
| PR-02.4 | Login con campos vacíos | Dejar email y/o password vacíos → submit | Mensajes de validación en español | |
| PR-02.5 | Logout | Clic en nombre usuario → "Cerrar Sesión" | Redirige a home con flash "Has cerrado sesión correctamente." | |
| PR-02.6 | Ver formulario registro | Navegar a `/registro` | Muestra formulario completo de registro | |
| PR-02.7 | Registro exitoso | Llenar todos los campos válidos → submit | Crea usuario como cliente, redirige a home con bienvenida | |
| PR-02.8 | Registro con email duplicado | Usar email que ya existe | Error "El correo electrónico ya está registrado." | |
| PR-02.9 | Acceso a login estando autenticado | Ya logueado, navegar a `/login` | Redirige a home (middleware guest) | |
| PR-02.10 | Acceso a registro estando autenticado | Ya logueado, navegar a `/registro` | Redirige a home (middleware guest) | |

---

## 4. PR-03: Roles y permisos

| ID | Caso de prueba | Pasos | Resultado esperado | ✅/❌ |
|----|---------------|-------|--------------------|----|
| PR-03.1 | Cliente accede a carrito | Login como cliente → `/carrito` | Acceso permitido | |
| PR-03.2 | Cliente accede a admin productos | Login como cliente → `/admin/productos` | Acceso denegado (redirect o error 403) | |
| PR-03.3 | Vendedor accede a admin productos | Login como vendedor → `/admin/productos` | Acceso permitido | |
| PR-03.4 | Vendedor accede a admin usuarios | Login como vendedor → `/admin/usuarios` | Acceso denegado | |
| PR-03.5 | Propietario accede a admin usuarios | Login como propietario → `/admin/usuarios` | Acceso permitido | |
| PR-03.6 | Propietario accede a admin menú | Login como propietario → `/admin/menu` | Acceso denegado | |
| PR-03.7 | Admin accede a admin menú | Login como admin → `/admin/menu` | Acceso permitido | |
| PR-03.8 | Invitado accede a ruta protegida | Sin login → `/carrito` | Redirige a `/login` | |
| PR-03.9 | Invitado accede a rutas públicas | Sin login → `/`, `/catalogo`, `/buscar` | Acceso permitido | |

---

## 5. PR-04: Sistema de temas CSS

| ID | Caso de prueba | Pasos | Resultado esperado | ✅/❌ |
|----|---------------|-------|--------------------|----|
| PR-04.1 | Tema por defecto (adultos) | Abrir home sin localStorage previo | Body tiene clase `tema-adultos`, paleta crema/dorado, tipografía Playfair | |
| PR-04.2 | Cambiar a tema niños | Clic en botón circular coral/turquesa en header | Body cambia a `tema-ninos`, paleta vibrante, tipografía Nunito | |
| PR-04.3 | Cambiar a tema jóvenes | Clic en botón circular violeta/cian en header | Body cambia a `tema-jovenes`, fondo oscuro, tipografía Orbitron | |
| PR-04.4 | Cambiar a tema adultos | Clic en botón circular negro/dorado en header | Body cambia a `tema-adultos`, paleta elegante | |
| PR-04.5 | Persistencia del tema | Cambiar a tema niños → recargar página (F5) | Tema niños persiste (guardado en localStorage) | |
| PR-04.6 | Tema afecta todas las páginas | Cambiar tema → navegar entre home, catálogo, login | Tema se mantiene consistente en todas las páginas | |
| PR-04.7 | Variables CSS del tema niños | Inspeccionar con DevTools | `--color-primary: #FF6B6B`, `--font-body: Nunito` | |
| PR-04.8 | Variables CSS del tema jóvenes | Inspeccionar con DevTools | `--color-primary: #7C3AED`, `--font-body: Poppins` | |
| PR-04.9 | Variables CSS del tema adultos | Inspeccionar con DevTools | `--color-primary: #1C1C1C`, `--font-heading: Playfair Display` | |
| PR-04.10 | Indicador visual del tema activo | Observar botones de tema | El botón del tema activo tiene borde blanco y escala 1.15 | |

---

## 6. PR-05: Modo día/noche

| ID | Caso de prueba | Pasos | Resultado esperado | ✅/❌ |
|----|---------------|-------|--------------------|----|
| PR-05.1 | Detección automática (día) | Acceder entre 7:00–19:00 sin localStorage previo | Body tiene `modo-dia`, fondos claros | |
| PR-05.2 | Detección automática (noche) | Acceder entre 19:00–7:00 sin localStorage previo | Body tiene `modo-noche`, fondos oscuros | |
| PR-05.3 | Toggle manual a noche | Clic en botón 🌙 en header | Cambia a `modo-noche`, ícono cambia a ☀️ | |
| PR-05.4 | Toggle manual a día | Estando en noche, clic en botón ☀️ | Cambia a `modo-dia`, ícono cambia a 🌙 | |
| PR-05.5 | Persistencia del modo | Cambiar a noche manual → recargar | Modo noche persiste (localStorage) | |
| PR-05.6 | Modo noche + tema niños | Seleccionar tema niños + activar noche | Fondo oscuro `#1A0A0A`, textos claros `#FFE8E8` | |
| PR-05.7 | Modo noche + tema jóvenes | Seleccionar tema jóvenes + activar noche | Fondo más oscuro `#050505`, textos `#E2E8F0` | |
| PR-05.8 | Modo noche + tema adultos | Seleccionar tema adultos + activar noche | Fondo `#121212`, textos `#F5F0E8` | |
| PR-05.9 | Transición suave | Cambiar de modo | Transición CSS de 0.4s en background-color y color | |

---

## 7. PR-06: Accesibilidad

| ID | Caso de prueba | Pasos | Resultado esperado | ✅/❌ |
|----|---------------|-------|--------------------|----|
| PR-06.1 | Tamaño base | Sin modificar | `--font-scale: 1`, texto a 1rem base | |
| PR-06.2 | Aumentar fuente (A+) | Clic en botón A+ | Texto más grande, `--font-scale` sube a 1.1 | |
| PR-06.3 | Aumentar máximo | Clic en A+ varias veces | Máximo `--font-scale: 1.4`, no sube más | |
| PR-06.4 | Reducir fuente (A-) | Clic en botón A- | Texto más pequeño, `--font-scale` baja a 0.9 | |
| PR-06.5 | Reducir mínimo | Clic en A- varias veces | Mínimo `--font-scale: 0.8`, no baja más | |
| PR-06.6 | Reset fuente (A) | Clic en botón A central | Vuelve a `--font-scale: 1` | |
| PR-06.7 | Persistencia escala | Cambiar a 1.2 → recargar | Escala persiste en localStorage | |
| PR-06.8 | Escala afecta todo el contenido | Aumentar fuente | Títulos, párrafos, botones, inputs, todo escala | |

---

## 8. PR-07: Menú dinámico

| ID | Caso de prueba | Pasos | Resultado esperado | ✅/❌ |
|----|---------------|-------|--------------------|----|
| PR-07.1 | Menú invitado | Sin login, ver header | Muestra: Inicio, Catálogo, Promociones | |
| PR-07.2 | Menú cliente | Login como cliente, ver header | Agrega: Mi Carrito, Favoritos, Mis Pedidos | |
| PR-07.3 | Menú vendedor | Login como vendedor, ver header | Agrega: Gestión (con hijos: Productos, Inventario, Pedidos) | |
| PR-07.4 | Menú propietario | Login como propietario, ver header | Agrega: Usuarios (hijo de Gestión), Reportes | |
| PR-07.5 | Menú admin | Login como admin, ver header | Agrega: Sistema (Menú Dinámico, Estadísticas) | |
| PR-07.6 | Submenú dropdown | Hover sobre "Gestión" | Despliega submenú con hijos | |
| PR-07.7 | Links funcionales | Clic en cada ítem del menú | Navega a la ruta correcta sin errores | |
| PR-07.8 | Caché del menú | Cambiar ítems en BD → esperar 5 min | Menú se actualiza tras invalidación de caché | |

---

## 9. PR-08: Home y destacados

| ID | Caso de prueba | Pasos | Resultado esperado | ✅/❌ |
|----|---------------|-------|--------------------|----|
| PR-08.1 | Hero section | Acceder a `/` | Muestra "Tu Estilo, Tu Manera" + subtítulo + CTAs | |
| PR-08.2 | CTA "Ver Catálogo" | Clic en botón | Navega a `/catalogo` | |
| PR-08.3 | CTA "Crear Cuenta" | Sin login, clic en botón | Navega a `/registro` | |
| PR-08.4 | CTA oculto si autenticado | Login → volver a home | Botón "Crear Cuenta" no aparece | |
| PR-08.5 | Productos destacados | Marcar productos como destacados en admin | Sección "⭐ Productos Destacados" muestra hasta 8 productos | |
| PR-08.6 | Nueva colección | Marcar productos como nueva colección | Sección "✨ Nueva Colección" muestra hasta 8 productos | |
| PR-08.7 | Promociones activas | Tener promoción con fechas vigentes | Sección "🏷️ Promociones Activas" muestra nombre, descuento, productos | |
| PR-08.8 | Estado vacío | Sin productos destacados ni promociones | Muestra "🛍️ Próximamente nuevos productos" + link a catálogo | |

---

## 10. PR-09: Catálogo y filtros

| ID | Caso de prueba | Pasos | Resultado esperado | ✅/❌ |
|----|---------------|-------|--------------------|----|
| PR-09.1 | Listado completo | Navegar a `/catalogo` | Grid de productos activos con nombre, precio, imagen | |
| PR-09.2 | Filtro por catálogo | Seleccionar catálogo (hombre/mujer/niños) | Solo muestra productos de ese catálogo | |
| PR-09.3 | Filtro por categoría | Seleccionar una categoría | Filtra productos de esa categoría | |
| PR-09.4 | Filtro por talla | Seleccionar una talla | Muestra productos que tienen esa talla disponible | |
| PR-09.5 | Filtro por rango de precio | Establecer precio mín y/o máx | Filtra dentro del rango | |
| PR-09.6 | Búsqueda por nombre | Escribir texto en campo de búsqueda | Filtra productos por nombre (LIKE) | |
| PR-09.7 | Combinación de filtros | Aplicar 2+ filtros simultáneamente | Muestra intersección de filtros | |
| PR-09.8 | Limpiar filtros | Quitar filtros | Vuelve a mostrar todos los productos | |
| PR-09.9 | Paginación | Más de N productos | Links de paginación funcionales | |
| PR-09.10 | Sin resultados | Filtros que no retornan nada | Mensaje "No se encontraron productos" | |

---

## 11. PR-10: Detalle de producto

| ID | Caso de prueba | Pasos | Resultado esperado | ✅/❌ |
|----|---------------|-------|--------------------|----|
| PR-10.1 | Acceder al detalle | Clic en ProductoCard en catálogo | Navega a `/catalogo/{id}` | |
| PR-10.2 | Información mostrada | Ver página de detalle | Nombre, descripción, precio, stock, imagen | |
| PR-10.3 | Tallas disponibles | Producto con tallas en `producto_talla` | Muestra selector de tallas | |
| PR-10.4 | Métricas de ventas | Producto con ventas previas | Muestra unidades vendidas (desde `detalle_pedido`) | |
| PR-10.5 | Agregar al carrito | Clic en "Agregar al carrito" (autenticado) | Agrega al carrito, muestra confirmación | |
| PR-10.6 | Toggle favorito | Clic en botón favorito (autenticado) | Agrega/quita de favoritos | |
| PR-10.7 | Acciones requieren login | Sin login, intentar agregar al carrito | Redirige a login o muestra mensaje | |

---

## 12. PR-11: Buscador global

| ID | Caso de prueba | Pasos | Resultado esperado | ✅/❌ |
|----|---------------|-------|--------------------|----|
| PR-11.1 | Visible siempre | En cualquier página, mirar header | Campo de búsqueda siempre presente con ícono 🔍 | |
| PR-11.2 | Búsqueda de productos | Escribir nombre de producto (≥2 chars) | Dropdown con sección "Productos" y resultados | |
| PR-11.3 | Búsqueda de categorías | Escribir nombre de categoría | Dropdown con sección "Categorías" | |
| PR-11.4 | Acciones del sistema | Escribir "producto" o "usuario" (como admin) | Dropdown con sección "Acciones del sistema" | |
| PR-11.5 | Filtrado por rol | Como invitado buscar "usuario" | NO muestra acciones de admin | |
| PR-11.6 | Filtrado por rol (admin) | Como admin buscar "usuario" | Muestra acción "Gestionar usuarios" | |
| PR-11.7 | Debounce 300ms | Escribir rápido | Solo una petición al server tras dejar de escribir | |
| PR-11.8 | Limpiar búsqueda | Clic en botón ✕ | Campo se vacía, dropdown se cierra | |
| PR-11.9 | Cerrar al clic fuera | Clic fuera del dropdown | Dropdown se cierra | |
| PR-11.10 | Resultado con imagen | Buscar producto con imagen | Muestra thumbnail del producto en dropdown | |
| PR-11.11 | Resultado con precio | Buscar producto | Muestra precio "Bs. X.XX" en dropdown | |
| PR-11.12 | Navegar a resultado | Clic en resultado del dropdown | Navega a la URL del resultado | |

---

## 13. PR-12: Carrito de compras

| ID | Caso de prueba | Pasos | Resultado esperado | ✅/❌ |
|----|---------------|-------|--------------------|----|
| PR-12.1 | Ver carrito vacío | Login como cliente → `/carrito` | Muestra mensaje "carrito vacío" | |
| PR-12.2 | Agregar producto | Desde detalle de producto → "Agregar al carrito" | Producto aparece en carrito con cantidad 1 | |
| PR-12.3 | Modificar cantidad | En carrito, cambiar cantidad de un ítem | Subtotal se recalcula | |
| PR-12.4 | Eliminar ítem | Clic en eliminar en un ítem | Ítem desaparece del carrito | |
| PR-12.5 | Total correcto | Varios ítems con distintas cantidades | Total = Σ(precio × cantidad) | |
| PR-12.6 | Badge en header | Agregar productos al carrito | Badge 🛒 muestra cantidad total de ítems | |
| PR-12.7 | Persistencia | Agregar productos → logout → login | Carrito persiste (guardado en BD) | |
| PR-12.8 | Botón checkout | Carrito con ítems → clic checkout | Navega a crear pedido | |

---

## 14. PR-13: Favoritos

| ID | Caso de prueba | Pasos | Resultado esperado | ✅/❌ |
|----|---------------|-------|--------------------|----|
| PR-13.1 | Ver favoritos vacíos | Login como cliente → `/favoritos` | Muestra mensaje vacío | |
| PR-13.2 | Agregar favorito | Desde detalle producto → clic favorito | Producto aparece en lista de favoritos | |
| PR-13.3 | Quitar favorito | Clic toggle en producto ya favorito | Producto desaparece de favoritos | |
| PR-13.4 | Agregar al carrito desde favoritos | En `/favoritos`, clic "agregar al carrito" | Producto se agrega al carrito | |
| PR-13.5 | Solo autenticados | Sin login → intentar toggle favorito | No permitido / redirige a login | |

---

## 15. PR-14: Pedidos (cliente)

| ID | Caso de prueba | Pasos | Resultado esperado | ✅/❌ |
|----|---------------|-------|--------------------|----|
| PR-14.1 | Crear pedido | Carrito con ítems → checkout → llenar datos envío → submit | Pedido creado, carrito vaciado, redirige a historial | |
| PR-14.2 | Campos obligatorios | Intentar crear pedido sin dirección | Mensaje de error en español | |
| PR-14.3 | Resumen del pedido | En formulario de crear pedido | Muestra lista de productos, cantidades y total | |
| PR-14.4 | Historial de pedidos | Navegar a `/pedidos/historial` | Lista de pedidos con fecha, total, estado | |
| PR-14.5 | Ver detalle de pedido | Clic en un pedido del historial | Muestra productos, cantidades, precios, datos envío, estado | |
| PR-14.6 | Estado inicial | Crear nuevo pedido | Estado = PENDIENTE | |
| PR-14.7 | Solo pedidos propios | Cliente A no puede ver pedidos de Cliente B | Solo ve sus propios pedidos | |

---

## 16. PR-15: Gestión de pedidos (admin)

| ID | Caso de prueba | Pasos | Resultado esperado | ✅/❌ |
|----|---------------|-------|--------------------|----|
| PR-15.1 | Listar todos los pedidos | Login como vendedor+ → `/admin/pedidos` | Tabla con todos los pedidos de todos los usuarios | |
| PR-15.2 | Ver detalle del pedido | Clic en un pedido | Muestra datos completos + datos del cliente | |
| PR-15.3 | Cambiar estado a CONFIRMADO | Seleccionar CONFIRMADO → guardar | Estado actualizado | |
| PR-15.4 | Cambiar estado a ENVIADO | Desde CONFIRMADO → ENVIADO | Estado actualizado | |
| PR-15.5 | Cambiar estado a ENTREGADO | Desde ENVIADO → ENTREGADO | Estado actualizado | |
| PR-15.6 | Flujo completo de estados | PENDIENTE → CONFIRMADO → ENVIADO → ENTREGADO | Cada transición funciona correctamente | |

---

## 17. PR-16: Gestión de productos (admin)

| ID | Caso de prueba | Pasos | Resultado esperado | ✅/❌ |
|----|---------------|-------|--------------------|----|
| PR-16.1 | Listar productos | Login como vendedor+ → `/admin/productos` | Tabla con todos los productos | |
| PR-16.2 | Crear producto | Clic "Crear" → llenar formulario → guardar | Producto creado en BD | |
| PR-16.3 | Editar producto | Clic "Editar" en un producto → modificar → guardar | Datos actualizados | |
| PR-16.4 | Eliminar producto | Clic "Eliminar" → confirmar | Producto eliminado (o marcado inactivo) | |
| PR-16.5 | Asignar tallas | En formulario, seleccionar tallas con stock | Registros en `producto_talla` | |
| PR-16.6 | Asignar catálogos | En formulario, seleccionar catálogos | Registros en `catalogo_producto` | |
| PR-16.7 | Toggle destacado | Clic en toggle destacado | Campo `destacado` cambia, aparece en home | |
| PR-16.8 | Toggle nueva colección | Clic en toggle nueva colección | Campo `es_nueva_coleccion` cambia | |
| PR-16.9 | Validaciones del formulario | Dejar campos obligatorios vacíos | Mensajes de error en español | |

---

## 18. PR-17: Inventario

| ID | Caso de prueba | Pasos | Resultado esperado | ✅/❌ |
|----|---------------|-------|--------------------|----|
| PR-17.1 | Listar movimientos | Login como vendedor+ → `/admin/inventario` | Tabla con movimientos de stock | |
| PR-17.2 | Registrar entrada | Clic "Crear" → tipo entrada → cantidad → guardar | Movimiento registrado, stock del producto aumenta | |
| PR-17.3 | Registrar salida | Crear movimiento tipo salida | Movimiento registrado, stock disminuye | |
| PR-17.4 | Seleccionar técnica | Elegir FIFO o Promedio | Técnica registrada en el movimiento | |
| PR-17.5 | Validación de cantidad | Intentar salida mayor al stock | Error de validación | |

---

## 19. PR-18: Gestión de usuarios (admin)

| ID | Caso de prueba | Pasos | Resultado esperado | ✅/❌ |
|----|---------------|-------|--------------------|----|
| PR-18.1 | Listar usuarios | Login como propietario+ → `/admin/usuarios` | Tabla con todos los usuarios | |
| PR-18.2 | Crear usuario | Clic "Crear" → llenar datos + rol → guardar | Usuario creado con rol asignado | |
| PR-18.3 | Editar usuario | Clic "Editar" → modificar datos → guardar | Datos actualizados | |
| PR-18.4 | Eliminar/desactivar usuario | Clic "Eliminar" → confirmar | Usuario eliminado o desactivado | |
| PR-18.5 | Restricción por nivel | Propietario no puede crear admin | No aparece opción de rol admin | |
| PR-18.6 | Filtrar por rol | Seleccionar filtro de rol | Solo muestra usuarios de ese rol | |

---

## 20. PR-19: Menú dinámico (admin)

| ID | Caso de prueba | Pasos | Resultado esperado | ✅/❌ |
|----|---------------|-------|--------------------|----|
| PR-19.1 | Ver árbol de menú | Login como admin → `/admin/menu` | Muestra ítems en estructura jerárquica | |
| PR-19.2 | Crear ítem de menú | Clic "Crear" → llenar label, ruta, nivel, padre → guardar | Ítem creado y visible | |
| PR-19.3 | Editar ítem de menú | Clic "Editar" → modificar → guardar | Ítem actualizado | |
| PR-19.4 | Eliminar ítem de menú | Clic "Eliminar" → confirmar | Ítem eliminado, hijos reasignados o eliminados | |
| PR-19.5 | Caché invalidado | Crear/editar/eliminar ítem | Menú se actualiza al recargar (caché limpiado) | |
| PR-19.6 | Nivel mínimo funcional | Crear ítem con nivel 3 | Solo visible para propietario+ | |

---

## 21. PR-20: Estadísticas

| ID | Caso de prueba | Pasos | Resultado esperado | ✅/❌ |
|----|---------------|-------|--------------------|----|
| PR-20.1 | Dashboard de estadísticas | Login como admin → `/admin/estadisticas` | Muestra dashboard con métricas | |
| PR-20.2 | Top productos vendidos | Ver sección de top productos | Lista productos por unidades vendidas (desc) | |
| PR-20.3 | Pedidos por estado | Ver sección de pedidos | Muestra conteo por cada estado | |
| PR-20.4 | Páginas más visitadas | Ver sección de visitas | Lista URLs con más visitas | |
| PR-20.5 | Resumen general | Ver métricas globales | Total productos, usuarios, pedidos, ventas | |

---

## 22. PR-21: Reportes

| ID | Caso de prueba | Pasos | Resultado esperado | ✅/❌ |
|----|---------------|-------|--------------------|----|
| PR-21.1 | Reporte de ventas | Login como propietario+ → `/admin/reportes` | Gráfico de barras mensuales | |
| PR-21.2 | Filtro por año | Seleccionar año diferente | Datos se actualizan al año seleccionado | |
| PR-21.3 | Ganancias del periodo | Ver resumen anual | Muestra total de ganancias del año | |
| PR-21.4 | Ventas por mes | Ver gráfico | Cada barra = ventas de un mes | |

---

## 23. PR-22: Contador de visitas

| ID | Caso de prueba | Pasos | Resultado esperado | ✅/❌ |
|----|---------------|-------|--------------------|----|
| PR-22.1 | Contador visible en footer | En cualquier página, ver footer | Muestra "👁️ Esta página ha sido visitada X veces" | |
| PR-22.2 | Incremento por visita | Recargar la misma página | Contador incrementa en 1 | |
| PR-22.3 | Contadores independientes | Visitar `/` y luego `/catalogo` | Cada página tiene su propio contador | |
| PR-22.4 | Formato correcto singular | Página con 1 visita | Muestra "1 vez" (no "1 veces") | |
| PR-22.5 | Formato correcto plural | Página con N>1 visitas | Muestra "N veces" | |
| PR-22.6 | Persistencia en BD | Verificar tabla `page_visit` | Registros con `page_url` y `visit_count` correctos | |

---

## 24. PR-23: Validaciones en español

| ID | Caso de prueba | Pasos | Resultado esperado | ✅/❌ |
|----|---------------|-------|--------------------|----|
| PR-23.1 | Login - email vacío | Submit sin email | "El correo electrónico es obligatorio." | |
| PR-23.2 | Login - email inválido | Ingresar "abc" como email | "Ingresa un correo electrónico válido." | |
| PR-23.3 | Login - password vacío | Submit sin password | "La contraseña es obligatoria." | |
| PR-23.4 | Registro - campos vacíos | Submit vacío | Mensajes en español para cada campo | |
| PR-23.5 | Producto - nombre vacío | En admin, crear producto sin nombre | Mensaje en español | |
| PR-23.6 | Pedido - dirección vacía | Crear pedido sin dirección | Mensaje en español | |
| PR-23.7 | Errores inline en formularios | Cualquier formulario con error | Errores aparecen debajo del campo correspondiente | |

---

## 25. PR-24: Flujos completos (casos de uso end-to-end)

### CU-01: Flujo completo de compra
```
1. Abrir home como invitado
2. Navegar al catálogo
3. Filtrar por categoría
4. Ver detalle de un producto
5. Clic "Ingresar" (redirige a login)
6. Registrar nueva cuenta
7. Volver al producto
8. Agregar al carrito
9. Ir al carrito
10. Crear pedido (llenar dirección, teléfono, referencia)
11. Ver pedido en historial
12. Verificar estado PENDIENTE
```

### CU-02: Gestión de pedido por admin
```
1. Login como vendedor
2. Ir a Admin → Pedidos
3. Ver pedido del CU-01
4. Cambiar estado a CONFIRMADO
5. Cambiar estado a ENVIADO
6. Cambiar estado a ENTREGADO
7. Login como el cliente del CU-01
8. Verificar en historial que estado = ENTREGADO
```

### CU-03: Gestión completa de producto
```
1. Login como vendedor
2. Ir a Admin → Productos → Crear
3. Llenar todos los campos + asignar tallas + catálogo
4. Guardar producto
5. Verificar en catálogo público
6. Editar producto (cambiar precio)
7. Toggle como destacado
8. Verificar que aparece en home
9. Registrar entrada de inventario
10. Verificar stock actualizado
```

### CU-04: Cambio visual completo de temas
```
1. Abrir home (tema adultos, modo día por defecto)
2. Verificar paleta crema/dorado, tipografía Playfair
3. Cambiar a tema niños
4. Verificar paleta coral/turquesa, tipografía Nunito
5. Activar modo noche
6. Verificar fondos oscuros con colores del tema niños
7. Cambiar a tema jóvenes
8. Verificar paleta violeta/cian en modo noche
9. Aumentar fuente con A+ (3 clics)
10. Verificar que todo el texto creció
11. Recargar página
12. Verificar que tema, modo y escala persisten
```

### CU-05: Buscador global con diferentes roles
```
1. Como invitado: buscar "chaqueta" → ver resultados de productos
2. Buscar "usuario" → NO debe mostrar acciones de admin
3. Login como admin
4. Buscar "usuario" → DEBE mostrar "Gestionar usuarios"
5. Buscar "categoría" → mostrar resultados de categorías
6. Clic en resultado → navega a la página correcta
```

### CU-06: Flujo de favoritos
```
1. Login como cliente
2. Ir al catálogo → ver detalle de un producto
3. Agregar a favoritos
4. Ir a otro producto → agregar a favoritos
5. Ir a /favoritos → verificar 2 productos
6. Agregar uno al carrito desde favoritos
7. Quitar el otro de favoritos
8. Verificar que solo queda 1 en favoritos
```

---

## 26. PR-25: Responsive

| ID | Caso de prueba | Pasos | Resultado esperado | ✅/❌ |
|----|---------------|-------|--------------------|----|
| PR-25.1 | Header en móvil (≤768px) | Reducir ventana a 375px ancho | Header se adapta: nav oculto, logo texto oculto, buscador full width | |
| PR-25.2 | Grid de productos en móvil | Catálogo en 375px | Grid de 2 columnas (minmax 160px) | |
| PR-25.3 | Grid de productos en desktop | Catálogo en 1920px | Grid hasta 5 columnas (minmax 240px) | |
| PR-25.4 | Formularios en móvil | Login/registro en 375px | Formularios ocupan 100% del ancho | |
| PR-25.5 | Tablas admin en móvil | Admin productos en 375px | Tablas con scroll horizontal o adaptadas | |

---

## Resumen de casos de prueba

| Sección | Cantidad de pruebas |
|---------|-------------------|
| PR-01: Servidor y conexión | 5 |
| PR-02: Autenticación | 10 |
| PR-03: Roles y permisos | 9 |
| PR-04: Sistema de temas CSS | 10 |
| PR-05: Modo día/noche | 9 |
| PR-06: Accesibilidad | 8 |
| PR-07: Menú dinámico | 8 |
| PR-08: Home y destacados | 8 |
| PR-09: Catálogo y filtros | 10 |
| PR-10: Detalle de producto | 7 |
| PR-11: Buscador global | 12 |
| PR-12: Carrito de compras | 8 |
| PR-13: Favoritos | 5 |
| PR-14: Pedidos (cliente) | 7 |
| PR-15: Gestión pedidos (admin) | 6 |
| PR-16: Gestión productos (admin) | 9 |
| PR-17: Inventario | 5 |
| PR-18: Gestión usuarios (admin) | 6 |
| PR-19: Menú dinámico (admin) | 6 |
| PR-20: Estadísticas | 5 |
| PR-21: Reportes | 4 |
| PR-22: Contador de visitas | 6 |
| PR-23: Validaciones en español | 7 |
| PR-24: Flujos completos (E2E) | 6 flujos |
| PR-25: Responsive | 5 |
| **TOTAL** | **164 pruebas + 6 flujos E2E** |
