<template>
  <AppLayout>
    <div class="container">
      <div class="admin-page fade-in">
        <div class="admin-header">
          <h1 class="page-title">🔗 Menú Dinámico</h1>
          <Link :href="route('admin.menu.create')" class="btn btn-primary">+ Nuevo Ítem</Link>
        </div>
        <div class="menu-list">
          <div v-for="item in items" :key="item.id" class="menu-item-card card">
            <div class="mi-header">
              <span class="mi-icon">{{ item.icon || '📄' }}</span>
              <div class="mi-info">
                <strong>{{ item.label }}</strong>
                <span class="mi-route">{{ item.route_name }}</span>
              </div>
              <span class="mi-nivel">Nivel ≥ {{ item.role_nivel_minimo }}</span>
              <span :class="['status-dot', item.activo ? 'active' : 'inactive']">{{ item.activo ? 'Activo' : 'Inactivo' }}</span>
              <div class="mi-actions">
                <Link :href="route('admin.menu.edit', item.id)" class="btn btn-outline btn-xs">Editar</Link>
                <button @click="eliminar(item.id)" class="btn btn-xs btn-danger">✕</button>
              </div>
            </div>
            <div v-if="item.children?.length" class="mi-children">
              <div v-for="hijo in item.children" :key="hijo.id" class="mi-child">
                <span>↳ {{ hijo.icon || '' }} {{ hijo.label }}</span>
                <span class="mi-route">{{ hijo.route_name }}</span>
                <span class="mi-nivel">Nivel ≥ {{ hijo.role_nivel_minimo }}</span>
                <span :class="['status-dot', hijo.activo ? 'active' : 'inactive']">{{ hijo.activo ? '✓' : '✕' }}</span>
                <div class="mi-actions">
                  <Link :href="route('admin.menu.edit', hijo.id)" class="btn btn-outline btn-xs">Editar</Link>
                  <button @click="eliminar(hijo.id)" class="btn btn-xs btn-danger">✕</button>
                </div>
              </div>
            </div>
          </div>
        </div>
        <p class="menu-note">💡 También puedes gestionar ítems directamente desde la base de datos o mediante el seeder <code>MenuItemSeeder</code>.</p>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { Link, router } from '@inertiajs/vue3'
defineProps({ items: Array })

function eliminar(id) {
  if (confirm('¿Desactivar este ítem de menú?')) {
    router.delete(route('admin.menu.destroy', id), { preserveScroll: true })
  }
}
</script>

<style scoped>
.admin-page { padding:2rem 0; }
.admin-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem; }
.page-title { font-size:1.75rem; }
.menu-list { display:flex; flex-direction:column; gap:1rem; }
.mi-actions { display:flex; gap:0.375rem; }
.btn-xs { padding:0.25rem 0.5rem; font-size:0.75rem; }
.btn-danger { background:var(--color-danger); color:white; }
.mi-header { display:flex; align-items:center; gap:1rem; }
.mi-icon { font-size:1.5rem; }
.mi-info { flex:1; }
.mi-info strong { display:block; }
.mi-route { font-size:0.75rem; color:var(--text-secondary); font-family:monospace; }
.mi-nivel { font-size:0.7rem; background:var(--bg-secondary); padding:0.15rem 0.5rem; border-radius:var(--radius-pill); color:var(--text-secondary); }
.mi-children { margin-top:0.75rem; padding-left:2.5rem; display:flex; flex-direction:column; gap:0.5rem; }
.mi-child { display:flex; align-items:center; gap:1rem; font-size:0.875rem; padding:0.375rem 0; border-top:1px solid var(--border-color); }
.status-dot { padding:0.15rem 0.4rem; border-radius:var(--radius-pill); font-size:0.65rem; font-weight:700; }
.status-dot.active { background:color-mix(in srgb, var(--color-success) 20%, var(--bg-card)); color:var(--color-success); }
.status-dot.inactive { background:color-mix(in srgb, var(--color-danger) 20%, var(--bg-card)); color:var(--color-danger); }
.menu-note { margin-top:2rem; font-size:0.85rem; color:var(--text-secondary); }
</style>
