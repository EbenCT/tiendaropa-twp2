<template>
  <AppLayout>
    <div class="container">
      <div class="catalogo-page fade-in">
        <h1 class="page-title">Catálogo</h1>

        <!-- Filtros -->
        <div class="filtros-bar card">
          <div class="filtros-grid">
            <select v-model="filtrosLocal.catalogo" @change="aplicarFiltros" class="input">
              <option value="">Todos los catálogos</option>
              <option v-for="c in catalogos" :key="c.id" :value="c.id">{{ c.nombre }}</option>
            </select>
            <select v-model="filtrosLocal.categoria" @change="aplicarFiltros" class="input">
              <option value="">Todas las categorías</option>
              <option v-for="c in categorias" :key="c.id" :value="c.id">{{ c.nombre }}</option>
            </select>
            <select v-model="filtrosLocal.talla" @change="aplicarFiltros" class="input">
              <option value="">Todas las tallas</option>
              <option v-for="t in tallas" :key="t.id" :value="t.id">{{ t.codigo }} ({{ t.tipo }})</option>
            </select>
            <input v-model="filtrosLocal.precio_min" @change="aplicarFiltros" type="number" class="input" placeholder="Precio mín." min="0" />
            <input v-model="filtrosLocal.precio_max" @change="aplicarFiltros" type="number" class="input" placeholder="Precio máx." min="0" />
            <input v-model="filtrosLocal.q" @keyup.enter="aplicarFiltros" type="text" class="input" placeholder="Buscar..." />
          </div>
          <div class="filtros-actions">
            <button @click="aplicarFiltros" class="btn btn-primary">Filtrar</button>
            <button @click="limpiarFiltros" class="btn btn-outline">Limpiar</button>
          </div>
        </div>

        <!-- Grid de productos -->
        <div v-if="productos.data.length" class="productos-grid">
          <ProductoCard v-for="p in productos.data" :key="p.id" :producto="p" />
        </div>
        <div v-else class="empty-state">
          <p>🔍 No se encontraron productos con los filtros seleccionados.</p>
        </div>

        <!-- Paginación -->
        <div v-if="productos.last_page > 1" class="paginacion">
          <template v-for="link in productos.links" :key="link.label">
            <Link
              v-if="link.url"
              :href="link.url"
              :class="['pag-link', { active: link.active }]"
              v-html="link.label"
              preserve-scroll
            />
            <span v-else class="pag-link disabled" v-html="link.label" />
          </template>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { reactive } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import ProductoCard from '@/Components/ProductoCard.vue'
import { Link, router } from '@inertiajs/vue3'

const props = defineProps({
  productos:  { type: Object, required: true },
  catalogos:  { type: Array, default: () => [] },
  categorias: { type: Array, default: () => [] },
  tallas:     { type: Array, default: () => [] },
  filtros:    { type: Object, default: () => ({}) },
})

const filtrosLocal = reactive({ ...props.filtros })

function aplicarFiltros() {
  const params = {}
  Object.entries(filtrosLocal).forEach(([k, v]) => { if (v) params[k] = v })
  router.get(route('catalogo'), params, { preserveState: true, preserveScroll: true })
}

function limpiarFiltros() {
  Object.keys(filtrosLocal).forEach(k => filtrosLocal[k] = '')
  router.get(route('catalogo'), {}, { preserveState: true })
}
</script>

<style scoped>
.catalogo-page { padding:2rem 0; }
.page-title { font-size:2rem; margin-bottom:1.5rem; }
.filtros-bar { margin-bottom:2rem; }
.filtros-grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(180px, 1fr)); gap:0.75rem; margin-bottom:1rem; }
.filtros-actions { display:flex; gap:0.75rem; }
.empty-state { text-align:center; padding:4rem 0; color:var(--text-secondary); font-size:1.1rem; }
.paginacion { display:flex; justify-content:center; gap:0.25rem; margin-top:2rem; flex-wrap:wrap; }
.pag-link { padding:0.4rem 0.75rem; border-radius:var(--radius-sm); border:1px solid var(--border-color); background:var(--bg-card); color:var(--text-primary); font-size:0.85rem; cursor:pointer; transition:all 0.2s; text-decoration:none; }
.pag-link:hover { background:var(--color-primary); color:var(--text-on-primary); }
.pag-link.active { background:var(--color-primary); color:var(--text-on-primary); font-weight:700; }
.pag-link.disabled { opacity:0.4; cursor:default; pointer-events:none; }
</style>
