<template>
  <AppLayout>
    <div class="container">
      <div class="admin-page fade-in">
        <div class="admin-header">
          <h1 class="page-title">📦 Gestión de Productos</h1>
          <Link :href="route('admin.productos.create')" class="btn btn-primary">+ Nuevo Producto</Link>
        </div>

        <div class="filtros-inline card" style="margin-bottom:1.5rem">
          <input v-model="busqueda" @keyup.enter="buscar" type="text" class="input" placeholder="Buscar producto..." style="max-width:300px" />
          <select v-model="categoriaFiltro" @change="buscar" class="input" style="max-width:200px">
            <option value="">Todas las categorías</option>
            <option v-for="c in categorias" :key="c.id" :value="c.id">{{ c.nombre }}</option>
          </select>
          <button @click="buscar" class="btn btn-primary">Buscar</button>
        </div>

        <div class="table-wrap card">
          <table class="admin-table">
            <thead>
              <tr>
                <th>ID</th>
                <th>Imagen</th>
                <th>Nombre</th>
                <th>Categoría</th>
                <th>Precio</th>
                <th>Stock</th>
                <th>Estado</th>
                <th>Acciones</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="p in productos.data" :key="p.id">
                <td>{{ p.id }}</td>
                <td><img :src="p.imagen_principal?.url || p.imagen_url || '/img/placeholder.jpg'" class="table-img" /></td>
                <td><strong>{{ p.nombre }}</strong></td>
                <td>{{ p.categoria?.nombre }}</td>
                <td>Bs. {{ Number(p.precio_unitario).toFixed(2) }}</td>
                <td>{{ p.stock_actual }}</td>
                <td>
                  <span :class="['status-dot', p.activo ? 'active' : 'inactive']">{{ p.activo ? 'Activo' : 'Inactivo' }}</span>
                </td>
                <td class="actions-cell">
                  <Link :href="route('admin.productos.edit', p.id)" class="btn btn-outline btn-xs">Editar</Link>
                  <button @click="toggleDestacado(p.id, 'destacado')" :class="['btn btn-xs', p.destacado ? 'btn-accent' : 'btn-outline']">⭐</button>
                  <button @click="eliminar(p.id)" class="btn btn-xs btn-danger">✕</button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-if="productos.last_page > 1" class="paginacion">
          <template v-for="link in productos.links" :key="link.label">
            <Link v-if="link.url" :href="link.url" :class="['pag-link', { active: link.active }]" v-html="link.label" />
            <span v-else class="pag-link disabled" v-html="link.label" />
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

defineProps({
  productos:  { type: Object, required: true },
  categorias: { type: Array, default: () => [] },
})

const busqueda = ref('')
const categoriaFiltro = ref('')

function buscar() {
  const params = {}
  if (busqueda.value) params.q = busqueda.value
  if (categoriaFiltro.value) params.categoria = categoriaFiltro.value
  router.get(route('admin.productos.index'), params, { preserveState: true })
}

function toggleDestacado(id, campo) {
  router.post(route('admin.destacados.toggle', id), { campo }, { preserveScroll: true })
}

function eliminar(id) {
  if (confirm('¿Desactivar este producto?')) {
    router.delete(route('admin.productos.destroy', id), { preserveScroll: true })
  }
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
.admin-table td { padding:0.75rem; border-bottom:1px solid var(--border-color); font-size:0.875rem; vertical-align:middle; }
.table-img { width:48px; height:60px; object-fit:cover; border-radius:var(--radius-sm); }
.status-dot { padding:0.2rem 0.5rem; border-radius:var(--radius-pill); font-size:0.7rem; font-weight:600; }
.status-dot.active { background:color-mix(in srgb, var(--color-success) 20%, var(--bg-card)); color:var(--color-success); }
.status-dot.inactive { background:color-mix(in srgb, var(--color-danger) 20%, var(--bg-card)); color:var(--color-danger); }
.actions-cell { display:flex; gap:0.375rem; }
.btn-xs { padding:0.25rem 0.5rem; font-size:0.75rem; }
.btn-danger { background:var(--color-danger); color:white; }
.paginacion { display:flex; justify-content:center; gap:0.25rem; margin-top:1.5rem; }
.pag-link { padding:0.4rem 0.75rem; border-radius:var(--radius-sm); border:1px solid var(--border-color); background:var(--bg-card); color:var(--text-primary); font-size:0.85rem; text-decoration:none; }
.pag-link.active { background:var(--color-primary); color:var(--text-on-primary); }
.pag-link.disabled { opacity:0.4; pointer-events:none; }
</style>
