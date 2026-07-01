<template>
  <AppLayout>
    <div class="container">
      <div class="admin-page fade-in">

        <h1 class="page-title">
          <i class="fa-solid fa-chart-line"></i> Estadísticas
        </h1>

        <!-- KPIs -->
        <div class="kpi-grid">
          <div class="kpi-card card">
            <div class="kpi-icon kpi-blue">
              <i class="fa-solid fa-box"></i>
            </div>
            <div class="kpi-body">
              <p class="kpi-valor">{{ resumen.totalProductos }}</p>
              <p class="kpi-label">Productos activos</p>
            </div>
          </div>
          <div class="kpi-card card">
            <div class="kpi-icon kpi-violet">
              <i class="fa-solid fa-clipboard-list"></i>
            </div>
            <div class="kpi-body">
              <p class="kpi-valor">{{ resumen.totalPedidos }}</p>
              <p class="kpi-label">Pedidos totales</p>
            </div>
          </div>
          <div class="kpi-card card">
            <div class="kpi-icon kpi-green">
              <i class="fa-solid fa-money-bill-wave"></i>
            </div>
            <div class="kpi-body">
              <p class="kpi-valor">Bs. {{ Number(resumen.totalVentas || 0).toLocaleString('es-BO', {minimumFractionDigits:2}) }}</p>
              <p class="kpi-label">Ingresos totales</p>
            </div>
          </div>
          <div class="kpi-card card">
            <div class="kpi-icon kpi-orange">
              <i class="fa-solid fa-users"></i>
            </div>
            <div class="kpi-body">
              <p class="kpi-valor">{{ resumen.totalUsuarios || '—' }}</p>
              <p class="kpi-label">Usuarios registrados</p>
            </div>
          </div>
        </div>

        <!-- Pedidos por estado -->
        <div class="card section-card">
          <h2 class="section-title">
            <i class="fa-solid fa-chart-pie"></i> Pedidos por Estado
          </h2>
          <div class="estados-grid">
            <div v-for="(count, estado) in pedidosPorEstado" :key="estado" class="estado-item">
              <div class="estado-icon" :class="`est-${estado.toLowerCase()}`">
                <i :class="estadoIcon(estado)"></i>
              </div>
              <div>
                <p class="estado-count">{{ count }}</p>
                <p class="estado-label">{{ estado.charAt(0) + estado.slice(1).toLowerCase() }}</p>
              </div>
            </div>
          </div>
        </div>

        <div class="two-col">
          <!-- Top productos vendidos -->
          <div class="card section-card">
            <h2 class="section-title">
              <i class="fa-solid fa-trophy"></i> Top 10 Productos Vendidos
            </h2>
            <div v-if="topProductos.length" class="top-list">
              <div v-for="(p, i) in topProductos" :key="p.id" class="top-item">
                <span class="top-rank" :class="i < 3 ? `rank-${i+1}` : ''">{{ i + 1 }}</span>
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

          <!-- Páginas más visitadas -->
          <div class="card section-card">
            <h2 class="section-title">
              <i class="fa-solid fa-eye"></i> Páginas Más Visitadas
            </h2>
            <div v-if="topVisitas.length" class="top-list">
              <div v-for="v in topVisitas" :key="v.id" class="top-item">
                <div class="top-bar-wrap" style="flex:2">
                  <span class="top-url">{{ v.page_name || v.page_url }}</span>
                  <div class="top-bar-sub">
                    <div class="top-bar top-bar-vis" :style="{ width: visitBarWidth(v.visit_count) }"></div>
                  </div>
                </div>
                <span class="top-valor">{{ v.visit_count }}</span>
              </div>
            </div>
            <p v-else class="empty-text">Sin datos de visitas.</p>
          </div>
        </div>

      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { computed } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
  topProductos:     { type: Array,  default: () => [] },
  pedidosPorEstado: { type: Object, default: () => ({}) },
  topVisitas:       { type: Array,  default: () => [] },
  resumen:          { type: Object, default: () => ({}) },
})

const maxVendido = computed(() => Math.max(...props.topProductos.map(p => p.total_vendido), 1))
const maxVisitas = computed(() => Math.max(...props.topVisitas.map(v => v.visit_count), 1))
function barWidth(val)      { return Math.round((val / maxVendido.value) * 100) + '%' }
function visitBarWidth(val) { return Math.round((val / maxVisitas.value) * 100) + '%' }

function estadoIcon(estado) {
  return {
    'PENDIENTE':  'fa-solid fa-clock',
    'CONFIRMADO': 'fa-solid fa-circle-check',
    'ENVIADO':    'fa-solid fa-truck',
    'ENTREGADO':  'fa-solid fa-house-circle-check',
  }[estado] || 'fa-solid fa-circle-dot'
}
</script>

<style scoped>
.admin-page { padding: 1.5rem 0; }
.page-title { font-size: 1.5rem; display: flex; align-items: center; gap: 0.625rem; margin-bottom: 1.5rem; }
.section-card { margin-bottom: 1.5rem; }
.section-title { font-size: 1.05rem; margin-bottom: 1.25rem; display: flex; align-items: center; gap: 0.5rem; }

/* KPIs */
.kpi-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1.5rem; }
.kpi-card { display: flex; align-items: center; gap: 1rem; padding: 1.25rem; }
.kpi-icon { width: 52px; height: 52px; border-radius: var(--radius); display: flex; align-items: center; justify-content: center; font-size: 1.3rem; flex-shrink: 0; }
.kpi-blue   { background: color-mix(in srgb, var(--color-info) 18%, var(--bg-card)); color: var(--color-info); }
.kpi-violet { background: color-mix(in srgb, var(--color-primary) 18%, var(--bg-card)); color: var(--color-primary); }
.kpi-green  { background: color-mix(in srgb, var(--color-success) 18%, var(--bg-card)); color: var(--color-success); }
.kpi-orange { background: color-mix(in srgb, var(--color-warning) 18%, var(--bg-card)); color: var(--color-warning); }
.kpi-valor { font-size: 1.35rem; font-weight: 700; color: var(--text-primary); }
.kpi-label { font-size: 0.72rem; color: var(--text-secondary); margin-top: 0.1rem; }

/* Estados */
.estados-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 1rem; }
.estado-item { display: flex; align-items: center; gap: 0.875rem; padding: 0.875rem; background: var(--bg-secondary); border-radius: var(--radius-sm); }
.estado-icon { width: 40px; height: 40px; border-radius: var(--radius-sm); display: flex; align-items: center; justify-content: center; font-size: 1rem; }
.est-pendiente  { background: color-mix(in srgb, var(--color-warning) 20%, var(--bg-card)); color: var(--color-warning); }
.est-confirmado { background: color-mix(in srgb, var(--color-info) 20%, var(--bg-card)); color: var(--color-info); }
.est-enviado    { background: color-mix(in srgb, var(--color-primary) 20%, var(--bg-card)); color: var(--color-primary); }
.est-entregado  { background: color-mix(in srgb, var(--color-success) 20%, var(--bg-card)); color: var(--color-success); }
.estado-count { font-size: 1.5rem; font-weight: 700; color: var(--text-primary); }
.estado-label { font-size: 0.72rem; color: var(--text-secondary); }

/* Dos columnas */
.two-col { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; }
@media (max-width: 900px) { .two-col { grid-template-columns: 1fr; } }

/* Top list */
.top-list { display: flex; flex-direction: column; gap: 0.4rem; }
.top-item { display: flex; align-items: center; gap: 0.625rem; padding: 0.4rem 0; border-bottom: 1px solid var(--border-color); }
.top-rank { width: 26px; height: 26px; border-radius: 50%; background: var(--bg-secondary); display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.75rem; flex-shrink: 0; }
.rank-1 { background: #FFD700; color: #333; }
.rank-2 { background: #C0C0C0; color: #333; }
.rank-3 { background: #CD7F32; color: white; }
.top-img { width: 36px; height: 44px; object-fit: cover; border-radius: var(--radius-sm); flex-shrink: 0; }
.top-nombre { flex: 1; font-weight: 500; font-size: 0.85rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.top-bar-wrap { flex: 1; }
.top-url { font-size: 0.78rem; color: var(--text-secondary); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; display: block; }
.top-bar-sub { height: 6px; background: var(--bg-secondary); border-radius: 3px; margin-top: 0.2rem; overflow: hidden; }
.top-bar { height: 8px; background: linear-gradient(90deg, var(--color-primary), var(--color-accent)); border-radius: 4px; transition: width 0.4s; }
.top-bar-vis { height: 100%; border-radius: 3px; }
.top-valor { font-weight: 700; font-size: 0.82rem; min-width: 60px; text-align: right; color: var(--color-accent); }
.empty-text { color: var(--text-secondary); font-size: 0.875rem; }
</style>
