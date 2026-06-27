# Errores Detectados

Este archivo registra los errores detectados durante las pruebas MVP.

> **Actualización 2026-06-26**: Se revisó el proyecto completo y se verificaron los 8 errores contra el código actual y la BD remota (vía `php artisan tinker` y peticiones HTTP reales a `php artisan serve` + `npm run dev`). Detalle de cada uno abajo.

## 1. Error en Registro de Usuario (PR-02)
* **Estado:** ✅ Corregido y verificado
* **Error message**: `SQLSTATE[42703]: Undefined column: 7 ERROR: no existe la columna «updated_at» en la relación «usuario»`
* **Causa/Problema**: El modelo `User` en Laravel asume que la tabla tiene las columnas `created_at` y `updated_at` (timestamps activados por defecto). Al intentar registrar un usuario o actualizar, falla porque la tabla `usuario` heredada del proyecto Java no tiene estas columnas.
* **Solución aplicada**: `public $timestamps = false;` ya está en `App\Models\User` (línea 14).
* **Verificación**: Se registró un usuario de prueba real vía POST a `/registro` → respuesta `302` a `/` con flash `"¡Bienvenido, Prueba! Tu cuenta ha sido creada."`. Usuario creado con `id=32`, `rol_nuevo=cliente`.

## 2. Error de Navegación en Enlaces (Inertia)
* **Estado:** ✅ Resuelto (efecto colateral del error #6)
* **Problema**: Los enlaces en el menú (como "Catálogo") y en las tarjetas de productos no dispararon la navegación al hacer clic en el entorno de pruebas, forzando la navegación directa por URL.
* **Causa real**: El `AppLayout.vue` renderiza el menú dinámico en cada página vía `route(item.route)`. Cuando un usuario logueado tenía el ítem "Mi Carrito" con `route_name='carrito'` (inexistente), Ziggy lanzaba una excepción JS al montar el layout (ver error #6), dejando la SPA de Inertia en estado roto y por eso ningún `Link` disparaba navegación.
* **Verificación**: Se confirmó con `php artisan route:list` que **todos** los `route_name` usados en `AppLayout.vue`, `ProductoCard.vue` y `menu_item` (BD) resuelven a rutas válidas (`home`, `catalogo`, `catalogo.show`, `carrito.index`, `favoritos.index`, `pedidos.historial`, etc. — 20/20 OK). Code review de los componentes `Link` no encontró errores de sintaxis. **Pendiente**: confirmar con un clic real en navegador (no se contó con herramienta de automatización de navegador en esta sesión), pero la causa raíz que rompía la SPA ya no existe.

## 3. Error en Seeder de Tallas (Posible causa raíz en DB)
* **Estado:** ✅ Corregido
* **Error message**: `SQLSTATE[42P10]: Invalid column reference: 7 ERROR: no hay restricción única o de exclusión que coincida con la especificación ON CONFLICT (SQL: insert into "talla" ...)`
* **Causa/Problema**: Un seeder estaba usando `upsert`/`ON CONFLICT` en la tabla `talla` sin índice único que coincidiera.
* **Solución aplicada**: `TallaSeeder` usa `updateOrInsert()` (no genera `ON CONFLICT`, hace SELECT+UPDATE/INSERT manual), por lo que no depende de una constraint única en BD.
* **Adicional**: Se agregaron a `TallaSeeder` las tallas de pantalón (28, 30, 32, 34, 36, 38) que faltaban — eran usadas por productos heredados de Java pero no existían en el catálogo de tallas nuevo (ver error #7).

## 4. Contraseñas de usuarios antiguos en texto plano (PR-02)
* **Estado:** ✅ Corregido y verificado
* **Problema**: Los usuarios preexistentes en la base de datos (heredados de Java) tenían sus contraseñas en texto plano. Laravel espera un hash bcrypt, por lo que el login fallaba para todos ellos.
* **Solución aplicada**: `HashPasswordsSeeder` (ya existía pero no estaba registrado en `DatabaseSeeder`) ahora se ejecuta automáticamente. Se corrió contra la BD remota.
* **Verificación**: `SELECT` directo confirma **0 de 20 usuarios** con password sin hash bcrypt. Login probado end-to-end con usuario de prueba (password hasheado) → `302` exitoso a home.

## 5. Redirección rota al autenticarse / registrarse (PR-02)
* **Estado:** ✅ Corregido y verificado
* **Problema**: La constante `HOME` estaba definida como `/home`, causando 404 al redirigir tras login/registro o al acceder a rutas "guest" estando logueado.
* **Solución aplicada**: `RouteServiceProvider::HOME = '/'` (`app/Providers/RouteServiceProvider.php:20`).
* **Verificación**: Login, registro y acceso a `/login` estando autenticado redirigen correctamente a `http://127.0.0.1:8000/` (sin 404).

## 6. Error Crítico de Ziggy (Bloquea acceso a clientes logueados)
* **Estado:** ✅ Corregido y verificado
* **Error message**: `Ziggy error: route 'carrito' is not in the route list`
* **Causa/Problema**: El menú dinámico (tabla `menu_item`) tenía guardada la ruta `carrito` en vez de `carrito.index`.
* **Solución aplicada**: `MenuItemSeeder` ya usa `route_name='carrito.index'`; se re-ejecutó el seeder en la BD remota.
* **Verificación**: `SELECT` confirma 0 filas con `route_name='carrito'` y 1 fila con `route_name='carrito.index'`. Prueba HTTP de un usuario logueado muestra el menú con `"Mi Carrito" -> carrito.index`, ruta que existe en `php artisan route:list`.

## 7. Ausencia del selector de tallas en detalle de producto (PR-10)
* **Estado:** ✅ Corregido y verificado
* **Causa real**: No era un bug de la vista (`Catalogo/Show.vue` ya condiciona correctamente con `v-if="producto.tallas?.length"`), sino que la tabla pivot `producto_talla` estaba **completamente vacía** (0 registros) — nunca se migraron las tallas heredadas de la columna libre `producto.talla` del proyecto Java.
* **Solución aplicada**: Se creó `ProductoTallaSeeder` que migra `producto.talla` (texto libre) + categoría → fila en `producto_talla` con `stock` = `producto.stock_actual`. También se agregaron a `TallaSeeder` las tallas de pantalón numéricas (28/30/32/34/36/38) que no existían.
* **Verificación**: `producto_talla` pasó de 0 a 20 registros (1 por producto). Petición HTTP real a `/catalogo/1` confirma que el prop `producto.tallas` incluye `{codigo: "M", pivot: {stock: 45}}`, por lo que el selector ya se renderiza.

## 8. Secciones faltantes en Home (PR-08)
* **Estado:** ✅ Corregido y verificado
* **Causa real**: El código de `HomeController`/`Home/Index.vue` era correcto; el problema era que **ningún producto** tenía `destacado=true` ni `es_nueva_coleccion=true` en la BD.
* **Solución aplicada**: `DestacadosSeeder` (ya existía pero no estaba registrado en `DatabaseSeeder`) ahora se ejecuta automáticamente, marcando 4 productos como destacados y 4 como nueva colección.
* **Verificación**: Petición HTTP real a `/` confirma `props.destacados.length = 4` y `props.nuevaColeccion.length = 4`.

---

## Cambios de código realizados en esta sesión
* `database/seeders/TallaSeeder.php`: agregadas tallas de pantalón (28,30,32,34,36,38).
* `database/seeders/ProductoTallaSeeder.php`: **nuevo**, migra `producto.talla` legado → tabla pivot `producto_talla`.
* `database/seeders/DatabaseSeeder.php`: ahora llama también a `HashPasswordsSeeder`, `ProductoTallaSeeder` y `DestacadosSeeder` (existían como archivos sueltos sin ejecutarse).
* Se ejecutó `php artisan db:seed --force` contra la BD remota `db_grupo21sa`.

## Pendiente de verificación manual en navegador
* Error #2: confirmar con clic real que la navegación del menú y de las tarjetas de producto funciona (la causa raíz —Ziggy— ya está resuelta, pero no se contó con herramienta de automatización de navegador para clic real en esta sesión).
