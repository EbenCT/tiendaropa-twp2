<template>
  <AppLayout>
    <div class="container">
      <div class="admin-page fade-in">

        <!-- Encabezado -->
        <div class="admin-header">
          <div>
            <h1 class="page-title">
              <i class="fa-solid fa-sliders"></i> Menú Dinámico
            </h1>
            <p class="page-desc">
              Controla qué ítems aparecen en el menú lateral según el rol del usuario.
              El <strong>Nivel mínimo</strong> define desde qué rol se ve el ítem:
              <span class="nivel-badge nivel-0">0=Todos</span>
              <span class="nivel-badge nivel-1">1=Clientes</span>
              <span class="nivel-badge nivel-2">2=Vendedor+</span>
              <span class="nivel-badge nivel-3">3=Propietario+</span>
              <span class="nivel-badge nivel-4">4=Admin</span>
            </p>
          </div>
          <Link :href="route('admin.menu.create')" class="btn btn-primary">
            <i class="fa-solid fa-plus"></i> Nuevo Ítem
          </Link>
        </div>

        <!-- Árbol del menú -->
        <div class="menu-tree">

          <!-- Ítems raíz -->
          <div v-for="item in items" :key="item.id" class="tree-node card">

            <!-- Ítem padre -->
            <div class="node-header">
              <div class="node-icon-wrap" :class="`level-${item.role_nivel_minimo}`">
                <i class="fa-solid fa-circle-dot"></i>
              </div>
              <div class="node-info">
                <div class="node-name">
                  {{ item.label }}
                  <span v-if="!item.route_name" class="node-type-badge">Grupo</span>
                </div>
                <div class="node-meta">
                  <span v-if="item.route_name" class="node-route">
                    <i class="fa-solid fa-link"></i> {{ item.route_name }}
                  </span>
                  <span class="node-level" :class="`badge-nivel-${item.role_nivel_minimo}`">
                    Nivel ≥ {{ item.role_nivel_minimo }}
                    ({{ nivelLabel(item.role_nivel_minimo) }})
                  </span>
                </div>
              </div>
              <span :class="['status-pill', item.activo ? 'pill-on' : 'pill-off']">
                <i :class="item.activo ? 'fa-solid fa-circle-check' : 'fa-solid fa-circle-xmark'"></i>
                {{ item.activo ? 'Activo' : 'Inactivo' }}
              </span>
              <div class="node-actions">
                <Link :href="route('admin.menu.edit', item.id)" class="btn btn-outline btn-xs" title="Editar">
                  <i class="fa-solid fa-pen"></i>
                </Link>
                <button @click="toggleActivo(item.id, !item.activo)" :class="['btn btn-xs', item.activo ? 'btn-warning' : 'btn-success']" :title="item.activo ? 'Desactivar' : 'Activar'">
                  <i :class="item.activo ? 'fa-solid fa-toggle-off' : 'fa-solid fa-toggle-on'"></i>
                </button>
              </div>
            </div>

            <!-- Hijos -->
            <div v-if="item.children?.length" class="node-children">
              <div v-for="hijo in item.children" :key="hijo.id" class="child-node">
                <i class="fa-solid fa-corner-down-right child-arrow"></i>
                <div class="node-icon-wrap sm" :class="`level-${hijo.role_nivel_minimo}`">
                  <i class="fa-solid fa-minus"></i>
                </div>
                <div class="node-info">
                  <div class="node-name">{{ hijo.label }}</div>
                  <div class="node-meta">
                    <span v-if="hijo.route_name" class="node-route">
                      <i class="fa-solid fa-link"></i> {{ hijo.route_name }}
                    </span>
                    <span class="node-level" :class="`badge-nivel-${hijo.role_nivel_minimo}`">
                      Nivel ≥ {{ hijo.role_nivel_minimo }}
                      ({{ nivelLabel(hijo.role_nivel_minimo) }})
                    </span>
                  </div>
                </div>
                <span :class="['status-pill', hijo.activo ? 'pill-on' : 'pill-off']">
                  <i :class="hijo.activo ? 'fa-solid fa-circle-check' : 'fa-solid fa-circle-xmark'"></i>
                  {{ hijo.activo ? 'Activo' : 'Inactivo' }}
                </span>
                <div class="node-actions">
                  <Link :href="route('admin.menu.edit', hijo.id)" class="btn btn-outline btn-xs">
                    <i class="fa-solid fa-pen"></i>
                  </Link>
                  <button @click="toggleActivo(hijo.id, !hijo.activo)" :class="['btn btn-xs', hijo.activo ? 'btn-warning' : 'btn-success']">
                    <i :class="hijo.activo ? 'fa-solid fa-toggle-off' : 'fa-solid fa-toggle-on'"></i>
                  </button>
                </div>
              </div>
            </div>

          </div>
        </div>

        <!-- Leyenda -->
        <div class="card legend-card">
          <h3 class="legend-title"><i class="fa-solid fa-circle-info"></i> Cómo funciona</h3>
          <div class="legend-grid">
            <div class="legend-item">
              <i class="fa-solid fa-layer-group"></i>
              <div>
                <strong>Grupos</strong>
                <p>Ítems sin ruta que agrupan sub-ítems (ej. "Gestión"). Deben tener hijos para aparecer en el menú.</p>
              </div>
            </div>
            <div class="legend-item">
              <i class="fa-solid fa-arrow-up-9-1"></i>
              <div>
                <strong>Nivel mínimo</strong>
                <p>Un ítem con nivel 2 lo verán vendedores, propietarios y admins. Nivel 0 = visible para todos (incluso sin sesión).</p>
              </div>
            </div>
            <div class="legend-item">
              <i class="fa-solid fa-rotate"></i>
              <div>
                <strong>Caché</strong>
                <p>Los cambios se aplican en máximo 5 minutos. Si necesitas reflejarlos de inmediato, limpia la caché del servidor.</p>
              </div>
            </div>
          </div>
        </div>

      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { Link, router } from '@inertiajs/vue3'
defineProps({ items: Array })

function toggleActivo(id, nuevoEstado) {
  const accion = nuevoEstado ? 'activar' : 'desactivar'
  if (!confirm(`¿${accion.charAt(0).toUpperCase() + accion.slice(1)} este ítem de menú?`)) return
  router.patch(route('admin.menu.toggle', id), {}, { preserveScroll: true })
}

function nivelLabel(nivel) {
  return { 0: 'Público', 1: 'Cliente', 2: 'Vendedor', 3: 'Propietario', 4: 'Admin' }[nivel] ?? nivel
}
</script>

<style scoped>
.admin-page { padding: 1.5rem 0; }
.admin-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1.5rem; gap: 1rem; }
.page-title { font-size: 1.5rem; display: flex; align-items: center; gap: 0.625rem; margin-bottom: 0.4rem; }
.page-desc { font-size: 0.82rem; color: var(--text-secondary); max-width: 620px; line-height: 1.6; display: flex; flex-wrap: wrap; gap: 0.25rem; align-items: center; }

/* Nivel badges en descripción */
.nivel-badge { font-size: 0.7rem; padding: 0.1rem 0.45rem; border-radius: var(--radius-pill); font-weight: 700; }
.nivel-0 { background: var(--bg-secondary); color: var(--text-secondary); }
.nivel-1 { background: color-mix(in srgb, var(--color-info) 20%, var(--bg-card)); color: var(--color-info); }
.nivel-2 { background: color-mix(in srgb, var(--color-warning) 20%, var(--bg-card)); color: var(--color-warning); }
.nivel-3 { background: color-mix(in srgb, var(--color-primary) 20%, var(--bg-card)); color: var(--color-primary); }
.nivel-4 { background: color-mix(in srgb, var(--color-accent) 30%, var(--bg-card)); color: var(--color-accent); }

/* Árbol */
.menu-tree { display: flex; flex-direction: column; gap: 1rem; margin-bottom: 1.5rem; }
.tree-node { padding: 0; overflow: hidden; }

/* Nodo */
.node-header { display: flex; align-items: center; gap: 0.875rem; padding: 0.875rem 1.25rem; }
.node-icon-wrap {
  width: 34px; height: 34px; border-radius: var(--radius-sm);
  display: flex; align-items: center; justify-content: center; font-size: 0.9rem;
  flex-shrink: 0;
}
.node-icon-wrap.sm { width: 26px; height: 26px; font-size: 0.7rem; }
.level-0 { background: var(--bg-secondary); color: var(--text-secondary); }
.level-1 { background: color-mix(in srgb, var(--color-info) 20%, var(--bg-card)); color: var(--color-info); }
.level-2 { background: color-mix(in srgb, var(--color-warning) 20%, var(--bg-card)); color: var(--color-warning); }
.level-3 { background: color-mix(in srgb, var(--color-primary) 20%, var(--bg-card)); color: var(--color-primary); }
.level-4 { background: color-mix(in srgb, var(--color-accent) 30%, var(--bg-card)); color: var(--color-accent); }

.node-info { flex: 1; min-width: 0; }
.node-name { font-weight: 600; font-size: 0.9rem; display: flex; align-items: center; gap: 0.4rem; }
.node-type-badge { font-size: 0.65rem; padding: 0.1rem 0.4rem; background: var(--bg-secondary); color: var(--text-secondary); border-radius: var(--radius-pill); font-weight: 600; }
.node-meta { display: flex; align-items: center; gap: 0.625rem; margin-top: 0.2rem; flex-wrap: wrap; }
.node-route { font-size: 0.73rem; color: var(--text-secondary); font-family: monospace; display: flex; align-items: center; gap: 0.3rem; }
.node-level { font-size: 0.7rem; padding: 0.1rem 0.5rem; border-radius: var(--radius-pill); font-weight: 600; }
.badge-nivel-0 { background: var(--bg-secondary); color: var(--text-secondary); }
.badge-nivel-1 { background: color-mix(in srgb, var(--color-info) 18%, var(--bg-card)); color: var(--color-info); }
.badge-nivel-2 { background: color-mix(in srgb, var(--color-warning) 18%, var(--bg-card)); color: var(--color-warning); }
.badge-nivel-3 { background: color-mix(in srgb, var(--color-primary) 18%, var(--bg-card)); color: var(--color-primary); }
.badge-nivel-4 { background: color-mix(in srgb, var(--color-accent) 25%, var(--bg-card)); color: var(--color-accent); }

.status-pill { display: flex; align-items: center; gap: 0.3rem; font-size: 0.72rem; font-weight: 600; padding: 0.2rem 0.6rem; border-radius: var(--radius-pill); white-space: nowrap; }
.pill-on  { background: color-mix(in srgb, var(--color-success) 15%, var(--bg-card)); color: var(--color-success); }
.pill-off { background: color-mix(in srgb, var(--color-danger) 15%, var(--bg-card)); color: var(--color-danger); }

.node-actions { display: flex; gap: 0.35rem; }
.btn-xs { padding: 0.3rem 0.55rem; font-size: 0.8rem; }
.btn-warning { background: color-mix(in srgb, var(--color-warning) 20%, var(--bg-card)); color: var(--color-warning); border: 1.5px solid var(--color-warning); }
.btn-success { background: color-mix(in srgb, var(--color-success) 20%, var(--bg-card)); color: var(--color-success); border: 1.5px solid var(--color-success); }

/* Hijos */
.node-children { border-top: 1px solid var(--border-color); background: var(--bg-secondary); }
.child-node { display: flex; align-items: center; gap: 0.75rem; padding: 0.7rem 1.25rem; border-bottom: 1px solid var(--border-color); }
.child-node:last-child { border-bottom: none; }
.child-arrow { color: var(--text-secondary); font-size: 0.85rem; flex-shrink: 0; }

/* Leyenda */
.legend-card { margin-top: 0.5rem; }
.legend-title { font-size: 0.95rem; display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1rem; }
.legend-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1rem; }
.legend-item { display: flex; gap: 0.875rem; font-size: 0.83rem; }
.legend-item > i { font-size: 1.1rem; color: var(--color-primary); margin-top: 0.1rem; flex-shrink: 0; }
.legend-item strong { display: block; margin-bottom: 0.2rem; }
.legend-item p { color: var(--text-secondary); line-height: 1.5; margin: 0; }
</style>
