# Fase 14 — Integración de Pagos con PagoFácil Bolivia

> Estado: **implementación completa y verificada end-to-end contra el sandbox real de PagoFácil**
> (2026-06-29). Este documento describe cómo se integró PagoFácil (pasarela boliviana, requisito
> explícito del docente) como pasarela **principal**, manteniendo Stripe (Fase 13,
> `plan_pagos_stripe.md`) como pasarela secundaria ya funcional. Cubre pago único vía QR y plan de
> cuotas vía QR (sin métodos de pago guardados, PagoFácil no los soporta).

---

## 1. Resumen ejecutivo

Stripe (Fase 13) quedó funcionalmente completo pero **nunca podrá mover dinero real a una cuenta
boliviana** (Bolivia no es país soportado). El docente pidió integrar **PagoFácil Bolivia**
(`pagofacil.com.bo`), pasarela local que sí liquida en BOB. Ya se cuenta con:

- Documentación técnica completa en `InfoPagoFacil/md/` (3 documentos + colección Postman).
- Credenciales de **sandbox/pruebas** en `InfoPagoFacil/md/Credentials pagofacil test.txt`, ya
  cargadas en `.env` (`PAGOFACIL_*`, ver sección 5.2).

Decisiones ya confirmadas con el usuario:

1. **Mecanismo base: API MasterQR v2** (`/login`, `/generate-qr`, `/query-transaction`, callback) —
   no el "Botón de Pago CheckOut" (doc más vieja, sin endpoint de consulta de estado confirmado).
2. **Dual gateway**: Stripe se mantiene tal cual (Fase 13 intacta), PagoFácil se agrega como pasarela
   **principal** con un selector visible en la UI de pago.
3. **Plan de cuotas sin cobro automático**: PagoFácil no tiene tarjetas guardadas ni cobro
   off-session. Cada cuota genera **su propio QR** cuando vence; el cliente la paga manualmente. El
   comando programado pasa de "cobrar" (Stripe) a "pre-generar QR + sincronizar estado" (PagoFácil).

---

## 2. Hallazgos clave de la documentación (`InfoPagoFacil/md/`)

- **URL_BASE**: `https://masterqr.pagofacil.com.bo/api/services/v2`.
- **Autenticación**: `POST /login` con headers `tcTokenService` / `tcTokenSecret` → devuelve
  `accessToken` (JWT) + `expiresInMinutes`. Todas las llamadas posteriores usan
  `Authorization: Bearer <accessToken>`. **El token expira** — hay que cachearlo y renovarlo.
- **`POST /list-enabled-services`**: lista los métodos QR habilitados para el comercio
  (`paymentMethodId`, moneda, límites por transacción/día). **Paso obligatorio antes de cualquier
  cobro real**: el Postman de ejemplo usa `paymentMethodId: 34`, pero ese es un valor de ejemplo de
  *otra* cuenta — hay que confirmar el ID real habilitado para nuestras credenciales.
- **`POST /generate-qr`**: crea la transacción y devuelve `qrBase64` (imagen QR), `transactionId`
  (de PagoFácil), y `checkoutUrl`/`deepLink`/`universalUrl` opcionales. Parámetros relevantes:
  `paymentMethod`, `clientName`, `documentType`, `documentId`, `phoneNumber`, `email`,
  `paymentNumber` (**nuestro identificador de transacción, libre, clave para correlacionar el
  callback**), `amount`, `currency` (2 = BOB), `clientCode`, `callbackUrl`, `orderDetail[]`.
- **`POST /query-transaction`**: consulta estado por `pagofacilTransactionId` o
  `companyTransactionId` (nuestro `paymentNumber`). Devuelve `paymentStatus` (código numérico, **no
  documentado explícitamente para QR** — ver riesgos).
- **Callback**: PagoFácil hace `POST` a la `callbackUrl` registrada con
  `{ PedidoID, Fecha, Hora, MetodoPago, Estado }`, donde `PedidoID` es justamente nuestro
  `paymentNumber`. Hay que responder siempre `200 OK` con
  `{ "error":0, "status":1, "message":"...", "values":true }`.
- **Códigos de `Estado`/`MetodoPago`**: solo están documentados en el doc del *Botón de Pago
  CheckOut* (`Estado`: 1=PENDIENTE, 2=PAGADO, 3=REVERTIDO, 4=ANULADO; `MetodoPago`: 1=TigoMoney,
  2=Punto PagoFacil, 3=Tarjeta, 4=Transferencia QR, 5=BCP, 6=LinkSer, 7=Soli Pagos). **No está
  confirmado que la API MasterQR use los mismos códigos** — se trata como un supuesto a verificar
  empíricamente en sandbox (ver sección 7).

---

## 3. Decisiones de diseño

1. **`paymentNumber` (nuestro identificador)** se construye de forma parseable para que el callback
   pueda ubicar la fila exacta sin depender solo del `pagofacilTransactionId`:
   - Pago único: `P{pedido_id}-U`
   - Cuota individual: `P{pedido_id}-C{num_cuota}`
2. **No hay "métodos de pago guardados" en PagoFácil** — la tabla `metodo_pago_usuario` sigue siendo
   exclusiva de Stripe (sin cambios). El selector de pasarela en la UI deja esto claro: "Tarjeta
   (Stripe)" ofrece guardar tarjeta; "QR (PagoFácil)" no.
3. **Plan de cuotas QR**: al crear el plan se genera de inmediato el QR de la cuota 1. Las cuotas
   siguientes generan su QR cuando el cliente entra a pagarlas (botón "Pagar esta cuota" en
   `Pedidos/Show.vue`) o de forma anticipada vía el comando programado (sección 5.4), nunca se
   cobran automáticamente.
4. **Problema de callback en local**: PagoFácil necesita una URL pública para notificar. En
   `php artisan serve` (localhost) eso no es alcanzable sin un túnel. Mitigación de dos capas:
   - Recomendado para pruebas: túnel con `ngrok http 8000` (o similar), registrar esa URL pública
     como `callbackUrl`.
   - Red de seguridad siempre activa (sirve también si el callback falla en producción): botón "Ya
     pagué, verificar" en la UI que dispara `query-transaction` manualmente, más el comando
     programado que hace lo mismo de forma periódica para transacciones pendientes.
5. **`gateway` como columna nueva** en `pago` (`'stripe'` / `'pagofacil'`) — necesaria porque ahora
   una fila `Pago` puede venir de cualquiera de las dos pasarelas y la UI/admin necesita saber cuál
   para decidir qué mostrar (QR vs tarjeta).

---

## 4. Hallazgos de la base de datos (a verificar antes de programar, mismo patrón que Fase 13)

`pago.venta_id` sigue siendo **UNIQUE** (Fase 13) — el diseño de PagoFácil reutiliza
`Pago::updateOrCreate(['venta_id' => ...], [...])` igual que Stripe. `cuota.estado` sigue limitado a
`'PENDIENTE'`/`'PAGADO'` — coincide exactamente con el modelo "sin estado fallido, reintento
manual/automático" ya usado en Stripe, no se necesita ningún cambio de constraint.

**Pendiente de confirmar al implementar** (correr el mismo query de `pg_get_constraintdef` que en
`db_analysis.md`): que las columnas nuevas de `pago`/`cuota` (todas `nullable`, ALTER aditivo) no
choquen con ningún índice o constraint existente — bajo riesgo, mismo patrón que las columnas
`stripe_*` ya aplicadas sin problema.

---

## 5. Arquitectura técnica

### 5.1 Base de datos y modelos

- **Migración** `database/migrations/2026_06_29_100013_add_pagofacil_columns_to_pago_table.php`
  (`Schema::table('pago', ...)`): agrega `gateway` (varchar 20, nullable — `'stripe'`/`'pagofacil'`),
  `pagofacil_transaction_id` (varchar 100, nullable), `pagofacil_status` (varchar 60, nullable, valor
  crudo de `paymentStatus`/`Estado`), `pagofacil_qr_base64` (text, nullable), `pagofacil_expira_en`
  (timestamp, nullable).
- **Migración** `database/migrations/2026_06_29_100014_add_pagofacil_columns_to_cuota_table.php`
  (`Schema::table('cuota', ...)`): mismas 4 columnas `pagofacil_*` que en `pago` (cada cuota QR es su
  propia transacción independiente).
- `app/Models/Pago.php`: agregar las 5 columnas a `$fillable`, cast `pagofacil_expira_en` como
  `datetime`.
- `app/Models/Cuota.php`: agregar las 4 columnas `pagofacil_*` a `$fillable`, mismo cast.

### 5.2 Dependencias y configuración

- **Sin SDK nuevo** — PagoFácil no publica un paquete Composer oficial. Se usa el cliente HTTP que
  ya trae Laravel (`Illuminate\Support\Facades\Http`, Guzzle por debajo), sin instalar nada.
- `.env` (**ya completado** con las credenciales de `InfoPagoFacil/md/Credentials pagofacil
  test.txt`):
  ```
  PAGOFACIL_URL=https://masterqr.pagofacil.com.bo/api/services/v2
  PAGOFACIL_COMMERCE_ID=...        (del producto CheckOut, no usado por el flujo QR, queda documentado)
  PAGOFACIL_TOKEN_SERVICE=...
  PAGOFACIL_TOKEN_SECRET=...
  PAGOFACIL_PAYMENT_METHOD_ID=34   (valor de ejemplo del Postman — VERIFICAR en el paso 1 de implementación)
  PAGOFACIL_CURRENCY=2             (2 = BOB según la doc)
  ```
  `.env.example` actualizado en paralelo con los mismos nombres vacíos (ya hecho).
- `config/services.php`: nuevo bloque `'pagofacil' => ['url', 'commerce_id', 'token_service',
  'token_secret', 'payment_method_id', 'currency']`, mismo patrón que `'stripe'`.

### 5.3 Capa de servicio (`app/Services/PagoFacil/`)

- **`PagoFacilClient.php`**: cliente HTTP de bajo nivel.
  - `login(): string` — llama `/login`, cachea el `accessToken` (Laravel `Cache`, TTL =
    `expiresInMinutes - 1` minuto de margen) para no autenticar en cada request.
  - `listEnabledServices(): array` — wrapper de `/list-enabled-services`.
  - `generateQr(array $payload): array` — wrapper de `/generate-qr`, reintenta una vez con login
    forzado si la respuesta es 401 (token expirado a mitad de sesión).
  - `queryTransaction(string $paymentNumber): array` — wrapper de `/query-transaction` por
    `companyTransactionId`.
- **`QrPagoService.php`**: pago único.
  - `generarQrPagoUnico(Pedido $pedido, Venta $venta): array` — `Pago::updateOrCreate(['venta_id' =>
    ...], ['modalidad' => 'CONTADO', 'gateway' => 'pagofacil', 'metodo' => 'qr_pagofacil', ...])`,
    construye `paymentNumber = "P{$pedido->id}-U"`, llama `generateQr`, persiste
    `pagofacil_transaction_id`/`pagofacil_qr_base64`/`pagofacil_expira_en` en el `Pago`, retorna el
    payload para el frontend.
  - `generarQrCuota(Cuota $cuota, Pedido $pedido): array` — mismo patrón pero sobre una fila
    `Cuota` (recibe también el `Pedido` para los datos del cliente), con
    `paymentNumber = "P{$pedido->id}-C{$cuota->num_cuota}"`.
  - `consultarEstado(string $paymentNumber): array` — wrapper fino sobre `queryTransaction`, usado
    tanto por el botón "Ya pagué" como por el comando programado.
- **`CuotasPagoFacilService.php`**: plan de cuotas.
  - `crearPlanCuotas(Pedido $pedido, Venta $venta, int $numCuotas): array` — mismo cálculo de montos
    que `CuotasService` de Stripe (mínimo Bs. 50/cuota, ajuste de redondeo en la última), crea N
    filas `Cuota`, genera el QR de la cuota 1 de inmediato vía `QrPagoService::generarQrCuota` y
    retorna ese payload de QR (no la fila `Pago` — el controlador no necesita el modelo, solo el
    JSON para el frontend).
- **`CallbackHandlerService.php`**: única fuente de verdad de éxito/fracaso vía callback.
  - Parsea `PedidoID` (= nuestro `paymentNumber`) con una regex `/^P(\d+)-(U|C(\d+))$/` para
    distinguir pago único vs cuota número N.
  - Pago único: actualiza `Pago.pagofacil_status`, `fecha_pago`, llama
    `Pedido::confirmarPorPago()` si el estado mapea a "pagado".
  - Cuota: actualiza `Cuota.estado = 'PAGADO'` + `fecha_pago_real`; si es la cuota 1, también
    confirma el pedido.
  - Mapeo de `Estado` → interno: se implementa con una constante `ESTADO_PAGADO = 2` (de la doc del
    Botón de Pago, **asumido también válido para MasterQR** hasta verificar en sandbox) y se loguea
    el valor crudo siempre, para poder ajustar el mapeo rápido si la prueba real muestra otro código.

### 5.4 Comando programado

`app/Console/Commands/SincronizarPagoFacil.php` (`php artisan pagos:sincronizar-pagofacil`):

1. Cuotas `PENDIENTE` con `pago.gateway = 'pagofacil'`, `fecha_vencimiento <= hoy`, sin
   `pagofacil_transaction_id` (o con `pagofacil_expira_en` vencido) → genera su QR por anticipado
   (para que esté listo cuando el cliente entre a pagar).
2. Cuotas/Pagos `pagofacil_transaction_id` presente pero aún no confirmados → `query-transaction`
   como red de seguridad (cubre callbacks perdidos). Si el resultado indica pagado, aplica la misma
   lógica que `CallbackHandlerService`.

Registrado en `Kernel::schedule()` con `->everyFifteenMinutes()` (más frecuente que el de Stripe
porque los QR expiran) — **no reemplaza** `pagos:cobrar-cuotas` (Stripe), corren ambos.

### 5.5 Rutas y controladores HTTP

- `app/Http/Controllers/PagoFacilCallbackController.php`: `POST /pagofacil/callback` (pública, sin
  `auth`), responde siempre `{error:0,status:1,message:"...",values:true}` con `200`, incluso si el
  procesamiento interno falla (mismo principio que `StripeWebhookController`: nunca dejar que
  PagoFácil reintente indefinidamente por un 500 nuestro).
- `VerifyCsrfToken::$except` agrega `'pagofacil/callback'`.
- Dentro de `Cliente/PagoController.php` (el mismo controlador de Fase 13, ampliado — no uno nuevo,
  para no duplicar `mostrarPago`/`exito`/`cancelado`):
  - `POST /pedidos/{id}/pagar/pagofacil/unico` → `QrPagoService::generarQrPagoUnico`, responde JSON
    `{qrBase64, expirationDate, paymentNumber}`.
  - `POST /pedidos/{id}/pagar/pagofacil/cuotas` → body `{num_cuotas}`, `CuotasPagoFacilService`.
  - `POST /pedidos/{id}/pagar/pagofacil/cuotas/{cuotaId}/qr` → genera/regenera el QR de una cuota
    `PENDIENTE` puntual (botón "Pagar esta cuota").
  - `GET /pedidos/{id}/pagar/pagofacil/estado?paymentNumber=...` → consulta manual de estado (botón
    "Ya pagué, verificar"), sin esperar el callback.

### 5.6 Frontend (Vue)

- **`Pedidos/Pagar.vue`** (ya existe de Fase 13, se amplía): selector de pasarela arriba de todo —
  "💳 Tarjeta (Stripe)" / "🔲 QR PagoFácil" — cambia qué bloque se muestra debajo.
  - Bloque PagoFácil pago único: botón "Generar QR" → muestra `<img :src="'data:image/png;base64,'
    + qrBase64">`, cuenta regresiva hasta `expirationDate`, botón "Ya pagué, verificar" (polling
    manual a `pagofacil/estado`, no automático, para no saturar la API).
  - Bloque PagoFácil cuotas: mismo selector 2/3/6 con el mínimo Bs.50/cuota ya usado en Stripe, sin
    selector de tarjeta guardada (no aplica); al confirmar, muestra el QR de la cuota 1.
- **`Pedidos/Show.vue`**: la sección "Pago" ahora muestra el `gateway` activo; si hay cuotas
  PagoFácil `PENDIENTE` con `fecha_vencimiento <= hoy`, botón "Pagar esta cuota" que abre el QR
  correspondiente (modal reutilizando el mismo componente de QR de `Pagar.vue`, extraído como
  componente si conviene — `Components/QrPagoFacil.vue`).
- **`Admin/Pedidos/Show.vue`**: la sección de solo lectura agrega `gateway` y `pagofacil_status` (o
  `stripe_status` según corresponda) junto a lo ya existente.
- `Pedidos/Historial.vue`: el badge de estado de pago ya existente se mantiene igual (es agnóstico
  de pasarela, solo mira si hay un `Pago` con estado exitoso).

### 5.7 Sin cambios en `MenuItemSeeder` / `HandleInertiaRequests`

- No hace falta ítem de menú nuevo — la pasarela se elige dentro de la pantalla de pago existente
  (`Pedidos/Pagar.vue`), no es una sección separada.
- No hace falta compartir credenciales públicas de PagoFácil vía Inertia (a diferencia de
  `stripe.publishableKey`, PagoFácil no tiene una llave "pública" — toda la integración es
  server-to-server, el frontend solo recibe el `qrBase64` ya generado).

---

## 6. Secuencia de implementación (incremental y verificable)

1. `PagoFacilClient` (login con caché de token) + config + `.env` (**`.env` ya completado**) —
   verificar con `tinker`: login real exitoso, y `list-enabled-services` para **confirmar el
   `paymentMethodId` real** (reemplazar el `34` de ejemplo si no coincide) y los límites de monto.
2. Migraciones (`pago` + `cuota`) + actualizar modelos.
3. Pago único QR end-to-end: `QrPagoService` + rutas + bloque QR en `Pagar.vue` (sin callback aún,
   solo `generate-qr` + consulta manual `query-transaction` vía botón "Ya pagué") — probar generando
   un QR real y consultando su estado.
4. Callback público (`ngrok` o túnel similar para pruebas locales) + `CallbackHandlerService` para
   pago único — confirmar empíricamente los códigos reales de `Estado`/`MetodoPago` que envía
   PagoFácil y ajustar el mapeo si difieren de lo documentado para el Botón de Pago.
5. Plan de cuotas PagoFácil (servicio + rutas + UI en `Pagar.vue` y botón "Pagar esta cuota" en
   `Show.vue`).
6. Comando `pagos:sincronizar-pagofacil` + registro en `Kernel`.
7. Selector de pasarela visible (Stripe/PagoFácil) + visibilidad admin de solo lectura con `gateway`.
8. Actualizar `implementation_plan.md`, `db_analysis.md`, `task.md` y agregar `PR-27` a
   `plan_de_pruebas.md`.

---

## 7. Riesgos y hallazgos confirmados durante la implementación

- **`PAGOFACIL_PAYMENT_METHOD_ID=34`**: confirmado real (`/list-enabled-services` devuelve
  `{"paymentMethodId":34,"paymentMethodName":"QR ATC","currencyName":"BOB","maxAmountPerDay":70000,
  "maxAmountPerTransaction":70000,"minAmountPerTransaction":0.01}`) — el valor del .env coincidía
  con el real, no hizo falta cambiarlo.
- **`paymentStatusDescription`**: `/query-transaction` devuelve esta descripción ya en español
  ("En Proceso", etc.) junto al código numérico — reduce el riesgo de mapeo de estados documentado
  originalmente; el código sigue usando el numérico (`paymentStatus`) para la lógica, pero se puede
  mostrar la descripción directamente en la UI sin traducir nada a mano.
- **SSL en este entorno Windows**: la instalación de PHP usada (`C:\php.8.0.3`) no tenía
  `curl.cainfo`/`openssl.cafile` configurados a nivel de sistema, lo que rompía todas las llamadas
  HTTPS de `PagoFacilClient` con `cURL error 60: SSL certificate problem`. **Resuelto sin tocar
  archivos fuera del proyecto**: se agregó el bundle CA de Mozilla en `storage/app/cacert.pem` (no
  es secreto, es público) y `PagoFacilClient::http()` lo pasa explícitamente vía
  `->withOptions(['verify' => storage_path('app/cacert.pem')])` en cada request. Si se despliega en
  otro servidor con PHP correctamente configurado, esto sigue funcionando igual (no depende de que
  falte la config global).
- **`callbackUrl` debe ser un dominio público resoluble** (confirmado empíricamente): PagoFácil
  rechaza `generate-qr` con `"Invalid Url Callback"` si el host es `localhost`, `127.0.0.1`, o un
  dominio que no resuelve — no es solo un chequeo de formato. En local (sin túnel público
  configurado), `QrPagoService::callbackUrl()` envía un placeholder bajo `tecnoweb.org.bo` (dominio
  real del despliegue) solo para que `generate-qr` no rechace la solicitud; **el callback no llega
  a esta app en desarrollo local** por este motivo — la fuente de verdad en local es el botón "Ya
  pagué, verificar" (`query-transaction` manual) y el comando `pagos:sincronizar-pagofacil`, ambos
  ya probados contra el sandbox real. Al desplegar en `tecnoweb.org.bo` con HTTPS público real, el
  callback debería llegar igual que cualquier otra notificación.
- **Códigos de `Estado`/`MetodoPago` del callback**: se simularon manualmente contra el endpoint
  propio (`POST /pagofacil/callback` con `{"PedidoID":"P{id}-U","Estado":2}`) y el mapeo documentado
  para "Botón de Pago CheckOut" (`2`=PAGADO) **funcionó correctamente** tanto para pago único como
  para cuotas — confirma la cuota correcta, marca el pedido `CONFIRMADO`. No se pudo confirmar el
  código real que envía PagoFácil en un callback genuino (requiere el túnel público pendiente, ver
  abajo), pero la lógica de procesamiento ya está validada con ese supuesto.
- **Sin túnel público para probar la entrega real del callback**: no había `ngrok`/`cloudflared`
  instalados, e iniciar un túnel (`localtunnel` u otro) expone el servidor local a internet — una
  acción que requiere autorización explícita del usuario antes de ejecutarla. **Pendiente**: si se
  quiere verificar la entrega real del callback (no solo la simulación), correr `ngrok http 8000` (o
  similar) manualmente, registrar esa URL pública en `QrPagoService::callbackUrl()` temporalmente, y
  repetir una transacción de prueba pagándola de verdad desde una app bancaria boliviana en modo
  sandbox.
- **Las credenciales son de archivo "test"** — asumidas de sandbox.
- **Bug real encontrado y corregido con Playwright** (clic real en navegador, 2026-06-29):
  `Pedidos/Pagar.vue` usaba una sola variable `qrPf` compartida entre el bloque "Pago único" y el
  bloque "Plan de cuotas". Al generar el QR de pago único y luego cambiar a la pestaña de cuotas, se
  mostraba el QR del pago único disfrazado de "cuota 1" con el texto roto `"QR de la cuota 1 de ."`
  (campo vacío). **Corregido** separando el estado en `qrUnico`/`qrCuotas` y
  `mensajeEstadoUnico`/`mensajeEstadoCuotas` independientes, con un único `verificarEstadoQr(paymentNumber,
  mensajeRef)` parametrizado. Reverificado con un pedido de Bs. 340 (3 cuotas de Bs. 113.33/113.33/
  113.34): el texto ahora muestra correctamente `"QR de la cuota 1 de 3"` y cada pestaña conserva su
  propio QR sin mezclarse.

---

## 8. Estado actual de avance

- [x] Documentación de PagoFácil revisada (`InfoPagoFacil/md/`: CheckOut Botón de Pago v2.1, Flujo
      QR, Servicios API MasterQR v1.1.0, colección Postman).
- [x] Decisiones de diseño confirmadas con el usuario (API MasterQR como base, dual gateway con
      Stripe, cuotas vía QR por cuota sin cobro automático).
- [x] Credenciales de sandbox recibidas (`InfoPagoFacil/md/Credentials pagofacil test.txt`) y
      cargadas en `.env`/`.env.example`, **validadas contra la API real** (login, list-enabled-services,
      generate-qr, query-transaction todos responden `200` con estas credenciales).
- [x] `config/services.php` (bloque `pagofacil`) + migraciones aditivas en `pago`
      (`2026_06_29_100013_...`) y `cuota` (`2026_06_29_100014_...`) + modelos actualizados —
      aplicadas contra la BD remota `db_grupo21sa`.
- [x] `app/Services/PagoFacil/`: `PagoFacilClient` (login con caché de token + reintento en 401),
      `QrPagoService` (pago único y QR por cuota), `CuotasPagoFacilService` (plan 2/3/6 cuotas),
      `CallbackHandlerService` (parsea `PedidoID`, actualiza `Pago`/`Cuota`/`Pedido`).
- [x] `PagoFacilCallbackController` (`POST /pagofacil/callback`, pública, excluida de CSRF) +
      `Cliente/PagoController` ampliado con `pagoFacilUnico`, `pagoFacilCuotas`, `pagoFacilQrCuota`,
      `pagoFacilEstado`.
- [x] Frontend: selector de pasarela (PagoFácil/Stripe) en `Pedidos/Pagar.vue`, componente
      reutilizable `Components/QrPagoFacil.vue` (QR + verificación automática cada 10s + botón
      manual), botón "Pagar esta cuota" en `Pedidos/Show.vue`, columna `gateway` en
      `Admin/Pedidos/Show.vue` (solo lectura). `npm run build` sin errores.
- [x] Comando `pagos:sincronizar-pagofacil` (pre-genera QR de cuotas vencidas + sincroniza estado
      pendiente vía `query-transaction`) registrado en `Kernel::schedule()->everyFifteenMinutes()`.
- [x] **Pruebas end-to-end contra el sandbox real** (2026-06-29, ver sección 7 para hallazgos):
      login real, `list-enabled-services` (confirma `paymentMethodId=34`), generación de QR de pago
      único vía HTTP completo (login → controlador → servicio → cliente → API real → fila `Pago`
      actualizada correctamente en BD), plan de cuotas (3 cuotas con montos correctos: 28.33+28.33+
      28.34=85.00, QR solo de la cuota 1), consulta manual de estado (`query-transaction` real),
      simulación de callback para pago único (`Pedido` pasó a `CONFIRMADO`) y para una cuota
      (`Cuota.estado` pasó a `PAGADO`), y ejecución limpia de `pagos:sincronizar-pagofacil` contra
      datos reales (sincronizó el estado de la cuota 1 sin marcarla pagada, correctamente, porque
      genuinamente no se pagó). Datos de prueba (pedido/venta/pago/cuotas desechables) limpiados al
      terminar; el usuario QA `qa.pagofacil@test.local` / `Test1234!` queda disponible para pruebas
      futuras en el navegador (mismo patrón que los usuarios `test.*@local.test` del proyecto).
- [x] **Verificación visual con Playwright (clic real, 2026-06-29)**: se instaló Playwright +
      Chromium temporalmente, se levantaron `php artisan serve` + `npm run dev`, y se recorrió la UI
      real con dos pedidos desechables: selector de pasarela (PagoFácil activo por defecto, cambio a
      Stripe y vuelta), generación y render del QR de pago único, botón "Ya pagué, verificar"
      disparando una consulta real (`query-transaction`) y mostrando "Estado: En Proceso", plan de
      cuotas (3 cuotas de Bs. 113.33/113.33/113.34 sobre un pedido de Bs. 340), tabla de cuotas en
      `Pedidos/Show.vue` con el botón "Pagar" visible solo en la cuota que vence hoy, y la sección
      "Pasarela: QR PagoFácil" + badge `PENDIENTE` correctos. Se encontró y corrigió un bug real (ver
      arriba: estado de QR compartido entre pestañas). Playwright desinstalado y datos de prueba
      limpiados al terminar.
- [ ] **Pendiente** (requiere decisión/acción del usuario, no bloquea la entrega): probar la entrega
      real del callback con un túnel público (`ngrok` u otro) — no se ejecutó porque expone el
      servidor local a internet y requiere autorización explícita antes de hacerlo. Sin esto, el
      callback automático no se ha probado de punta a punta, pero la ruta de respaldo (consulta
      manual + comando programado) sí está probada y es la fuente de verdad funcional hoy.

Ver `plan_pagos_stripe.md` para el detalle de la Fase 13 (Stripe, que se mantiene sin cambios) y
`db_analysis.md`/`implementation_plan.md` para el resto del proyecto.
