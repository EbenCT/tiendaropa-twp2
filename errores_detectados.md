# Errores Detectados

Este archivo registra los errores detectados durante las pruebas MVP.

## 1. Error en Registro de Usuario (PR-02)
* **Estado:** ❌ Fallido
* **Error message**: `SQLSTATE[42703]: Undefined column: 7 ERROR: no existe la columna «updated_at» en la relación «usuario»`
* **Causa/Problema**: El modelo `User` en Laravel asume que la tabla tiene las columnas `created_at` y `updated_at` (timestamps activados por defecto). Al intentar registrar un usuario o actualizar, falla porque la tabla `usuario` heredada del proyecto Java no tiene estas columnas.
* **Solución requerida**: Agregar `public $timestamps = false;` en `App\Models\User` o crear la migración.

## 2. Error de Navegación en Enlaces (Inertia)
* **Estado:** ❌ Fallido
* **Problema**: Los enlaces en el menú (como "Catálogo") y en las tarjetas de productos no dispararon la navegación al hacer clic en el entorno de pruebas, forzando la navegación directa por URL. Puede ser un problema con cómo están estructurados los tags `Link` de Inertia o los eventos en Vue.

## 3. Error en Seeder de Tallas (Posible causa raíz en DB)
* **Error message**: `SQLSTATE[42P10]: Invalid column reference: 7 ERROR: no hay restricción única o de exclusión que coincida con la especificación ON CONFLICT (SQL: insert into "talla" ...)`
* **Causa/Problema**: Un seeder está usando `upsert` o `updateOrInsert` en la tabla `talla` sin tener un índice único o llave primaria que coincida con la especificación del conflicto, lo que podría estar afectando los datos en la base de datos.

## 4. Contraseñas de usuarios antiguos en texto plano (PR-02)
* **Estado:** ❌ Fallido
* **Problema**: Los usuarios preexistentes en la base de datos (heredados de Java, como `luis.anez@gmail.com`) tienen sus contraseñas en texto plano (ej. `luis2026`). Laravel espera un hash bcrypt, por lo que el login falla para todos ellos.

## 5. Redirección rota al autenticarse / registrarse (PR-02)
* **Estado:** ❌ Fallido
* **Problema**: En `RouteServiceProvider` y en el middleware `RedirectIfAuthenticated`, la constante `HOME` está definida como `/home`. Al registrarse o intentar entrar a una ruta de "guest" estando logueado, redirige a `/home` devolviendo error 404, porque la ruta de inicio es `/`.

## 6. Error Crítico de Ziggy (Bloquea acceso a clientes logueados)
* **Estado:** ❌ Fallido
* **Error message**: `Ziggy error: route 'carrito' is not in the route list`
* **Causa/Problema**: El menú dinámico (tabla `menu_item`) tiene guardada la ruta `carrito` para "Mi Carrito". Sin embargo, en Laravel se llama `carrito.index`. Al loguearse un usuario (nivel >= 1), el middleware inyecta el menú en todas las vistas de Inertia (`AppLayout.vue`), provocando un pantallazo de error general.

## 7. Ausencia del selector de tallas en detalle de producto (PR-10)
* **Estado:** ❌ Fallido
* **Problema**: Al ver los detalles de un producto (`/catalogo/{id}`), no aparece el selector de tallas, a pesar de que el producto podría tener tallas asignadas.

## 8. Secciones faltantes en Home (PR-08)
* **Estado:** ❌ Fallido
* **Problema**: Las secciones "Productos Destacados" y "Nueva Colección" no se muestran en el Home, lo cual podría deberse a que no existen productos con esas banderas activas o porque el código de la vista no los está renderizando correctamente.
