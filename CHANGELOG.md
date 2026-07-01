# Changelog

## [Unreleased] — 2026-06-30

### Correcciones de errores

#### Error SQL en reportes (`detalle_pedido.subtotal` inexistente)
- **Archivo:** `app/Http/Controllers/Admin/ReporteController.php`
- **Causa:** Las queries de top productos y ventas por categoría usaban `SUM(detalle_pedido.subtotal)`, pero esa columna no existe en la tabla. Solo existen `cantidad` y `precio_unitario`.
- **Fix:** Reemplazado en ambas queries por `SUM(detalle_pedido.cantidad * detalle_pedido.precio_unitario)`.

#### Botón "Ingresar" invisible en modo luz
- **Archivo:** `resources/js/Layouts/AppLayout.vue`
- **Causa:** En `tema-adultos.modo-dia`, `--color-primary: #1C1C1C` y `--bg-header: #1C1C1C` son idénticos. El `.btn-outline` usaba `color: var(--color-primary)` sobre ese fondo, haciéndolo invisible.
- **Fix:** Regla CSS scoped `.topbar .btn-outline` con color y borde blancos, ya que el topbar siempre tiene fondo oscuro en todos los temas.

#### Reset de escala de fuente no persistía
- **Archivo:** `resources/js/composables/useTema.js`
- **Causa:** `resetFuente()` actualizaba `fontScale.value = 1` pero no llamaba a `localStorage.setItem()`. Al navegar a otra página, la escala se re-leía del `localStorage` y volvía al valor anterior.
- **Fix:** Añadido `localStorage.setItem('fontScale', '1')` dentro de `resetFuente()`.

---

### Nuevas funcionalidades

#### Layout fijo — sidebar y footer siempre visibles
- **Archivo:** `resources/js/Layouts/AppLayout.vue`
- `app-shell`: `height: 100vh; overflow: hidden` — el shell nunca hace scroll.
- `app-body`: `height: calc(100vh - 60px)` — altura fija descontando el topbar.
- `app-sidebar`: `height: 100%; overflow-y: auto` — el sidebar scrollea su propio contenido si es largo.
- `app-main`: `overflow: hidden` — el contenedor principal tampoco scrollea.
- `page-content`: `overflow-y: auto` — **única zona de scroll**. El usuario mueve la rueda y solo el contenido de la página responde.
- Footer siempre visible al final del `app-main` con `flex-shrink: 0`.

#### Sidebar colapsable (solo iconos)
- **Archivo:** `resources/js/Layouts/AppLayout.vue`
- En desktop (≥900px): clic en el botón hamburger colapsa el sidebar a 60px de ancho mostrando solo iconos con tooltips (`title` attribute).
- En mobile (<900px): el mismo botón abre/cierra el sidebar con overlay (comportamiento original).
- Estado persiste en `localStorage.sidebarCollapsed`.
- Todos los textos del sidebar usan clase `.sidebar-text` que se oculta con `display: none` al colapsar.
- Los submenús se cierran automáticamente cuando el sidebar está colapsado (`v-show` condicionado a `!sidebarCollapsed`).
- Overrides CSS con `!important` para mobile garantizan que el sidebar siempre se muestre completo en pantallas pequeñas.

#### Buscador multifuncional con filtro por rol
- **Archivos:** `app/Http/Controllers/BuscadorController.php`, `resources/js/Layouts/AppLayout.vue`
- El buscador ahora retorna 4 categorías de resultados: productos, categorías, usuarios y acciones del sistema.
- **Usuarios:** vendedores (nivel ≥ 2) buscan clientes; propietarios y admins (nivel ≥ 3) buscan todos los usuarios.
- **Acciones del sistema:** cada acción tiene un `nivel` mínimo requerido. Clientes e invitados nunca ven resultados administrativos.
- Placeholder actualizado a "Buscar en el sistema...".
- Dropdown muestra sección "Usuarios" con icono de persona y el rol del usuario encontrado.

---

### Mejoras de UI/UX

#### Tamaño de fuente base reducido
- **Archivo:** `resources/css/app.css`
- Fuente base reducida de `16px` a `14px` para una apariencia más compacta y profesional.
- La escala de accesibilidad (`--font-scale`) sigue funcionando sobre esta nueva base.

#### Iconos Font Awesome en lugar de emojis
- Todos los emojis del sistema (en menú, botones, etiquetas) fueron reemplazados por iconos de Font Awesome 6 Free (`fa-solid`, `fa-regular`) para una apariencia más profesional y consistente.

---

## Historial de versiones anteriores

### Pagos
- Integración de Stripe para tarjetas internacionales.
- Integración de PagoFácil para pagos locales (Bolivia).

### Contexto compartido Inertia
- `HandleInertiaRequests::share()` expone `auth.user.nivel` (nivel numérico del rol) a todos los componentes Vue para control de acceso en el frontend.

### Sistema de temas
- 3 temas (`ninos`, `jovenes`, `adultos`) × 2 modos (`dia`, `noche`) + modo `auto` basado en hora del sistema.
- Escala de fuente ajustable entre 0.8× y 1.4×.
- Todo el estado persiste en `localStorage`.
