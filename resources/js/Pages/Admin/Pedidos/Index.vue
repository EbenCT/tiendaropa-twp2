<template>
  <AppLayout>
    <div class="container">
      <div class="admin-page fade-in">
        <h1 class="page-title">📋 Gestión de Pedidos</h1>
        <div class="filtros-inline card" style="margin-bottom:1.5rem">
          <select v-model="estadoFiltro" @change="filtrar" class="input" style="max-width:200px">
            <option value="">Todos los estados</option>
            <option value="PENDIENTE">Pendiente</option>
            <option value="CONFIRMADO">Confirmado</option>
            <option value="ENVIADO">Enviado</option>
            <option value="ENTREGADO">Entregado</option>
          </select>
        </div>

        <div class="table-wrap card">
          <table class="admin-table">
            <thead>
              <tr><th>ID</th><th>Cliente</th><th>Dirección</th><th>Estado</th><th>Productos</th><th>Total</th><th>Fecha</th><th>Acciones</th></tr>
            </thead>
            <tbody>
              <tr v-for="p in pedidos.data" :key="p.id">
                <td>#{{ p.id }}</td>
                <td>{{ p.usuario?.nombre }} {{ p.usuario?.apellido }}</td>
                <td class="td-truncate">{{ p.direccion }}</td>
                <td>
                  <select :value="p.estado" @change="cambiarEstado(p.id, $event.target.value)" class="input input-sm">
                    <option value="PENDIENTE">Pendiente</option>
                    <option value="CONFIRMADO">Confirmado</option>
                    <option value="ENVIADO">Enviado</option>
                    <option value="ENTREGADO">Entregado</option>
                  </select>
                </td>
                <td>{{ p.detalles?.length || 0 }}</td>
                <td>Bs. {{ Number(p.total || 0).toFixed(2) }}</td>
                <td>{{ new Date(p.created_at).toLocaleDateString('es-BO') }}</td>
                <td><Link :href="route('admin.pedidos.show', p.id)" class="btn btn-outline btn-xs">Ver</Link></td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-if="pedidos.last_page > 1" class="paginacion">
          <template v-for="link in pedidos.links" :key="link.label">
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
defineProps({ pedidos: Object })
const estadoFiltro = ref('')
function filtrar() { router.get(route('admin.pedidos.index'), estadoFiltro.value ? { estado: estadoFiltro.value } : {}, { preserveState: true }) }
function cambiarEstado(id, estado) { router.patch(route('admin.pedidos.estado', id), { estado }, { preserveScroll: true }) }
</script>

<style scoped>
.admin-page { padding:2rem 0; }
.page-title { font-size:1.75rem; margin-bottom:1.5rem; }
.filtros-inline { display:flex; gap:0.75rem; align-items:center; }
.table-wrap { overflow-x:auto; }
.admin-table { width:100%; border-collapse:collapse; }
.admin-table th { text-align:left; padding:0.75rem; font-size:0.75rem; text-transform:uppercase; color:var(--text-secondary); border-bottom:2px solid var(--border-color); }
.admin-table td { padding:0.75rem; border-bottom:1px solid var(--border-color); font-size:0.875rem; }
.td-truncate { max-width:200px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.input-sm { padding:0.3rem 0.5rem; font-size:0.8rem; }
.btn-xs { padding:0.25rem 0.5rem; font-size:0.75rem; }
.paginacion { display:flex; justify-content:center; gap:0.25rem; margin-top:1.5rem; }
.pag-link { padding:0.4rem 0.75rem; border-radius:var(--radius-sm); border:1px solid var(--border-color); background:var(--bg-card); color:var(--text-primary); font-size:0.85rem; text-decoration:none; }
.pag-link.active { background:var(--color-primary); color:var(--text-on-primary); }
</style>
