# Análisis de la Base de Datos – db_grupo21sa

## Resumen

Base de datos PostgreSQL remota en `db.tecnoweb.org.bo:5432`.  
Total: **21 tablas** (13 originales del proyecto Java + 8 nuevas creadas por migraciones aditivas).

---

## Tablas originales del proyecto Java (13)

| Tabla | Filas | Estado | Modelo Eloquent |
|-------|-------|--------|-----------------|
| `catalogo` | 3 | ✅ Reutilizada sin cambios | `Catalogo` |
| `catalogo_producto` | 11 | ✅ Reutilizada (pivot catálogo ↔ producto) | — (relación M:M) |
| `categoria` | 15 | ✅ Reutilizada sin cambios | `Categoria` |
| `cuota` | 7 | ✅ Reutilizada (plan de pagos) | `Cuota` |
| `detalle_pedido` | 16 | ✅ Reutilizada sin cambios | `DetallePedido` |
| `inventario` | 12 | ✅ Reutilizada (movimientos stock, técnica FIFO/Promedio) | `Inventario` |
| `pago` | 4 | ✅ Reutilizada (columnas Stripe pendientes) | `Pago` |
| `pedido` | 6 | ✅ Reutilizada sin cambios | `Pedido` |
| `producto` | 20 | ✅ Adaptada (+`destacado`, +`es_nueva_coleccion`) | `Producto` |
| `producto_promocion` | 6 | ✅ Reutilizada (pivot producto ↔ promoción) | — (relación M:M) |
| `promocion` | 3 | ✅ Reutilizada sin cambios | `Promocion` |
| `usuario` | 15 | ✅ Adaptada (+`email_verified_at`, +`remember_token`, +`rol_nuevo`) | `User` |
| `venta` | 4 | ✅ Reutilizada sin cambios | `Venta` |

### Adaptaciones realizadas

#### `usuario`
- **Agregadas:** `email_verified_at` (timestamp nullable), `remember_token` (varchar 100 nullable), `rol_nuevo` (varchar referenciando slug de rol)
- **Mantenidas:** `id`, `ci`, `nombre`, `apellido`, `email`, `telefono`, `password`, `activo`
- **Depreciada:** columna `rol` original (varchar libre) → reemplazada por `rol_nuevo`
- Laravel usa `$table = 'usuario'` en el modelo `User.php`

#### `producto`
- **Agregadas:** `destacado` (boolean, default false), `es_nueva_coleccion` (boolean, default false)
- **Mantenidas:** `id`, `categoria_id`, `nombre`, `descripcion`, `precio_unitario`, `talla`, `imagen_url`, `qr_code`, `stock_actual`, `activo`

---

## Tablas nuevas creadas (8)

| Tabla | Filas | Propósito | Migración |
|---|---|---|---|
| `roles` | 4 | Roles normalizados con nivel numérico | `100001_create_roles_table` |
| `talla` | 21 | Catálogo de tallas (adulto, niño, calzado) | `100004_create_talla_table` |
| `producto_talla` | — | Pivot producto ↔ talla con stock por talla | `100005_create_producto_talla_table` |
| `producto_imagen` | — | Múltiples imágenes por producto (url, orden, principal) | `100006_create_producto_imagen_table` |
| `carrito_item` | — | Carrito persistente por usuario | `100007_create_carrito_item_table` |
| `favorito` | — | Productos favoritos por usuario (UNIQUE usuario+producto) | `100008_create_favorito_table` |
| `menu_item` | 15 | Menú dinámico con jerarquía padre-hijo y nivel mínimo | `100009_create_menu_item_table` |
| `page_visit` | — | Contador de visitas por URL (UNIQUE page_url) | `100010_create_page_visit_table` |
| `metodo_pago_usuario` | 0 | Métodos de pago Stripe (preparada, vacía) | `100011_create_metodo_pago_usuario_table` |

---

## Estructura de tabla `roles`

| slug | nombre | nivel |
|------|--------|-------|
| `admin` | Administrador | 4 |
| `propietario` | Propietario | 3 |
| `vendedor` | Vendedor | 2 |
| `cliente` | Cliente | 1 |

---

## Estructura de tabla `menu_item` (15 ítems)

| ID | Label | Ruta | Nivel mín | Parent |
|----|-------|------|-----------|--------|
| 1 | Inicio | `home` | 0 | — |
| 2 | Catálogo | `catalogo` | 0 | — |
| 3 | Promociones | `promociones` | 0 | — |
| 4 | Mi Carrito | `carrito` | 1 | — |
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

---

## Pendiente para tabla `pago` (Fase 13 – Stripe)

```
AGREGAR:  stripe_payment_intent_id (varchar, nullable)
AGREGAR:  stripe_status (varchar, nullable)
AGREGAR:  metodo (varchar: tarjeta/transferencia, nullable)
```
