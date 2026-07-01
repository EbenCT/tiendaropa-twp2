<template>
  <AppLayout>
    <div class="container">
      <div class="admin-page fade-in">
        <div class="admin-header">
          <h1 class="page-title"><i class="fa-solid fa-users"></i> Gestión de Usuarios</h1>
          <Link :href="route('admin.usuarios.create')" class="btn btn-primary">+ Nuevo Usuario</Link>
        </div>

        <div class="filtros-inline card" style="margin-bottom:1.5rem">
          <input v-model="busqueda" @keyup.enter="buscar" type="text" class="input" placeholder="Buscar por nombre, email..." style="max-width:300px" />
          <select v-model="rolFiltro" @change="buscar" class="input" style="max-width:180px">
            <option value="">Todos los roles</option>
            <option value="admin">Admin</option>
            <option value="propietario">Propietario</option>
            <option value="vendedor">Vendedor</option>
            <option value="cliente">Cliente</option>
          </select>
          <button @click="buscar" class="btn btn-primary">Buscar</button>
        </div>

        <div class="table-wrap card">
          <table class="admin-table">
            <thead>
              <tr><th>ID</th><th>Nombre</th><th>Email</th><th>CI</th><th>Rol</th><th>Estado</th><th>Acciones</th></tr>
            </thead>
            <tbody>
              <tr v-for="u in usuarios.data" :key="u.id">
                <td>{{ u.id }}</td>
                <td><strong>{{ u.nombre }} {{ u.apellido }}</strong></td>
                <td>{{ u.email }}</td>
                <td>{{ u.ci }}</td>
                <td><span class="rol-badge">{{ u.rol_nuevo || u.rol }}</span></td>
                <td><span :class="['status-dot', u.activo ? 'active' : 'inactive']">{{ u.activo ? 'Activo' : 'Inactivo' }}</span></td>
                <td class="actions-cell">
                  <Link :href="route('admin.usuarios.edit', u.id)" class="btn btn-outline btn-xs">Editar</Link>
                  <button @click="desactivar(u.id)" class="btn btn-xs btn-danger" v-if="u.activo" title="Desactivar"><i class="fa-solid fa-xmark"></i></button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-if="usuarios.last_page > 1" class="paginacion">
          <template v-for="link in usuarios.links" :key="link.label">
            <Link v-if="link.url" :href="link.url" :class="['pag-link', { active: link.active }]" v-html="link.label" />
          </template>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Link, router } from '@inertiajs/vue3'
defineProps({ usuarios: Object })
const busqueda = ref('')
const rolFiltro = ref('')
function buscar() {
  const params = {}
  if (busqueda.value) params.q = busqueda.value
  if (rolFiltro.value) params.rol = rolFiltro.value
  router.get(route('admin.usuarios.index'), params, { preserveState: true })
}
function desactivar(id) {
  if (confirm('¿Desactivar este usuario?')) router.delete(route('admin.usuarios.destroy', id), { preserveScroll: true })
}
</script>

<style scoped>
.admin-page { padding:2rem 0; }
.admin-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem; }
.page-title { font-size:1.75rem; }
.filtros-inline { display:flex; gap:0.75rem; align-items:center; flex-wrap:wrap; }
.table-wrap { overflow-x:auto; }
.admin-table { width:100%; border-collapse:collapse; }
.admin-table th { text-align:left; padding:0.75rem; font-size:0.75rem; text-transform:uppercase; color:var(--text-secondary); border-bottom:2px solid var(--border-color); }
.admin-table td { padding:0.75rem; border-bottom:1px solid var(--border-color); font-size:0.875rem; }
.rol-badge { background:color-mix(in srgb, var(--color-info) 15%, var(--bg-card)); color:var(--color-info); padding:0.15rem 0.5rem; border-radius:var(--radius-pill); font-size:0.75rem; font-weight:600; text-transform:capitalize; }
.status-dot { padding:0.2rem 0.5rem; border-radius:var(--radius-pill); font-size:0.7rem; font-weight:600; }
.status-dot.active { background:color-mix(in srgb, var(--color-success) 20%, var(--bg-card)); color:var(--color-success); }
.status-dot.inactive { background:color-mix(in srgb, var(--color-danger) 20%, var(--bg-card)); color:var(--color-danger); }
.actions-cell { display:flex; gap:0.375rem; }
.btn-xs { padding:0.25rem 0.5rem; font-size:0.75rem; }
.btn-danger { background:var(--color-danger); color:white; }
.paginacion { display:flex; justify-content:center; gap:0.25rem; margin-top:1.5rem; }
.pag-link { padding:0.4rem 0.75rem; border-radius:var(--radius-sm); border:1px solid var(--border-color); background:var(--bg-card); color:var(--text-primary); font-size:0.85rem; text-decoration:none; }
.pag-link.active { background:var(--color-primary); color:var(--text-on-primary); }
</style>
