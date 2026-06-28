# Fase 13 — Plan de Integración de Pagos con Stripe

> Estado: **plan revisado, pendiente de aprobación final del usuario antes de implementar.**
> Este documento describe cómo se integrará Stripe en la tienda de ropa para cubrir el requisito
> de la rúbrica (registro de métodos de pago, pago único y plan de cuotas por usuario).

---

## 1. Resumen ejecutivo

Hoy el checkout **no cobra nada**: `PedidoController::store()` crea el `Pedido` + `DetallePedido`
+ `Venta` y redirige directo al historial. Las tablas `pago`, `cuota` (legadas del proyecto Java) y
`metodo_pago_usuario` (creada en la migración `100011`, vacía) existen pero no están conectadas a
ningún proveedor de pagos real.

Este plan integra **Stripe en modo TEST permanente**, cubriendo:
- Pago único de un pedido (Stripe Checkout hospedado).
- Plan de cuotas (2, 3 o 6 cuotas sin interés, cobradas con una tarjeta guardada).
- Registro/gestión de métodos de pago guardados por usuario (Stripe Customer + tarjetas).
- Webhooks para confirmar pagos de forma asíncrona y confiable.
- Un código QR (propio, no nativo de Stripe) que apunta a la misma URL de Checkout, para pagar
  desde el celular durante la demo.

---

## 2. Investigación: las dos preguntas clave del usuario

### ¿Existe un "modo prueba con QR" en Stripe?

No como payment method nativo relevante para este proyecto. Stripe sí tiene payment methods
basados en QR (**PayNow** de Singapur, **PromptPay** de Tailandia), pero están atados a esas
monedas/regiones — usarlos aquí obligaría a fingir transacciones en SGD o THB, algo
descontextualizado para una tienda boliviana.

**Lo que sí se implementa**: un QR genérico (librería `qrcode`, generado en el navegador) que
codifica la URL de la **Stripe Checkout Session** ya creada para el pedido. El cliente la escanea
con el celular y paga en el checkout hospedado real de Stripe, en modo test. Es una conveniencia
de presentación, **no** un método de pago distinto — esto se documenta así para no sobre-vender
la funcionalidad.

### ¿Stripe tiene un modo producción donde el dinero se deposite en mi cuenta?

**Bolivia no está en la lista de países soportados por Stripe** (verificado contra la página
oficial `stripe.com/global`), ni para cuentas verificadas ni para payouts. Esto significa que,
hoy, **no es posible** recibir dinero real de Stripe en una cuenta bancaria boliviana sin
constituir una entidad de negocio en un país soportado (ej. una LLC en EE.UU. con cuenta bancaria
allá) — una decisión de negocio fuera del alcance de este código.

Lo que **sí** es viable y es lo que recomiendo para este proyecto académico: crear una cuenta
Stripe gratuita declarando un país soportado (ej. "United States") **únicamente** para obtener
llaves de **modo TEST**. El modo test no requiere verificación de identidad ni cuenta bancaria
real, y funciona idéntico al modo live en todo lo demás (Checkout, PaymentIntents, SetupIntents,
webhooks, tarjetas guardadas). Esto satisface el requisito de la rúbrica ("implementar pagos
electrónicos") sin mover dinero real nunca, lo cual es honesto y apropiado para un proyecto de
curso — nadie quiere cobrar tarjetas reales a un sitio que se entrega a un docente.

**Conclusión**: el alcance de "producción" de esta integración es, honestamente, **modo TEST
permanente**. Si la materia exige explícitamente mover dinero real, eso requeriría salir de Stripe
o conseguir una entidad en un país soportado — fuera del alcance de este plan salvo que se decida
lo contrario.

### Alternativas bolivianas investigadas (a pedido del usuario)

Se investigaron 4 opciones que sí liquidan en BOB a una cuenta boliviana real:

- **PagosNet** (vía EBANX Direct API): en Bolivia funciona como pago en efectivo en puntos
  físicos (no tarjeta/QR directo); requiere "Merchant Signup Form" + contacto comercial con EBANX
  antes de dar credenciales. Sí tiene sandbox, pero solo tras aprobación comercial.
- **Circle.bo**: pasarela 100% boliviana, tarjetas + QR, comisión 3% + Bs.1/transacción,
  "integra en 10 minutos" — pero exige abrir cuenta de comercio real (sin sandbox público
  encontrado) y no menciona soporte de cuotas.
- **dLocal**: liquida directamente en BOB, soporta tarjetas/QR/transferencia/Multipago en
  Bolivia, pero es plataforma enterprise B2B (onboarding por ventas) y su propia documentación
  dice explícitamente que las tarjetas **no soportan pagos recurrentes/cuotas**.
- **PagoFácil Bolivia** (`pagofacil.com.bo` — **distinto** de "PagoFacil.net", que es una empresa
  mexicana no relacionada, cuidado con confundirlas): agregador boliviano de QR interbancario
  (estándar QR Simple del BCB), transferencias y billeteras móviles (Tigo Money, Yape) con ~14
  bancos bolivianos. Tiene un producto "PagoFácil Checkout" para tiendas online. Aprobación de
  comercio en ~2 días (la más rápida de las 4) — pero **no se confirmó** soporte de cuotas/meses
  sin intereses, ni un sandbox público sin aprobación previa, ni documentación técnica de API
  accesible públicamente.
- **API de QR interbancario de un banco boliviano** (ej. "ApiPagosQR" del BCP, o el estándar
  abierto "OpenBCB" del Banco Central de Bolivia): la forma más "real" de cobrar y recibir en una
  cuenta bancaria boliviana propia sin intermediarios — pero exige ser cliente comercial de ese
  banco específico.

**Ninguna de las 5 ofrece** el flujo de "cuenta gratis en 5 minutos + sandbox instantáneo + SDK
público" que tiene Stripe — todas exigen alta comercial real (KYC, contrato, o ser cliente de un
banco específico) no viable con certeza en los ~3 días restantes, y ninguna confirma soporte de
"plan de cuotas" salvo Stripe. **Decisión del usuario: continuar con Stripe en modo TEST para esta
entrega.** PagoFácil Bolivia (u otra de estas opciones) queda documentada como el camino real a
evaluar después de la entrega académica si se quiere cobrar dinero real en una cuenta boliviana.

---

## 3. Hallazgos de la base de datos (importantes — cambian valores concretos del diseño)

Se revisaron los CHECK constraints reales de las tablas `pago`, `cuota`, `pedido` y
`metodo_pago_usuario` en la BD remota antes de fijar valores en el código (siguiendo el mismo
patrón de trampas heredadas ya documentado en `db_analysis.md` para `usuario.rol` e
`inventario.tipo`/`tecnica`):

| Tabla.columna | Constraint real | Implicación |
|---|---|---|
| `pago.modalidad` | CHECK solo permite `'CONTADO'` / `'CREDITO'` | Pago único = `'CONTADO'`, plan de cuotas = `'CREDITO'` (no `'unico'`/`'cuotas'` como se había bocetado inicialmente) |
| `pago.venta_id` | **UNIQUE** | Solo puede existir **una fila `Pago` por venta**, para siempre. No se crea una fila nueva en cada intento — se usa `Pago::updateOrCreate(['venta_id' => ...], [...])`, reutilizando y actualizando la misma fila en cada reintento |
| `cuota.estado` | CHECK solo permite `'PENDIENTE'` / `'PAGADO'` | **No existe** un valor `'FALLIDA'` a nivel de BD. Una cuota cuyo cobro off-session falla **se queda en `'PENDIENTE'`** — el comando programado la reintentará automáticamente al día siguiente sin necesidad de marcarla aparte. La UI puede mostrarla como "vencida" comparando `fecha_vencimiento < hoy` mientras siga `PENDIENTE`, y ofrecer un botón "reintentar ahora" que simplemente repite el intento de cobro |
| `pedido.estado` | CHECK permite `PENDIENTE/CONFIRMADO/ENVIADO/ENTREGADO` | Sin cambios — se confirma que no hace falta tocar el enum, el pedido se queda en `PENDIENTE` durante el pago |
| `metodo_pago_usuario` | Sin CHECK de valores, solo NOT NULL en `activo`/`es_principal` y FK `ON DELETE CASCADE` a `usuario` | Sin sorpresas, el diseño original aplica tal cual |

---

## 4. Decisiones de diseño confirmadas

1. **Plan de cuotas**: selector de **2, 3 o 6 cuotas**, sin interés (recargo 0%). Monto mínimo por
   cuota: Bs. 50 — si el total del pedido no alcanza para que alguna opción cumpla ese mínimo, esa
   opción se oculta del selector.
2. **No** se agrega "marcar como pagado manualmente" en el panel admin (efectivo/transferencia
   fuera de Stripe) — el alcance queda limitado exclusivamente a Stripe.
3. `Pedido.estado` se mantiene en `PENDIENTE` durante todo el proceso de pago. Pasa a `CONFIRMADO`
   solo cuando el webhook de Stripe confirma el pago único o la primera cuota exitosa.
4. Los montos se muestran en bolivianos (Bs.) en toda la interfaz, pero se cobran a Stripe en
   **USD convertidos con una tasa fija configurable** (`STRIPE_BOB_USD_RATE` en `.env`, valor por
   defecto 6.96) — es más honesto que cobrar el mismo número en USD sin conversión.

**Decisiones operativas menores** (valor por defecto recomendado, no bloquean el desarrollo):
- Cuota con cobro fallido: sin un estado "fallida" separado en BD (ver hallazgo de constraint
  arriba) — se reintenta automáticamente al día siguiente vía el comando programado, y además se
  ofrece un botón manual de "reintentar ahora" en la UI del cliente.
- El webhook se construye para funcionar igual en local (con Stripe CLI) y, si el hosting de
  `tecnoweb.org.bo` lo permite, en producción — si no hay HTTPS público fijo o cron real
  disponible ahí, la demo de webhooks/cuotas se puede hacer siempre en local.

---

## 5. Arquitectura técnica

### 5.1 Base de datos y modelos

- **Migración nueva** `database/migrations/2026_06_18_100012_add_stripe_columns_to_pago_table.php`
  (`Schema::table('pago', ...)`, ALTER aditivo — nunca `create`, `pago` es tabla legada con datos
  reales): agrega `stripe_payment_intent_id` (varchar 255, nullable), `stripe_status` (varchar 50,
  nullable — se persiste el valor crudo del `PaymentIntent.status` de Stripe sin traducir),
  `metodo` (varchar 30, nullable — valores de aplicación: `tarjeta_unico` / `tarjeta_cuotas`).
  `app/Models/Pago.php` ya declara estos 3 campos en `$fillable` (el modelo se adelantó a la
  migración) — no requiere cambios.
- **Reescribir `app/Models/MetodoPagoUsuario.php`** (hoy es un stub vacío): `$table =
  'metodo_pago_usuario'`, `$timestamps = true` (la migración `100011` sí define
  `created_at`/`updated_at`, a diferencia de las tablas legadas), `$fillable` con los 7 campos,
  `$casts` para `es_principal`/`activo` como boolean, y `belongsTo(User::class, 'usuario_id')` (el
  lado inverso `User::metodosPago()` ya existe en `app/Models/User.php:83-86`).

### 5.2 Dependencias y configuración

- `composer require stripe/stripe-php`, `npm install @stripe/stripe-js qrcode`.
- `config/services.php`: bloque `'stripe' => ['key', 'secret', 'webhook_secret', 'currency' =>
  'usd', 'bob_usd_rate' => 6.96]`, mismo patrón que `mailgun`/`postmark` ya presentes.
- `.env.example`/`.env`: `STRIPE_KEY`, `STRIPE_SECRET`, `STRIPE_WEBHOOK_SECRET`,
  `STRIPE_CURRENCY=usd`, `STRIPE_BOB_USD_RATE=6.96`.
- `AppServiceProvider::register()`: singleton de `\Stripe\StripeClient`, inyectable por type-hint.

### 5.3 Capa de servicio Stripe (`app/Services/Stripe/`)

Ningún controlador llama al SDK de Stripe directamente:

- **`PagoUnicoService`**: crea una Stripe Checkout Session hospedada (modo `payment`).
  `line_items` derivados de `$pedido->detalles`, convertidos a centavos USD. `metadata` incluye
  `pedido_id`/`venta_id` (única forma de correlacionar la sesión con las filas locales desde el
  webhook). `Pago::updateOrCreate(['venta_id' => $venta->id], ['modalidad' => 'CONTADO', ...])`
  antes de llamar a Stripe (recordar la restricción UNIQUE de `venta_id`).
- **`CuotasService`**: requiere un método de pago ya guardado (no acepta tarjeta nueva en el
  momento). Calcula `monto_cuota = total/N` con ajuste de redondeo en la última cuota, crea N
  filas `Cuota` (`fecha_vencimiento`: cuota 1 = hoy, cuota i = hoy + (i-1) meses), cobra la cuota 1
  síncronamente. El `Pago` padre usa `modalidad = 'CREDITO'`. El webhook es la única fuente de
  verdad de éxito/fracaso (necesario por 3D Secure).
- **`MetodoPagoService`**: Customer de Stripe por usuario (reutilizado para todas sus tarjetas) +
  SetupIntent (`usage: 'off_session'`) + soft-delete (`activo=false`, nunca DELETE real).
- **`WebhookHandlerService`**: único lugar que interpreta eventos de Stripe y actualiza `Pago`,
  `Cuota`, `Pedido`, `MetodoPagoUsuario`. Maneja `checkout.session.completed`,
  `payment_intent.succeeded`, `payment_intent.payment_failed`, `setup_intent.succeeded`. Transición
  `Pedido` de `PENDIENTE` a `CONFIRMADO` vía un método nuevo `Pedido::confirmarPorPago()` (solo si
  `estado === 'PENDIENTE'`, para no pisar un estado más avanzado puesto manualmente).

### 5.4 Comando programado

`app/Console/Commands/CobrarCuotasVencidas.php` (`php artisan pagos:cobrar-cuotas`, primer comando
custom del proyecto): busca `Cuota::where('estado','PENDIENTE')->where('fecha_vencimiento','<=',
today())`, cobra off-session usando el método de pago principal del usuario. Si falla, la cuota
simplemente sigue `PENDIENTE` (ver hallazgo de constraint) y se reintentará el día siguiente.
Registrado en `Kernel::schedule()` con `->daily()`.

### 5.5 Rutas y controladores HTTP

- `app/Http/Controllers/Cliente/PagoController.php`: `GET /pedidos/{id}/pagar`,
  `POST /pedidos/{id}/pagar/unico` (responde JSON `{url}`), `POST /pedidos/{id}/pagar/cuotas`,
  `GET /pedidos/pago/exito` y `/cancelado`.
- `app/Http/Controllers/Cliente/MetodoPagoController.php`: índice, `setup-intent` (JSON puro),
  marcar-principal, eliminar.
- `app/Http/Controllers/StripeWebhookController.php`: verifica firma, delega al
  `WebhookHandlerService`, responde siempre 200 salvo firma inválida.
- `VerifyCsrfToken::$except` agrega `'stripe/webhook'`.
- Rutas de pago/métodos dentro del grupo `auth` existente; el webhook va en rutas públicas, sin
  middleware de auth, protegido solo por verificación de firma.

### 5.6 Frontend (Vue)

- **`Pedidos/Pagar.vue`** (nuevo): toggle pago único / cuotas, botón + QR para pago único,
  selector de cuotas (2/3/6, ocultando las que violen el mínimo Bs.50) + tarjeta guardada para
  cuotas.
- **`MetodosPago/Index.vue`** (nuevo): lista de tarjetas guardadas, marcar-principal, eliminar,
  agregar tarjeta vía Stripe Elements.
- **`Pedidos/Create.vue`**: sin cambios de código — solo cambia el redirect del backend.
- **`Pedidos/Show.vue`**: sección "Pago" con badge de estado, tabla de cuotas si aplica, botón
  "Pagar ahora"/"Reintentar".
- **`Pedidos/Historial.vue`**: badge de estado de pago por fila. *Nota aparte detectada al leer el
  archivo, fuera del alcance de Stripe pero trivial de corregir de paso*: usa `pedido.created_at`,
  que siempre es `null` porque `Pedido` tiene `$timestamps=false` (usa `fecha`) — se corrige a
  `pedido.fecha` ya que se va a tocar este archivo igual.
- **`Admin/Pedidos/Show.vue`**: misma sección de pago/cuotas en solo lectura (sin botones, dado
  que se descartó el "marcar pagado manual"); usa `pedido.venta.total` en vez de `pedido.total`
  (que no existe como columna real).
- `HandleInertiaRequests::share()`: agrega `'stripe' => ['publishableKey' => ...]`.
- `MenuItemSeeder`: agrega ítem "Métodos de Pago" → `metodos-pago.index`, nivel mínimo 1.

### 5.7 Cambios puntuales en controladores existentes

- `Cliente/PedidoController.php::store()`: el redirect final cambia de `pedidos.historial` a
  `pedidos.pagar` con el id del pedido recién creado. Nada más del método cambia.
- `Cliente/PedidoController.php::show()`/`historial()` y `Admin/PedidoAdminController.php::show()`:
  ampliar eager-loading de `'venta.pagos'` a `'venta.pagos.cuotas'`.

---

## 6. Secuencia de implementación (incremental y verificable)

1. Migración + modelo `MetodoPagoUsuario` + dependencias + config + singleton.
2. Webhook esqueleto (solo loguea el evento) + exclusión CSRF — verificar con Stripe CLI.
3. Pago único end-to-end (servicio + controlador + `Pagar.vue` sin QR + redirect + webhook real).
4. QR en `Pagar.vue`.
5. Métodos de pago guardados (servicio + controlador + UI + webhook + menú).
6. Plan de cuotas (servicio + UI + webhook + sección en `Pedidos/Show.vue`).
7. Comando programado + registro en `Kernel`.
8. Visibilidad admin de solo lectura.
9. Actualizar `implementation_plan.md`, `db_analysis.md` y agregar `PR-26` a `plan_de_pruebas.md`.

---

## 7. Plan de verificación

- **Stripe CLI**: `stripe listen --forward-to localhost:8000/stripe/webhook` corriendo en una
  terminal separada durante toda la sesión de pruebas.
- **Tarjetas de prueba oficiales**: `4242 4242 4242 4242` (éxito directo), `4000 0027 6000 3184`
  (fuerza 3D Secure), `4000 0000 0000 9995` (fondos insuficientes).
- **Casos a cubrir** (futura sección `PR-26` de `plan_de_pruebas.md`): pago único exitoso, pago
  con 3DS, pago cancelado, QR escaneado desde móvil, guardar/marcar-principal/eliminar método de
  pago, plan de cuotas completo, cobro automático de cuota vencida, cuota fallida + reintento,
  visibilidad admin de solo lectura.

---

## 8. Riesgos a tener presentes

- Esta integración **nunca podrá mover dinero real a una cuenta bancaria boliviana** mientras
  Bolivia no sea país soportado por Stripe — el alcance de "producción" es honestamente "modo TEST
  permanente". Si la rúbrica exige pagos reales literalmente, aclarar con el docente antes de la
  entrega.
- El QR no es un payment method nativo de Stripe — es un atajo de UX hacia el mismo Checkout.
- El webhook secret de `stripe listen` (CLI local) es distinto al de un endpoint registrado en el
  Dashboard — si se despliega a `tecnoweb.org.bo` con webhook público real, hay que generar y
  configurar ese segundo secret por separado.
- El comando `pagos:cobrar-cuotas` depende de que algo dispare `schedule:run` periódicamente; en
  el hosting compartido esto no está garantizado — para la demo puede dispararse manualmente.

---

## 9. Estado actual de avance

**Implementación completa** (decisión del usuario: continuar con Stripe en modo TEST, ver sección
2 para el detalle de las alternativas bolivianas investigadas). Se verificó código (`php -l`,
`npm run build`, `route:list`, `schedule:list`, resolución del singleton vía `tinker`) pero **no**
end-to-end en navegador todavía.

**Avance real al 2026-06-28**:
- [x] Cuenta Stripe creada y `STRIPE_KEY`/`STRIPE_SECRET` reales puestas en `.env` (modo test).
- [x] Stripe CLI v1.43.2 instalado (`winget install Stripe.StripeCli`).
- [ ] Falta correr `stripe login` (autenticar el CLI con la cuenta Stripe, requiere navegador) y
      `stripe listen --forward-to localhost:8000/stripe/webhook` para obtener el `whsec_...` real
      y pegarlo en `STRIPE_WEBHOOK_SECRET` (sigue con el placeholder `whsec_xxxxxxxxxxxx`).
- [ ] Sin el paso anterior, ningún pago se reflejará como "succeeded" en la app (el webhook
      rechazará la firma) — es bloqueante para probar cualquier caso de PR-26.
- [ ] Pendiente: con `php artisan serve` + `npm run dev` + `stripe listen` corriendo, verificar
      los 10 casos de PR-26 en `plan_de_pruebas.md` en el navegador.

Ver `implementation_plan.md` y `task.md` para el detalle de archivos creados/modificados.
