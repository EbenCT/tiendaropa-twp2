# Análisis de la Base de Datos Existente – db_grupo21sa

## Tablas encontradas (13 en total)

| Tabla | Filas | Estado para el proyecto nuevo |
|-------|-------|-------------------------------|
| `catalogo` | 3 | ✅ Reutilizar (catálogos = secciones tipo hombre/mujer/niños) |
| `catalogo_producto` | 11 | ✅ Reutilizar (pivot catálogo ↔ producto) |
| `categoria` | 15 | ✅ Reutilizar (subcategorías de productos) |
| `cuota` | 7 | ✅ Reutilizar + extender (plan de pagos, vincular a Stripe) |
| `detalle_pedido` | 16 | ✅ Reutilizar |
| `inventario` | 12 | ✅ Reutilizar (movimientos de stock) |
| `pago` | 4 | ✅ Reutilizar + agregar columna stripe_payment_intent_id |
| `pedido` | 6 | ✅ Reutilizar |
| `producto` | 20 | ✅ Reutilizar + ajustar (ver notas) |
| `producto_promocion` | 6 | ✅ Reutilizar |
| `promocion` | 3 | ✅ Reutilizar |
| `usuario` | 15 | ⚠️ Adaptar (cambiar campo `rol` texto → FK a tabla roles) |
| `venta` | 4 | ✅ Reutilizar |

## Tablas que HAY QUE CREAR (nuevas)

| Tabla | Por qué |
|-------|---------|
| `roles` | El campo `rol` en `usuario` es varchar libre, necesita tabla normalizada con 4 roles |
| `carrito_item` | No existe carrito persistente en BD |
| `favorito` | No existe |
| `menu_item` | Menú dinámico por rol |
| `page_visit` | Contador de visitas por página |
| `talla` | Las tallas están en `producto.talla` como varchar libre (ej: "S,M,L") — necesita normalizar |
| `producto_talla` | Pivot producto ↔ talla con stock por talla |
| `metodo_pago_usuario` | Métodos de pago guardados (Stripe) |

## Problemas / Ajustes en tablas existentes

### `usuario` — ADAPTAR
- Campo `rol` es `varchar(20)` con valores libres → migrar a `role_id` FK
- Falta: `email_verified_at`, `remember_token` (requeridos por Laravel Auth)
- Nombres de columnas en español → Laravel usa `users` en inglés por defecto
- **Decisión:** Crear tabla `users` de Laravel y migrar datos de `usuario`, o renombrar y adaptar

### `producto` — AJUSTAR columnas
- `talla` es `varchar(50)` (puede tener "S,M,L" como string) → normalizar a tabla `talla` + pivot
- `imagen_url` es solo una URL → el nuevo sistema necesita múltiples imágenes (nueva tabla `producto_imagen`)
- Falta columna: `destacado` (boolean) para home

### `inventario` — REVISAR
- Tiene `tipo` (varchar 10), `tecnica` (varchar 20) → posiblemente para métodos de valuación (FIFO/PROMEDIO)
- Se puede reutilizar tal como está

### `pago` — EXTENDER
- Agregar: `stripe_payment_intent_id`, `stripe_status`, `metodo` (tarjeta/transferencia)
- `venta_id` ya existe como FK

## Resumen de lo que NO HAY QUE CREAR desde cero

Las siguientes tablas tienen datos reales y se reutilizan directamente:
- `catalogo`, `catalogo_producto`, `categoria`
- `producto` (con ajustes menores)
- `pedido`, `detalle_pedido`, `venta`
- `pago`, `cuota`
- `inventario`
- `promocion`, `producto_promocion`
- `usuario` (con migración del campo rol)
