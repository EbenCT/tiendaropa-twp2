<template>
  <AppLayout>
    <div class="container">
      <div class="admin-page fade-in">
        <h1 class="page-title">📈 Estadísticas</h1>

        <!-- Resumen -->
        <div class="stats-summary">
          <div class="stat-card card">
            <span class="stat-icon">📦</span>
            <span class="stat-valor">{{ resumen.totalProductos }}</span>
            <span class="stat-label">Productos Activos</span>
          </div>
          <div class="stat-card card">
            <span class="stat-icon">📋</span>
            <span class="stat-valor">{{ resumen.totalPedidos }}</span>
            <span class="stat-label">Pedidos Totales</span>
          </div>
          <div class="stat-card card">
            <span class="stat-icon">💰</span>
            <span class="stat-valor">Bs. {{ Number(resumen.totalVentas || 0).toFixed(2) }}</span>
            <span class="stat-label">Ventas Totales</span>
          </div>
        </div>

        <!-- Productos más vendidos -->
        <div class="card section-card">
          <h2>🏆 Top 10 Productos Más Vendidos</h2>
          <div v-if="topProductos.length" class="top-list">
            <div v-for="(p, i) in topProductos" :key="p.id" class="top-item">
              <span class="top-rank">{{ i + 1 }}</span>
              <img :src="p.imagen_url || '/img/placeholder.jpg'" class="top-img" />
              <span class="top-nombre">{{ p.nombre }}</span>
              <div class="top-bar-wrap">
                <div class="top-bar" :style="{ width: barWidth(p.total_vendido) }"></div>
              </div>
              <span class="top-valor">{{ p.total_vendido }} uds.</span>
            </div>
          </div>
          <p v-else class="empty-text">Sin datos de ventas aún.</p>
        </div>

        <!-- Pedidos por estado -->
        <div class="card section-card">
          <h2>📊 Pedidos por Estado</h2>
          <div class="estados-grid">
            <div v-for="(count, estado) in pedidosPorEstado" :key="estado" class="estado-item">
              <span :class="['estado-badge', `estado-${estado.toLowerCase()}`]">{{ estado }}</span>
              <span class="estado-count">{{ count }}</span>
            </div>
          </div>
        </div>

        <!-- Páginas más visitadas -->
        <div class="card section-card">
          <h2>👁️ Páginas Más Visitadas</h2>
          <div v-if="topVisitas.length" class="top-list">
            <div v-for="v in topVisitas" :key="v.id" class="top-item">
              <span class="top-nombre" style="flex:2">{{ v.page_name || v.page_url }}</span>
              <div class="top-bar-wrap">
                <div class="top-bar" :style="{ width: visitBarWidth(v.visit_count) }"></div>
              </div>
              <span class="top-valor">{{ v.visit_count }} visitas</span>
            </div>
          </div>
          <p v-else class="empty-text">Sin datos de visitas.</p>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { computed } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
  topProductos:     { type: Array, default: () => [] },
  pedidosPorEstado: { type: Object, default: () => ({}) },
  topVisitas:       { type: Array, default: () => [] },
  resumen:          { type: Object, default: () => ({}) },
})

const maxVendido = computed(() => Math.max(...props.topProductos.map(p => p.total_vendido), 1))
const maxVisitas = computed(() => Math.max(...props.topVisitas.map(v => v.visit_count), 1))
function barWidth(val) { return Math.round((val / maxVendido.value) * 100) + '%' }
function visitBarWidth(val) { return Math.round((val / maxVisitas.value) * 100) + '%' }
</script>

<style scoped>
.admin-page { padding:2rem 0; }
.page-title { font-size:1.75rem; margin-bottom:2rem; }
.stats-summary { display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:1.25rem; margin-bottom:2rem; }
.stat-card { display:flex; flex-direction:column; align-items:center; gap:0.5rem; padding:1.5rem; text-align:center; }
.stat-icon { font-size:2rem; }
.stat-valor { font-size:1.75rem; font-weight:700; color:var(--color-accent); }
.stat-label { font-size:0.8rem; color:var(--text-secondary); text-transform:uppercase; }
.section-card { margin-bottom:2rem; }
.section-card h2 { font-size:1.25rem; margin-bottom:1.25rem; }
.top-list { display:flex; flex-direction:column; gap:0.5rem; }
.top-item { display:flex; align-items:center; gap:0.75rem; padding:0.5rem 0; }
.top-rank { width:28px; height:28px; display:flex; align-items:center; justify-content:center; border-radius:50%; background:var(--bg-secondary); font-weight:700; font-size:0.8rem; }
.top-img { width:40px; height:50px; object-fit:cover; border-radius:var(--radius-sm); }
.top-nombre { flex:1; font-weight:500; font-size:0.9rem; }
.top-bar-wrap { flex:1; height:8px; background:var(--bg-secondary); border-radius:4px; overflow:hidden; }
.top-bar { height:100%; background:linear-gradient(90deg, var(--color-primary), var(--color-accent)); border-radius:4px; transition:width 0.5s ease; }
.top-valor { font-weight:700; font-size:0.85rem; min-width:80px; text-align:right; }
.estados-grid { display:flex; flex-wrap:wrap; gap:1rem; }
.estado-item { display:flex; align-items:center; gap:0.5rem; }
.estado-badge { padding:0.25rem 0.75rem; border-radius:var(--radius-pill); font-size:0.75rem; font-weight:700; text-transform:uppercase; }
.estado-pendiente { background:color-mix(in srgb, var(--color-warning) 20%, var(--bg-card)); color:var(--color-warning); }
.estado-confirmado { background:color-mix(in srgb, var(--color-info) 20%, var(--bg-card)); color:var(--color-info); }
.estado-enviado { background:color-mix(in srgb, var(--color-primary) 20%, var(--bg-card)); color:var(--color-primary); }
.estado-entregado { background:color-mix(in srgb, var(--color-success) 20%, var(--bg-card)); color:var(--color-success); }
.estado-count { font-size:1.5rem; font-weight:700; }
.empty-text { color:var(--text-secondary); font-size:0.9rem; }
</style>
