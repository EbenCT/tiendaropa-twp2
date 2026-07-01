<template>
  <AppLayout>
    <div class="container">
      <div class="admin-page fade-in">
        <div class="admin-header">
          <h1 class="page-title"><i class="fa-solid fa-warehouse"></i> Inventario</h1>
          <Link :href="route('admin.inventario.create')" class="btn btn-primary">
            <i class="fa-solid fa-plus"></i> Registrar Movimiento
          </Link>
        </div>

        <div class="table-wrap card">
          <table class="admin-table">
            <thead>
              <tr><th>ID</th><th>Producto</th><th>Tipo</th><th>Cantidad</th><th>Stock Actual</th><th>Técnica</th><th>Fecha</th></tr>
            </thead>
            <tbody>
              <tr v-for="m in movimientos.data" :key="m.id">
                <td>{{ m.id }}</td>
                <td>{{ m.producto?.nombre }}</td>
                <td><span :class="['tipo-badge', m.tipo === 'INGRESO' ? 'ingreso' : 'salida']">{{ m.tipo === 'INGRESO' ? 'Ingreso' : 'Salida' }}</span></td>
                <td>{{ m.cantidad }}</td>
                <td><span class="stock-val">{{ m.producto?.stock_actual ?? '—' }}</span></td>
                <td>{{ m.tecnica }}</td>
                <td>{{ m.fecha ? new Date(m.fecha).toLocaleDateString('es-BO') : '—' }}</td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-if="movimientos.last_page > 1" class="paginacion">
          <template v-for="link in movimientos.links" :key="link.label">
            <Link v-if="link.url" :href="link.url" :class="['pag-link', { active: link.active }]" v-html="link.label" />
          </template>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { Link } from '@inertiajs/vue3'
defineProps({ movimientos: Object, productos: Array })
</script>

<style scoped>
.admin-page { padding:2rem 0; }
.admin-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem; }
.page-title { font-size:1.75rem; }
.table-wrap { overflow-x:auto; }
.admin-table { width:100%; border-collapse:collapse; }
.admin-table th { text-align:left; padding:0.75rem; font-size:0.75rem; text-transform:uppercase; color:var(--text-secondary); border-bottom:2px solid var(--border-color); }
.admin-table td { padding:0.75rem; border-bottom:1px solid var(--border-color); font-size:0.875rem; }
.tipo-badge { padding:0.15rem 0.5rem; border-radius:var(--radius-pill); font-size:0.7rem; font-weight:700; text-transform:uppercase; }
.tipo-badge.ingreso { background:color-mix(in srgb, var(--color-success) 20%, var(--bg-card)); color:var(--color-success); }
.tipo-badge.salida { background:color-mix(in srgb, var(--color-danger) 20%, var(--bg-card)); color:var(--color-danger); }
.stock-val { font-weight:600; color:var(--color-accent); }
.paginacion { display:flex; justify-content:center; gap:0.25rem; margin-top:1.5rem; }
.pag-link { padding:0.4rem 0.75rem; border-radius:var(--radius-sm); border:1px solid var(--border-color); background:var(--bg-card); color:var(--text-primary); font-size:0.85rem; text-decoration:none; }
.pag-link.active { background:var(--color-primary); color:var(--text-on-primary); }
</style>
