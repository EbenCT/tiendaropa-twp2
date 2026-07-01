<template>
  <AppLayout>
    <div class="container">
      <div class="admin-page fade-in">
        <div class="admin-header">
          <h1 class="page-title">
            <i class="fa-solid fa-box"></i> Productos
          </h1>
          <!-- Solo propietario+ puede crear -->
          <Link
            v-if="$page.props.auth.user?.nivel >= 3"
            :href="route('admin.productos.create')"
            class="btn btn-primary"
          >
            <i class="fa-solid fa-plus"></i> Nuevo Producto
          </Link>
        </div>

        <!-- Filtros -->
        <div class="filtros-inline card" style="margin-bottom:1.5rem">
          <input v-model="busqueda" @keyup.enter="buscar" type="text" class="input" placeholder="Buscar producto..." style="max-width:300px" />
          <select v-model="categoriaFiltro" @change="buscar" class="input" style="max-width:200px">
            <option value="">Todas las categorías</option>
            <option v-for="c in categorias" :key="c.id" :value="c.id">{{ c.nombre }}</option>
          </select>
          <button @click="buscar" class="btn btn-primary">
            <i class="fa-solid fa-magnifying-glass"></i> Buscar
          </button>
          <button @click="limpiar" class="btn btn-outline">
            <i class="fa-solid fa-xmark"></i> Limpiar
          </button>
        </div>

        <!-- Tabla -->
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
                <th>Activo</th>
                <th v-if="$page.props.auth.user?.nivel >= 3">Acciones</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="p in productos.data" :key="p.id" :class="{ 'row-inactive': !p.activo }">
                <td class="td-id">{{ p.id }}</td>
                <td>
                  <img :src="p.imagen_principal?.url || p.imagen_url || '/img/placeholder.jpg'" class="table-img" />
                </td>
                <td>
                  <strong>{{ p.nombre }}</strong>
                  <div class="badge-row">
                    <span v-if="p.destacado" class="mini-badge badge-destacado">
                      <i class="fa-solid fa-star"></i> Destacado
                    </span>
                    <span v-if="p.es_nueva_coleccion" class="mini-badge badge-nuevo">
                      <i class="fa-solid fa-sparkles"></i> Nuevo
                    </span>
                  </div>
                </td>
                <td>{{ p.categoria?.nombre }}</td>
                <td class="td-precio">Bs. {{ Number(p.precio_unitario).toFixed(2) }}</td>
                <td>
                  <span :class="['stock-num', p.stock_actual < 5 ? 'stock-low' : '']">
                    {{ p.stock_actual }}
                  </span>
                </td>
                <td>
                  <!-- Toggle ON/OFF -->
                  <label class="switch" :title="p.activo ? 'Desactivar' : 'Activar'" @click.prevent="toggleActivo(p.id)">
                    <input type="checkbox" :checked="p.activo" readonly />
                    <span class="switch-slider"></span>
                  </label>
                </td>
                <td v-if="$page.props.auth.user?.nivel >= 3" class="actions-cell">
                  <Link :href="route('admin.productos.edit', p.id)" class="btn btn-outline btn-xs" title="Editar">
                    <i class="fa-solid fa-pen"></i>
                  </Link>
                  <button
                    @click="toggleDestacado(p.id, 'destacado')"
                    :class="['btn btn-xs', p.destacado ? 'btn-accent' : 'btn-outline']"
                    title="Destacado"
                  >
                    <i class="fa-solid fa-star"></i>
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Paginación -->
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

function limpiar() {
  busqueda.value = ''
  categoriaFiltro.value = ''
  router.get(route('admin.productos.index'), {}, { preserveState: true })
}

function toggleDestacado(id, campo) {
  router.post(route('admin.destacados.toggle', id), { campo }, { preserveScroll: true })
}

function toggleActivo(id) {
  router.patch(route('admin.productos.toggle-activo', id), {}, { preserveScroll: true })
}
</script>

<style scoped>
.admin-page { padding: 1.5rem 0; }
.admin-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; }
.page-title { font-size: 1.5rem; display: flex; align-items: center; gap: 0.625rem; }
.filtros-inline { display: flex; gap: 0.75rem; align-items: center; flex-wrap: wrap; padding: 1rem 1.25rem; }
.table-wrap { overflow-x: auto; padding: 0; }
.admin-table { width: 100%; border-collapse: collapse; min-width: 700px; }
.admin-table th {
  text-align: left; padding: 0.75rem 1rem; font-size: 0.72rem;
  text-transform: uppercase; letter-spacing: 0.05em;
  color: var(--text-secondary); border-bottom: 2px solid var(--border-color);
  background: var(--bg-secondary); white-space: nowrap;
}
.admin-table td { padding: 0.75rem 1rem; border-bottom: 1px solid var(--border-color); font-size: 0.875rem; vertical-align: middle; }
.table-img { width: 44px; height: 56px; object-fit: cover; border-radius: var(--radius-sm); }
.row-inactive { opacity: 0.55; }
.td-id { color: var(--text-secondary); font-size: 0.8rem; }
.td-precio { font-weight: 600; white-space: nowrap; }
.stock-num { font-weight: 600; }
.stock-low { color: var(--color-danger); }
.badge-row { display: flex; gap: 0.25rem; margin-top: 0.2rem; flex-wrap: wrap; }
.mini-badge { font-size: 0.65rem; padding: 0.1rem 0.4rem; border-radius: var(--radius-pill); font-weight: 600; }
.badge-destacado { background: color-mix(in srgb, var(--color-accent) 25%, var(--bg-card)); color: var(--color-accent); }
.badge-nuevo { background: color-mix(in srgb, var(--color-primary) 20%, var(--bg-card)); color: var(--color-primary); }
.actions-cell { display: flex; gap: 0.35rem; align-items: center; }
.btn-xs { padding: 0.3rem 0.5rem; font-size: 0.8rem; }
.paginacion { display: flex; justify-content: center; gap: 0.25rem; margin-top: 1.5rem; flex-wrap: wrap; }
.pag-link { padding: 0.4rem 0.75rem; border-radius: var(--radius-sm); border: 1px solid var(--border-color); background: var(--bg-card); color: var(--text-primary); font-size: 0.85rem; text-decoration: none; transition: all 0.15s; }
.pag-link:hover { background: var(--color-primary); color: var(--text-on-primary); }
.pag-link.active { background: var(--color-primary); color: var(--text-on-primary); font-weight: 700; }
.pag-link.disabled { opacity: 0.4; pointer-events: none; }
</style>
