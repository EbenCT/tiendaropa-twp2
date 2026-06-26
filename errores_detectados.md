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

