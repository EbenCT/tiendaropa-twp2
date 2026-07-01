# Errores Detectados

Este archivo registra los errores detectados durante las pruebas MVP.

> **Actualización 2026-06-26 (sesión 1)**: Se revisó el proyecto completo y se verificaron los 8 errores contra el código actual y la BD remota (vía `php artisan tinker` y peticiones HTTP reales a `php artisan serve` + `npm run dev`).
>
> **Actualización 2026-06-26 (sesión 2)**: Se instaló Playwright para probar con clics reales en navegador (no solo HTTP) contra todo `plan_de_pruebas.md`. Esto confirmó el error #2 y permitió encontrar **5 bugs adicionales no detectados en la sesión 1** (#9 a #13), todos corregidos. Detalle completo abajo.

## 1. Error en Registro de Usuario (PR-02)
* **Estado:** ✅ Corregido y verificado
* **Error message**: `SQLSTATE[42703]: Undefined column: 7 ERROR: no existe la columna «updated_at» en la relación «usuario»`
* **Causa/Problema**: El modelo `User` en Laravel asume que la tabla tiene las columnas `created_at` y `updated_at` (timestamps activados por defecto). Al intentar registrar un usuario o actualizar, falla porque la tabla `usuario` heredada del proyecto Java no tiene estas columnas.
* **Solución aplicada**: `public $timestamps = false;` ya está en `App\Models\User` (línea 14).
* **Verificación**: Se registró un usuario de prueba real vía POST a `/registro` → respuesta `302` a `/` con flash `"¡Bienvenido, Prueba! Tu cuenta ha sido creada."`. Usuario creado con `id=32`, `rol_nuevo=cliente`.

## 2. Error de Navegación en Enlaces (Inertia)
* **Estado:** ✅ Corregido y verificado con clic real (Playwright)
* **Problema**: Los enlaces en el menú (como "Catálogo") y en las tarjetas de productos no dispararon la navegación al hacer clic en el entorno de pruebas, forzando la navegación directa por URL.
* **Causa real**: El `AppLayout.vue` renderiza el menú dinámico en cada página vía `route(item.route)`. Cuando un usuario logueado tenía el ítem "Mi Carrito" con `route_name='carrito'` (inexistente), Ziggy lanzaba una excepción JS al montar el layout (ver error #6), dejando la SPA de Inertia en estado roto y por eso ningún `Link` disparaba navegación.
* **Verificación**: Con Playwright se hizo login real, clic en el link "Catálogo" del menú, en el ícono del carrito y en una `ProductoCard`: los tres navegan correctamente a sus rutas (`/catalogo`, `/carrito`, `/catalogo/{id}`). Causa raíz confirmada: el cuello de botella era el servidor de desarrollo PHP (single-threaded, ~10s por request contra la BD remota en Bolivia) combinado con la ruta Ziggy rota; con la ruta corregida, la navegación SPA funciona de punta a punta.

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

## 9. "Agregar al Carrito" no agregaba nada (PR-12)
* **Estado:** ✅ Corregido y verificado
* **Causa/Problema**: En `Catalogo/Show.vue`, `agregarCarrito()` llamaba a `formCarrito.post(url, { data: {...}, preserveScroll: true })`. La opción `data` no existe en la API de `useForm().post()` de Inertia — el payload real enviado al servidor era `{}` (vacío), por lo que `CarritoController@agregar` rechazaba la petición por validación (`producto_id` requerido) y nunca se creaba el `carrito_item`.
* **Solución aplicada**: Se reemplazó por `formCarrito.transform(() => ({ producto_id, talla_id, cantidad })).post(url, { preserveScroll: true })`, el patrón correcto de Inertia para transformar el payload de un formulario vacío.
* **Verificación**: Capturando la petición real con Playwright, el payload pasó de `{}` a `{"producto_id":1,"talla_id":3,"cantidad":1}`, y se confirmó la fila creada en `carrito_item`.

## 10. Crear pedido (checkout) rompía con Error 500 (PR-14)
* **Estado:** ✅ Corregido y verificado
* **Causa/Problema**: `PedidoController@store` ejecutaba `Pedido::create([...,'total' => $total,...])`, pero la tabla `pedido` (heredada del proyecto Java) **no tiene columna `total`** (el total se calcula desde `detalle_pedido`/`venta`). Cualquier intento de finalizar una compra terminaba en `Internal Server Error`.
* **Solución aplicada**: Se quitó `'total' => $total` del `Pedido::create()` y de `$fillable` en `App\Models\Pedido`.
* **Verificación**: Con Playwright se completó el flujo real (agregar al carrito → `/pedidos/crear` → llenar dirección/teléfono → confirmar): se creó el pedido (`PENDIENTE`), su `detalle_pedido` y su `venta`, y el carrito quedó vacío.

## 11. Registrar movimiento de inventario rompía con Error 500 (PR-17)
* **Estado:** ✅ Corregido y verificado
* **Causa/Problema**: La columna `usuario_id` de la tabla `inventario` es `NOT NULL` sin valor por defecto, pero `InventarioController@store` nunca la asignaba al crear el movimiento.
* **Solución aplicada**: Se agregó `'usuario_id' => $request->user()->id` al `Inventario::create()` (y se quitó `'activo'`, que no es una columna real de esa tabla — ya se descartaba silenciosamente por no estar en `$fillable`, pero generaba confusión).
* **Verificación**: Movimiento de entrada registrado con Playwright como vendedor; fila creada en `inventario` con `usuario_id` poblado correctamente.

## 12. Crear/editar usuario con rol "admin" rompía con violación de constraint (PR-18)
* **Estado:** ✅ Corregido
* **Causa/Problema**: La columna heredada `usuario.rol` tiene un `CHECK` que solo permite `'PROPIETARIO'`, `'VENDEDOR'`, `'CLIENTE'` (el proyecto Java nunca tuvo un rol admin). `UsuarioAdminController` hacía `'rol' => strtoupper($request->rol_nuevo)`, así que si `rol_nuevo='admin'`, se intentaba guardar `'ADMIN'` y Postgres rechazaba el insert/update.
* **Nota de contexto**: en la práctica esto no era alcanzable desde la UI actual (el formulario nunca ofrece "admin" como opción, ni para el propio admin — ver `getRolesDisponibles()`), pero quedaba como bug latente ante cualquier cambio futuro o request directo.
* **Solución aplicada**: Nuevo método `rolLegado()` que mapea `'admin'` → `'PROPIETARIO'` para la columna heredada, usado en `store()` y `update()`.

## 13. Falta la página para crear/editar ítems del menú dinámico (PR-19)
* **Estado:** ✅ Implementado (a pedido del usuario, antes era una funcionalidad faltante, no un bug de regresión)
* **Problema**: `MenuAdminController@create` y `@edit` renderizaban `Inertia::render('Admin/Menu/Form', ...)`, pero ese componente Vue no existía — entrar a `/admin/menu/create` o `/admin/menu/{id}/edit` rompía. `Admin/Menu/Index.vue` solo mostraba el árbol de lectura y decía explícitamente que la gestión era "vía BD o seeder".
* **Solución aplicada**: Se creó `resources/js/Pages/Admin/Menu/Form.vue` (mismo patrón que `Productos/Form.vue` y `Usuarios/Form.vue`) y se agregaron botones "+ Nuevo Ítem", "Editar" y "✕" (desactivar) en `Admin/Menu/Index.vue`, conectados a las rutas `admin.menu.create/store/edit/update/destroy` que ya existían en el backend.
* **Verificación**: Ítem de menú creado con Playwright como admin; aparece en la tabla `menu_item`.

## 14. Imágenes de producto rotas en todo el sitio (hallazgo nuevo, no listado originalmente)
* **Estado:** ✅ Mitigado
* **Problema**: Las URLs heredadas del proyecto Java en `producto.imagen_url` (dominio `first.com.bo`) devuelven `404`, y el fallback `/img/placeholder.jpg` referenciado en el código tampoco existe como archivo. Resultado: ícono de imagen rota en home, catálogo, carrito, favoritos, pedidos y admin.
* **Solución aplicada**: Listener global de `error` en `resources/js/app.js` que reemplaza cualquier `<img>` roto por un placeholder SVG inline (data-URI), sin depender de un archivo estático ni tocar cada componente individualmente.
* **Verificación**: Con Playwright se confirmó que tras el fallback todas las imágenes de la página de inicio muestran el placeholder en vez del ícono de imagen rota.

## 15. Badge de cantidad del carrito siempre en 0 (hallazgo nuevo, no listado originalmente)
* **Estado:** ✅ Corregido
* **Problema**: En `AppLayout.vue`, `const carritoCount = ref(0)` nunca se actualizaba (comentario en el propio código: `// Se actualizará desde eventos`). El badge 🛒 del header nunca reflejaba la cantidad real de ítems en el carrito.
* **Solución aplicada**: Se agregó `carritoCount` a los props globales compartidos por `HandleInertiaRequests` (suma de `cantidad` en `carrito_item` del usuario autenticado), y `AppLayout.vue` ahora lo lee como `computed(() => usePage().props.carritoCount ?? 0)`.

## 16. Historial de pedidos, listado admin de pedidos y listado de inventario rotos por columna `created_at` inexistente (hallazgo nuevo, no listado originalmente)
* **Estado:** ✅ Corregido y verificado
* **Error message**: `SQLSTATE[42703]: Undefined column: 7 ERROR: no existe la columna «created_at» ... (SQL: select * from "pedido" order by "created_at" desc limit 15 offset 0)`
* **Causa/Problema**: Tres controladores distintos ordenaban resultados con `->orderByDesc('created_at')`, pero las tablas heredadas `pedido` e `inventario` (igual que `usuario`, ver error #1) no tienen `created_at`/`updated_at` — usan una columna `fecha`. Esto rompía con Error 500 **tres pantallas completas**: el historial de pedidos del cliente (`/pedidos/historial`), el listado de pedidos del admin (`/admin/pedidos`) y el listado de movimientos de inventario (`/admin/inventario`).
* **Por qué no se detectó antes**: en la verificación HTTP de la sesión 1 solo se comprobó el código `302` del *redirect* tras crear un pedido (que sí funcionaba), sin seguir la navegación hasta la página de destino real, que era la que fallaba. En la primera pasada de Playwright de la sesión 2, el script de pruebas tampoco esperó lo suficiente ni inspeccionó el contenido real de la página, por lo que PR-14.4, PR-15.1 y PR-17.1 quedaron marcados (incorrectamente) como ✅.
* **Solución aplicada**: se cambió `orderByDesc('created_at')` por `orderByDesc('fecha')` en `app/Http/Controllers/Cliente/PedidoController.php` (método `historial`), `app/Http/Controllers/Admin/PedidoAdminController.php` (método `index`) y `app/Http/Controllers/Admin/InventarioController.php` (método `index`).
* **Verificación**: con Playwright se confirmó que las tres pantallas ahora cargan y muestran datos reales (9 pedidos en `/admin/pedidos`, 12 movimientos en `/admin/inventario`, y el historial de un cliente recién registrado mostrando su pedido). También se probó el flujo completo de cambio de estado de un pedido (PENDIENTE → CONFIRMADO → ENVIADO → ENTREGADO), que dependía de poder listar pedidos para llegar al control.

## 17. Registrar CUALQUIER movimiento de inventario rompía con Error 500 (bug más grave de la sesión, no detectado en pasadas anteriores)
* **Estado:** ✅ Corregido y verificado
* **Error message**: `SQLSTATE[23514]: Check violation: 7 ERROR: el nuevo registro para la relación «inventario» viola la restricción «check» «inventario_tipo_check»`
* **Causa/Problema**: La columna heredada `inventario.tipo` tiene un `CHECK` que solo permite `'INGRESO'`/`'SALIDA'` (mayúsculas, en español), pero `Admin/Inventario/Create.vue` envía `'entrada'`/`'salida'` (minúsculas) y `InventarioController@store` los guardaba tal cual. **Esto significa que el formulario de registrar movimiento de inventario nunca funcionó, ni para entradas ni para salidas** — todas las filas reales en la tabla (`INGRESO`/`SALIDA`) vienen de los seeders, no del formulario web.
* **Por qué se me escapó antes**: en la sesión anterior verifiqué "PR-17.2: Registrar entrada" y "PR-17.3: Registrar salida" comprobando solo que no apareciera un error visible y que la última fila de `inventario` existiera — pero esa fila siempre era una preexistente del seeder, nunca la que mi prueba acababa de intentar crear. No comparé el conteo total de filas ni el `stock_actual` antes/después con suficiente cuidado.
* **Bug relacionado encontrado en el mismo flujo**: la técnica "FIFO" del selector tampoco coincidía con el `CHECK` de la columna `tecnica` (solo permite `PEPS`/`UEPS`/`PROMEDIO`, acrónimos en español — PEPS=FIFO, UEPS=LIFO).
* **Solución aplicada**: se agregó un método `tipoLegado()` en `InventarioController` que mapea `entrada`→`INGRESO` y `salida`→`SALIDA` antes del `Inventario::create()`. En `Admin/Inventario/Create.vue` se cambiaron las opciones de técnica a `PEPS`/`UEPS`/`PROMEDIO` (mostrando "FIFO (PEPS)"/"LIFO (UEPS)" como etiqueta visible).
* **Verificación**: con Playwright se registraron movimientos reales de entrada y salida que sí se insertaron en la BD y actualizaron `stock_actual` correctamente (confirmado con consultas directas antes/después).

## 18. Sin validación de stock: una "salida" mayor al stock disponible se aceptaba y dejaba el stock en negativo
* **Estado:** ✅ Corregido y verificado
* **Problema**: Ni la validación del formulario ni el controlador comprobaban que la cantidad de una salida no superara el `stock_actual` del producto. Al probarlo (tras corregir el error #17) con una cantidad exagerada, el movimiento se guardó sin error y dejó el stock de un producto real en **-99989**.
* **Solución aplicada**: en `InventarioController@store`, si `tipo === 'salida'` se compara la cantidad solicitada contra `stock_actual` y se rechaza con un mensaje en español si la excede.
* **Verificación**: se intentó una salida de 999999 unidades sobre un producto con 50 en stock → error "La cantidad de salida (999999) supera el stock disponible (50)." y no se creó el movimiento.
* **Nota de limpieza**: el stock corrupto (-99989) del producto real afectado por la prueba (id=12, "Blazer Ejecutivo Gris") fue restaurado a su valor original (10) con autorización del usuario, y se eliminó el movimiento de prueba que lo causó.

## 19. Plan de cuotas PagoFácil mostraba el QR equivocado al cambiar de pestaña (hallazgo nuevo, sesión PagoFácil)
* **Estado:** ✅ Corregido y verificado con Playwright (clic real)
* **Causa/Problema**: `resources/js/Pages/Pedidos/Pagar.vue` usaba una sola variable `qrPf` compartida entre el bloque "Pago único" y el bloque "Plan de cuotas" de la pasarela PagoFácil. Si el cliente generaba primero el QR de pago único y luego cambiaba a la pestaña "Plan de cuotas", el componente mostraba ese mismo QR disfrazado de "cuota 1", con el texto roto `"QR de la cuota 1 de ."` (el número de cuotas quedaba vacío porque nunca se había seleccionado).
* **Por qué se detectó**: al probar el flujo completo con Playwright (login real, generar QR de pago único, cambiar de pestaña), el texto renderizado no coincidía con lo esperado — confirmó que el QR mostrado era el de la transacción de pago único (`paymentNumber="P{id}-U"`), no uno nuevo de cuota.
* **Solución aplicada**: se separó el estado en `qrUnico`/`qrCuotas` y `mensajeEstadoUnico`/`mensajeEstadoCuotas` (variables independientes, antes mezcladas en `qrPf`/`mensajeEstadoPf`), con una función `verificarEstadoQr(paymentNumber, mensajeRef)` parametrizada para no duplicar lógica.
* **Verificación**: con un pedido de prueba de Bs. 340 (3 cuotas de Bs. 113.33/113.33/113.34), tras la corrección el texto mostró correctamente `"QR de la cuota 1 de 3"`, y generar el QR de pago único ya no contamina la pestaña de cuotas (cada una conserva su propio QR independiente).

## 20. SQL SQLSTATE[42703] en página de reportes — columna `detalle_pedido.subtotal` inexistente
* **Estado:** ✅ Corregido
* **Error message**: `SQLSTATE[42703]: Undefined column: 7 ERROR: no existe la columna detalle_pedido.subtotal`
* **Causa/Problema**: `ReporteController.php` usaba `COALESCE(SUM(detalle_pedido.subtotal), 0)` en dos queries (top productos y ventas por categoría). La tabla `detalle_pedido` heredada del proyecto Java no tiene columna `subtotal` — solo tiene `cantidad` y `precio_unitario`.
* **Solución aplicada**: Reemplazado en ambas queries por `COALESCE(SUM(detalle_pedido.cantidad * detalle_pedido.precio_unitario), 0)`.
* **Archivos**: `app/Http/Controllers/Admin/ReporteController.php` (líneas 52 y 74).

## 21. Botón "Ingresar" invisible en modo luz (tema adultos)
* **Estado:** ✅ Corregido
* **Causa/Problema**: En `tema-adultos.modo-dia`, `--color-primary: #1C1C1C` y `--bg-header: #1C1C1C` son idénticos. El `.btn-outline` usaba `color: var(--color-primary)` y `border: 1.5px solid var(--color-primary)` — botón completamente invisible sobre el header del mismo color.
* **Solución aplicada**: Regla CSS `.topbar .btn-outline { border-color: rgba(255,255,255,0.5); color: white; }` scoped al topbar. Funciona en todos los temas porque el topbar siempre tiene fondo oscuro (`--bg-header`) en las 6 combinaciones tema+modo.
* **Archivos**: `resources/js/Layouts/AppLayout.vue` (sección `<style>`).

## 22. Reset de escala de fuente no persistía entre navegaciones
* **Estado:** ✅ Corregido
* **Causa/Problema**: `resetFuente()` en `useTema.js` actualizaba `fontScale.value = 1` pero nunca llamaba a `localStorage.setItem()`. Al navegar a otra página (nueva instancia del composable), la escala se re-leía del localStorage y volvía al valor anterior.
* **Solución aplicada**: Añadido `localStorage.setItem('fontScale', '1')` dentro de `resetFuente()`.
* **Archivos**: `resources/js/composables/useTema.js` (función `resetFuente`).

---

## Cambios de código realizados — sesión 1
* `database/seeders/TallaSeeder.php`: agregadas tallas de pantalón (28,30,32,34,36,38).
* `database/seeders/ProductoTallaSeeder.php`: **nuevo**, migra `producto.talla` legado → tabla pivot `producto_talla`.
* `database/seeders/DatabaseSeeder.php`: ahora llama también a `HashPasswordsSeeder`, `ProductoTallaSeeder` y `DestacadosSeeder` (existían como archivos sueltos sin ejecutarse).
* Se ejecutó `php artisan db:seed --force` contra la BD remota `db_grupo21sa`.

## Cambios de código realizados — sesión 2 (testing con Playwright)
* `resources/js/Pages/Catalogo/Show.vue`: fix payload vacío al agregar al carrito (error #9).
* `app/Http/Controllers/Cliente/PedidoController.php` + `app/Models/Pedido.php`: quitar columna `total` inexistente (error #10).
* `app/Http/Controllers/Admin/InventarioController.php`: agregar `usuario_id` obligatorio (error #11).
* `app/Http/Controllers/Admin/UsuarioAdminController.php`: mapear rol `admin` a un valor legado válido (error #12).
* `resources/js/Pages/Admin/Menu/Form.vue` (nuevo) + `Admin/Menu/Index.vue`: completar CRUD de menú dinámico (error #13).
* `resources/js/app.js`: fallback global de imágenes rotas (error #14).
* `app/Http/Middleware/HandleInertiaRequests.php` + `resources/js/Layouts/AppLayout.vue`: badge de carrito real (error #15).
* `app/Http/Controllers/Cliente/PedidoController.php`, `app/Http/Controllers/Admin/PedidoAdminController.php`, `app/Http/Controllers/Admin/InventarioController.php`: `orderByDesc('created_at')` → `orderByDesc('fecha')` (error #16).
* `app/Http/Controllers/Admin/InventarioController.php`: mapeo `tipoLegado()` (entrada→INGRESO, salida→SALIDA) y validación de stock disponible en salidas (errores #17 y #18).
* `resources/js/Pages/Admin/Inventario/Create.vue`: opciones de técnica corregidas a PEPS/UEPS/PROMEDIO (error #17).
* Usuarios de prueba creados para testing por rol: `test.admin@local.test`, `test.propietario@local.test`, `test.vendedor@local.test` (password `TestPass123!`) — quedan en la BD para pruebas futuras.

## Metodología de verificación
Todo lo de esta sesión se verificó con **Playwright** (Chromium real controlado por código) haciendo clic, llenando formularios y confirmando los cambios directamente en la base de datos remota — no solo lectura de código. El servidor de desarrollo PHP (`php artisan serve`) es de un solo hilo y cada request tarda ~10s por la latencia hacia la BD remota en Bolivia; esto causó varios falsos negativos iniciales en las pruebas automatizadas (timeouts de mi script, no fallas reales), todos descartados cruzando contra el estado real de la BD.

## Cambios de código realizados — sesión 3 (integración PagoFácil + Playwright)
* `app/Services/PagoFacil/` (nuevo): `PagoFacilClient`, `QrPagoService`, `CuotasPagoFacilService`, `CallbackHandlerService`.
* `app/Http/Controllers/PagoFacilCallbackController.php` (nuevo) + `Cliente/PagoController.php`: métodos `pagoFacilUnico`, `pagoFacilCuotas`, `pagoFacilQrCuota`, `pagoFacilEstado`.
* `app/Console/Commands/SincronizarPagoFacil.php` (nuevo) + `Kernel::schedule()`.
* Migraciones aditivas `2026_06_29_100013_...` (`pago`) y `2026_06_29_100014_...` (`cuota`): columnas `gateway`, `pagofacil_transaction_id`, `pagofacil_status`, `pagofacil_qr_base64`, `pagofacil_expira_en`.
* `resources/js/Pages/Pedidos/Pagar.vue`: selector de pasarela Stripe/PagoFácil + corrección del error #19 (estado de QR compartido entre pestañas).
* `resources/js/Components/QrPagoFacil.vue` (nuevo): componente reutilizable de QR + verificación de estado.
* `resources/js/Pages/Pedidos/Show.vue` y `Admin/Pedidos/Show.vue`: sección de pago ampliada con `gateway` y botón "Pagar esta cuota".
* `storage/app/cacert.pem` (nuevo): bundle CA de Mozilla, necesario porque esta instalación de PHP en Windows no tenía `curl.cainfo` configurado a nivel de sistema (rompía toda llamada HTTPS de `PagoFacilClient` con `cURL error 60`). Resuelto a nivel de código (`PagoFacilClient::http()`), sin tocar `php.ini`.
* Usuario de prueba creado: `qa.pagofacil@test.local` (password `Test1234!`) — queda en la BD para pruebas futuras, mismo patrón que los usuarios `test.*@local.test`.
* Ver `plan_pagos_pagofacil.md` para el detalle completo de arquitectura y hallazgos de sandbox (ID de método de pago confirmado, validación de `callbackUrl` contra dominios públicos, etc.).
