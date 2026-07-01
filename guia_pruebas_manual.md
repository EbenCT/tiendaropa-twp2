# Guía de Pruebas Manuales por Rol — Tienda de Ropa (INF-513)

> Documento para que **tú mismo** pruebes el sistema completo en el navegador, paso a paso, con
> los 3 roles principales (Cliente, Vendedor, Admin) y veas la diferencia real entre lo que cada
> uno puede hacer. Está escrito para seguirse de arriba hacia abajo, sin saltarse pasos.

---

## 0. Credenciales de las 3 cuentas de prueba

Ya están creadas en la base de datos remota (`db_grupo21sa`), listas para usar:

| Rol | Email | Password | Nivel |
|-----|-------|----------|-------|
| **Administrador** | `admin@tiendaropa.test` | `Admin123!` | 4 (control total) |
| **Vendedor** | `vendedor@tiendaropa.test` | `Vendedor123!` | 2 (productos, inventario, pedidos) |
| **Cliente** | `cliente@tiendaropa.test` | `Cliente123!` | 1 (compras propias) |

> El **Admin** automáticamente tiene también todo lo que puede hacer un **Propietario** (nivel 3:
> usuarios, reportes, estadísticas) — por eso con estas 3 cuentas cubres los 4 niveles de rol del
> sistema sin necesitar una cuarta cuenta de "propietario" separada.

---

## 1. Antes de empezar

```bash
# Terminal 1
php artisan serve          # http://127.0.0.1:8000

# Terminal 2
npm run dev                # Vite HMR (opcional si ya hiciste npm run build)
```

Abre **una ventana de navegador en modo incógnito por cada rol** (o 3 navegadores distintos) para
poder tener Cliente, Vendedor y Admin con sesión abierta al mismo tiempo y comparar el menú de cada
uno lado a lado — esa comparación visual es la forma más rápida de "ver la diferencia entre los
tres roles".

**Importante sobre los pagos** (para no pensar que algo está roto):
- **PagoFácil (QR)**: el QR que se genera es real (contra el sandbox de PagoFácil), pero no podrás
  pagarlo de verdad desde una app bancaria boliviana real en este entorno. Usa el botón
  **"Ya pagué, verificar"** — mostrará `"Estado: En Proceso"` (correcto, porque genuinamente no se
  pagó). Si quieres ver el estado `CONFIRMADO` end-to-end, hay un paso opcional al final (sección 6)
  que simula la notificación de pago con un comando.
- **Stripe**: si usas la tarjeta de prueba `4242 4242 4242 4242`, el checkout de Stripe sí la acepta,
  pero el pedido **no pasará automáticamente a CONFIRMADO** porque el webhook de Stripe necesita
  `stripe listen` corriendo en una terminal aparte (no está activo por defecto). Es esperado, no es
  un bug — usa PagoFácil si quieres ver el flujo de checkout sin esa pieza extra.

---

## 2. Parte 0 — Como Invitado (sin iniciar sesión)

Antes de loguearte con ninguna cuenta, prueba esto en una pestaña sin sesión:

- [ ] Abre `http://127.0.0.1:8000/` — debe verse el home con hero, productos destacados, nueva
      colección y promociones.
- [ ] En el header, prueba los 3 botones de **tema** (niños / jóvenes / adultos) — la paleta de
      colores y tipografía cambian en toda la página.
- [ ] Prueba el botón 🌙/☀️ de **modo noche/día** — los fondos cambian.
- [ ] Prueba los botones **A- / A / A+** — el tamaño de letra de toda la página escala.
- [ ] Usa el buscador del header, escribe el nombre de un producto — debe aparecer un dropdown con
      resultados.
- [ ] Prueba el botón hamburger (≡) del header → en pantalla grande colapsa el sidebar a solo iconos; en pantalla pequeña abre/cierra el menú.
- [ ] Ve a `Catálogo`, filtra por categoría/talla/precio — la lista se actualiza.
- [ ] Entra al detalle de un producto — clic en "Agregar al Carrito" o en el corazón 🤍 de favoritos
      → debe **redirigirte a `/login`** (estas acciones requieren cuenta).
- [ ] Intenta entrar directo por URL a `http://127.0.0.1:8000/carrito` → te redirige a `/login`.
- [ ] Intenta entrar a `http://127.0.0.1:8000/admin/productos` → te redirige a `/login`.
- [ ] Revisa el pie de página — debe decir "Esta página ha sido visitada N veces" (sube cada vez
      que recargas).

---

## 3. Parte 1 — Como Cliente (`cliente@tiendaropa.test`)

### 3.1 Login y menú

- [ ] Login en `/login`. Tras entrar, fíjate en el **menú del header**: debe mostrar Inicio,
      Catálogo, Promociones, **Mi Carrito**, **Favoritos**, **Mis Pedidos**, **Métodos de Pago** —
      y **nada de "Gestión"** (eso es para Vendedor+).

### 3.2 Catálogo y producto

- [ ] Ve al `Catálogo`, aplica un filtro combinado (categoría + talla + rango de precio).
- [ ] Entra al detalle de un producto: revisa que se vean varias tallas disponibles, descripción,
      y la métrica de "unidades vendidas" si el producto ya tiene ventas.
- [ ] Clic en **"Agregar al Carrito"** (elige una talla y cantidad antes). Debe confirmar.
- [ ] Clic en el corazón 🤍 → cambia a ❤️ (favorito agregado). Ve a `Favoritos` y confirma que
      aparece ahí.

### 3.3 Carrito y checkout

- [ ] Ve a `Mi Carrito` — revisa cantidades y subtotales, cambia la cantidad de un ítem y verifica
      que el total se recalcula.
- [ ] Clic en **"Realizar Pedido"** → llena Dirección, Teléfono y Referencia (opcional) → confirma.
- [ ] Debes terminar en la pantalla de **pago** del pedido recién creado (`/pedidos/{id}/pagar`).

### 3.4 Pagar con PagoFácil (QR) — pasarela principal

- [ ] En la pantalla de pago, el botón **"QR PagoFácil"** debe estar activo por defecto.
- [ ] Pestaña **"Pago único"** → clic en **"Generar QR para pagar"** → debe aparecer una imagen QR
      real + fecha de vencimiento.
- [ ] Clic en **"Ya pagué, verificar"** → debe mostrar `"Estado: En Proceso"` (correcto sin pago
      real).
- [ ] Cambia a la pestaña **"Plan de cuotas"** → elige 2, 3 o 6 cuotas (si el pedido es muy barato,
      puede que ninguna opción cumpla el mínimo de Bs. 50/cuota — eso es validación correcta, no un
      bug; agrega más productos al carrito para un pedido más grande si quieres probar esto) →
      confirma → debe generar el QR de la **cuota 1** (verás "QR de la cuota 1 de N").

### 3.5 Pagar con Stripe (tarjeta) — pasarela alternativa

- [ ] Clic en el botón **"Tarjeta (Stripe)"** arriba → cambia el bloque.
- [ ] Pestaña "Pago único" → **"Pagar ahora con Stripe"** → te lleva al checkout hospedado de
      Stripe → usa la tarjeta de prueba `4242 4242 4242 4242`, cualquier fecha futura, cualquier
      CVC → completa el pago (recuerda: el pedido no pasará a CONFIRMADO sin `stripe listen`, eso
      es esperado).
- [ ] Pestaña "Plan de cuotas" en Stripe: debe pedirte **registrar un método de pago primero** (ve
      a `Métodos de Pago` → agregar tarjeta vía Stripe Elements con la misma tarjeta de prueba).

### 3.6 Historial y detalle del pedido

- [ ] Ve a `Mis Pedidos` (historial) → debe listar el pedido recién creado con su estado.
- [ ] Entra al detalle del pedido → la sección "Pago" debe mostrar la **pasarela usada**
      (`QR PagoFácil` o `Stripe`) y, si elegiste plan de cuotas, la tabla con cada cuota, su monto,
      fecha de vencimiento y estado. La cuota que vence **hoy** debe tener un botón **"Pagar"**;
      las futuras no.

### 3.7 Verificar que NO puede entrar a zonas de Vendedor/Admin

- [ ] Intenta ir directo por URL a `http://127.0.0.1:8000/admin/productos` → debe **redirigir o dar
      error de acceso** (no debe dejarte entrar).
- [ ] Igual con `/admin/usuarios`, `/admin/reportes`, `/admin/menu`.

---

## 4. Parte 2 — Como Vendedor (`vendedor@tiendaropa.test`)

### 4.1 Login y menú

- [ ] Login. En el header debe aparecer un menú **"Gestión"** (con submenú Productos, Inventario,
      Pedidos) — pero **sin** Usuarios, Reportes, Estadísticas ni Menú Dinámico (eso es para
      Propietario/Admin).

### 4.2 Gestión de productos

- [ ] Ve a `Gestión → Productos` (`/admin/productos`) — tabla con todos los productos.
- [ ] Clic **"Crear"** → llena nombre, descripción, precio, categoría, asigna al menos una talla
      con stock y un catálogo → guarda. Verifica que aparece en la tabla.
- [ ] Clic **"Editar"** en un producto → cambia el precio → guarda → confirma el cambio.
- [ ] Clic en la ⭐ de un producto → se marca/desmarca como **destacado** (toggle inmediato).
- [ ] Edita un producto y marca el checkbox **"Nueva colección"** → guarda.
- [ ] Ve al `Catálogo` público (puedes abrir otra pestaña sin sesión) y confirma que tus cambios de
      destacado/nueva colección se reflejan en el **Home**.
- [ ] Clic en **✕** (eliminar) de un producto de prueba → confirma que se desactiva/elimina.

### 4.3 Inventario

- [ ] Ve a `Gestión → Inventario` (`/admin/inventario`) — lista de movimientos.
- [ ] Clic **"Crear"** → selecciona un producto, tipo **"Entrada"**, cantidad, técnica (Promedio /
      FIFO (PEPS) / LIFO (UEPS)) → guarda. El `stock_actual` del producto debe **aumentar**.
- [ ] Crea otro movimiento tipo **"Salida"** con una cantidad razonable → el stock debe
      **disminuir**.
- [ ] Intenta una **salida mayor al stock disponible** (por ejemplo 99999) → debe rechazarse con un
      mensaje de error en español, sin modificar el stock.

### 4.4 Pedidos (vista de todos los pedidos)

- [ ] Ve a `Gestión → Pedidos` (`/admin/pedidos`) — debe listar pedidos de **todos los clientes**,
      no solo los tuyos.
- [ ] Entra al detalle del pedido que creó la cuenta Cliente en la sección 3 → cambia su estado:
      `PENDIENTE → CONFIRMADO → ENVIADO → ENTREGADO`, paso a paso, guardando cada cambio.
- [ ] En ese mismo detalle, fíjate en la sección **"Pago (solo lectura)"** — debe mostrar la
      pasarela usada y el estado, pero **sin ningún botón de acción** (Vendedor no puede tocar
      pagos, solo verlos).
- [ ] Vuelve a la cuenta de **Cliente** (otra pestaña) y entra a `Mis Pedidos` → confirma que el
      estado que cambiaste como Vendedor ya se refleja ahí.

### 4.5 Verificar que NO puede entrar a zonas de Propietario/Admin

- [ ] Intenta ir directo por URL a `http://127.0.0.1:8000/admin/usuarios` → acceso denegado.
- [ ] Igual con `/admin/reportes`, `/admin/estadisticas`, `/admin/menu`.

---

## 5. Parte 3 — Como Admin (`admin@tiendaropa.test`)

### 5.1 Login y menú

- [ ] Login. El menú debe mostrar **todo**: Gestión (Productos/Inventario/Pedidos) + **Usuarios** +
      **Reportes** + un bloque **"Sistema"** con **Estadísticas** y **Menú Dinámico** — el Admin es
      el único que ve "Menú Dinámico".

### 5.2 Todo lo que ya probaste como Vendedor

- [ ] Repite rápidamente 1-2 acciones de la sección 4 (por ejemplo, editar un producto) para
      confirmar que el Admin también puede hacerlo — el Admin incluye todos los permisos de
      Vendedor.

### 5.3 Gestión de usuarios (exclusivo Propietario/Admin)

- [ ] Ve a `Usuarios` (`/admin/usuarios`) — tabla con todos los usuarios del sistema, incluyendo
      las 3 cuentas de esta guía.
- [ ] Filtra por rol (usa el selector de filtro) → confirma que solo muestra los usuarios de ese
      rol.
- [ ] Clic **"Crear"** → crea un usuario desechable de prueba con rol **Cliente** → guarda →
      confirma que aparece en la tabla.
- [ ] Edita ese usuario de prueba → cámbiale el nombre → guarda.
- [ ] **Importante**: revisa el selector de rol al crear/editar — como Admin puedes asignar
      Cliente, Vendedor o Propietario. El rol **"Admin" nunca aparece como opción en este
      formulario** (ni siquiera para el propio Admin) — es una restricción intencional del sistema,
      no un bug. Si entras con la cuenta Vendedor a este mismo formulario (no debería poder, ver
      4.5), o si pruebas con una cuenta nivel 3/Propietario, notarás que esa cuenta solo puede
      ofrecer hasta "Vendedor" — el límite de roles asignables sube según el nivel de quien crea el
      usuario.
- [ ] Elimina/desactiva el usuario de prueba que creaste, para no dejar basura en la BD.

### 5.4 Reportes y estadísticas (Propietario+/Admin)

- [ ] Ve a `Estadísticas` (`/admin/estadisticas`) — revisa: Top 10 productos más vendidos, pedidos
      por estado, páginas más visitadas.
- [ ] Ve a `Reportes` (`/admin/reportes`) — gráfico de ventas mensuales del año actual, cambia el
      selector de año y confirma que el gráfico se actualiza, revisa el resumen de ganancias
      anuales.

### 5.5 Menú dinámico (exclusivo Admin, nivel 4)

- [ ] Ve a `Menú Dinámico` (`/admin/menu`) — árbol jerárquico de ítems de menú.
- [ ] Clic **"+ Nuevo Ítem"** → crea un ítem de prueba (label, nombre de ruta Ziggy válido, nivel
      mínimo, ítem padre opcional) → guarda → confirma que aparece en el árbol.
- [ ] Pon el nivel mínimo del ítem en **3** (Propietario) → confirma con la cuenta Vendedor (nivel
      2) que **no lo ve** en su menú, y que si bajas el nivel a **1**, sí lo ve cualquier cliente
      logueado.
- [ ] Edita el ítem de prueba, luego elimínalo (✕) para dejar la BD limpia.

### 5.6 Visibilidad de pagos (solo lectura, igual que Vendedor)

- [ ] En `Gestión → Pedidos`, entra al detalle de cualquier pedido pagado → confirma que ves la
      pasarela y el estado, sin botones de acción — el Admin tampoco puede "forzar" un pago, por
      diseño (ningún rol puede marcar un pago como exitoso manualmente, solo la pasarela real o el
      callback/comando programado pueden hacerlo).

---

## 6. (Opcional, avanzado) Ver un pago de PagoFácil como `CONFIRMADO` de verdad

Como no hay forma de pagar el QR real desde este entorno, puedes **simular** la notificación que
PagoFácil enviaría tras un pago exitoso, usando el mismo formato que la documentación oficial
(`InfoPagoFacil/md/ServiciosPagoFacil_QrMaster_v1_1_0.md`):

1. Genera un QR de pago único para un pedido tuyo (sección 3.4) y anota el **ID del pedido**
   (ej. `15`).
2. En una terminal, con el servidor (`php artisan serve`) corriendo, ejecuta:
   ```bash
   curl -X POST http://127.0.0.1:8000/pagofacil/callback \
     -H "Content-Type: application/json" \
     -d '{"PedidoID":"P15-U","Fecha":"2026-06-30","Hora":"10:00:00","MetodoPago":4,"Estado":2}'
   ```
   (cambia `P15-U` por `P{tu_id_de_pedido}-U`).
3. Recarga el detalle del pedido (`/pedidos/15`) → el estado debe pasar a **CONFIRMADO** y la
   sección de pago debe mostrar "Pagado".
4. Para una cuota en vez de pago único, usa `"PedidoID":"P15-C1"` (cuota 1), `"P15-C2"` (cuota 2),
   etc.

> Esto **no** es hacer trampa ni inventar nada — es exactamente el mismo payload que PagoFácil
> enviaría de verdad a `/pagofacil/callback`, solo que lo disparas tú mismo en vez de esperar a que
> llegue desde sus servidores (que no pueden alcanzar tu `localhost` sin un túnel público).

---

## 7. Checklist resumen — diferencias entre roles

| Funcionalidad | Cliente | Vendedor | Admin |
|---|---|---|---|
| Catálogo, carrito, favoritos, pedidos propios | ✅ | — (no es su rol de trabajo, pero técnicamente nivel 2 ≥ nivel 1) | — |
| Pagar (PagoFácil/Stripe), ver historial propio | ✅ | — | — |
| Ver/editar **todos** los pedidos | ❌ | ✅ | ✅ |
| CRUD de productos, tallas, catálogos | ❌ | ✅ | ✅ |
| Inventario (entradas/salidas) | ❌ | ✅ | ✅ |
| Toggle destacado / nueva colección | ❌ | ✅ | ✅ |
| CRUD de usuarios + asignar roles | ❌ | ❌ | ✅ |
| Reportes de ventas / Estadísticas | ❌ | ❌ | ✅ |
| Menú dinámico (crear/editar ítems) | ❌ | ❌ | ✅ (único) |
| Marcar un pago como "pagado" manualmente | ❌ | ❌ | ❌ (nadie puede — solo la pasarela real) |

---

## 8. Limpieza al terminar

Las 3 cuentas (`admin@tiendaropa.test`, `vendedor@tiendaropa.test`, `cliente@tiendaropa.test`)
quedan en la base de datos para que puedas repetir estas pruebas cuando quieras — no es necesario
borrarlas. Si creaste pedidos/productos de prueba que no quieras conservar para la entrega final,
bórralos desde la misma UI de Admin (Usuarios/Productos/Menú) antes de presentar el proyecto.
