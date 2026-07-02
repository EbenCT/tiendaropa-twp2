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
            <div class="kpi-icon kpi-blue"><i class="fa-solid fa-box"></i></div>
            <div class="kpi-body">
              <p class="kpi-valor">{{ resumen.totalProductos }}</p>
              <p class="kpi-label">Productos activos</p>
            </div>
          </div>
          <div class="kpi-card card">
            <div class="kpi-icon kpi-violet"><i class="fa-solid fa-clipboard-list"></i></div>
            <div class="kpi-body">
              <p class="kpi-valor">{{ resumen.totalPedidos }}</p>
              <p class="kpi-label">Pedidos totales</p>
            </div>
          </div>
          <div class="kpi-card card">
            <div class="kpi-icon kpi-green"><i class="fa-solid fa-money-bill-wave"></i></div>
            <div class="kpi-body">
              <p class="kpi-valor">Bs. {{ fmt(resumen.totalVentas) }}</p>
              <p class="kpi-label">Ingresos totales</p>
            </div>
          </div>
          <div class="kpi-card card">
            <div class="kpi-icon kpi-orange"><i class="fa-solid fa-users"></i></div>
            <div class="kpi-body">
              <p class="kpi-valor">{{ resumen.totalUsuarios || '—' }}</p>
              <p class="kpi-label">Usuarios registrados</p>
            </div>
          </div>
        </div>

        <!-- Fila 1: Ventas por mes + Pedidos por estado -->
        <div class="two-col">
          <div class="card section-card">
            <h2 class="section-title"><i class="fa-solid fa-chart-column"></i> Ventas últimos 12 meses</h2>
            <div class="chart-wrap">
              <canvas ref="canvasVentas"></canvas>
            </div>
          </div>

          <div class="card section-card">
            <h2 class="section-title"><i class="fa-solid fa-chart-pie"></i> Pedidos por Estado</h2>
            <div class="chart-wrap chart-wrap-pie">
              <canvas ref="canvasEstados"></canvas>
            </div>
          </div>
        </div>

        <!-- Fila 2: Top productos + Roles de usuarios -->
        <div class="two-col">
          <div class="card section-card">
            <h2 class="section-title"><i class="fa-solid fa-trophy"></i> Top 10 Productos Vendidos</h2>
            <div class="chart-wrap">
              <canvas ref="canvasProductos"></canvas>
            </div>
          </div>

          <div class="card section-card">
            <h2 class="section-title"><i class="fa-solid fa-users-gear"></i> Distribución de Usuarios por Rol</h2>
            <div class="chart-wrap chart-wrap-pie">
              <canvas ref="canvasRoles"></canvas>
            </div>
          </div>
        </div>

        <!-- Páginas más visitadas -->
        <div class="card section-card">
          <h2 class="section-title"><i class="fa-solid fa-eye"></i> Páginas Más Visitadas</h2>
          <div class="chart-wrap">
            <canvas ref="canvasVisitas"></canvas>
          </div>
        </div>

        <!-- Bitácora rápida -->
        <div v-if="ultimasBitacora.length" class="card section-card">
          <h2 class="section-title">
            <i class="fa-solid fa-scroll"></i> Últimos Eventos del Sistema
            <a :href="route('admin.bitacora')" class="ver-mas">Ver todo <i class="fa-solid fa-arrow-right"></i></a>
          </h2>
          <div class="bit-list">
            <div v-for="r in ultimasBitacora" :key="r.fecha + r.accion" class="bit-row">
              <span class="badge" :class="badgeClass(r.accion)">{{ r.accion }}</span>
              <span class="bit-desc">{{ r.descripcion }}</span>
              <span class="bit-user">{{ r.usuario || '—' }}</span>
              <span class="bit-fecha">{{ r.fecha }}</span>
            </div>
          </div>
        </div>

      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import {
  Chart,
  BarController, BarElement,
  LineController, LineElement, PointElement,
  DoughnutController, ArcElement,
  CategoryScale, LinearScale,
  Tooltip, Legend,
} from 'chart.js'

Chart.register(
  BarController, BarElement,
  LineController, LineElement, PointElement,
  DoughnutController, ArcElement,
  CategoryScale, LinearScale,
  Tooltip, Legend
)

const props = defineProps({
  topProductos:     { type: Array,  default: () => [] },
  pedidosPorEstado: { type: Object, default: () => ({}) },
  topVisitas:       { type: Array,  default: () => [] },
  ventasPorMes:     { type: Array,  default: () => [] },
  usuariosPorRol:   { type: Object, default: () => ({}) },
  ultimasBitacora:  { type: Array,  default: () => [] },
  resumen:          { type: Object, default: () => ({}) },
})

const canvasVentas   = ref(null)
const canvasEstados  = ref(null)
const canvasProductos= ref(null)
const canvasRoles    = ref(null)
const canvasVisitas  = ref(null)

const MESES = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic']

const PALETA = [
  '#7C3AED','#10B981','#F59E0B','#EF4444','#3B82F6','#EC4899',
  '#14B8A6','#8B5CF6','#F97316','#06B6D4',
]

const colorPrimario = () => getComputedStyle(document.documentElement).getPropertyValue('--color-primary').trim() || '#7C3AED'

function fmt(v) {
  return Number(v || 0).toLocaleString('es-BO', { minimumFractionDigits: 2 })
}

// Texto de Chart.js en el color del tema
function chartTextColor() {
  return getComputedStyle(document.body).getPropertyValue('--text-primary').trim() || '#333'
}
function chartGridColor() {
  return getComputedStyle(document.body).getPropertyValue('--border-color').trim() || '#ddd'
}

onMounted(() => {
  const textColor = chartTextColor()
  const gridColor = chartGridColor()

  // 1. Ventas por mes (línea + barras)
  if (canvasVentas.value) {
    const meses12 = buildMeses12()
    new Chart(canvasVentas.value, {
      type: 'bar',
      data: {
        labels: meses12.map(m => m.label),
        datasets: [{
          label: 'Ingresos (Bs.)',
          data: meses12.map(m => m.total),
          backgroundColor: PALETA[0] + 'CC',
          borderColor: PALETA[0],
          borderRadius: 5,
        }],
      },
      options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
          x: { ticks: { color: textColor }, grid: { color: gridColor } },
          y: { ticks: { color: textColor, callback: v => 'Bs. ' + v }, grid: { color: gridColor } },
        },
      },
    })
  }

  // 2. Pedidos por estado (dona)
  if (canvasEstados.value && Object.keys(props.pedidosPorEstado).length) {
    const labels = Object.keys(props.pedidosPorEstado)
    const data   = Object.values(props.pedidosPorEstado)
    new Chart(canvasEstados.value, {
      type: 'doughnut',
      data: {
        labels,
        datasets: [{ data, backgroundColor: PALETA, borderWidth: 2 }],
      },
      options: {
        responsive: true, maintainAspectRatio: false,
        plugins: {
          legend: { position: 'bottom', labels: { color: textColor, padding: 12 } },
          tooltip: { callbacks: { label: ctx => ` ${ctx.label}: ${ctx.parsed} pedidos` } },
        },
      },
    })
  }

  // 3. Top productos (barras horizontales)
  if (canvasProductos.value && props.topProductos.length) {
    new Chart(canvasProductos.value, {
      type: 'bar',
      data: {
        labels: props.topProductos.map(p => truncar(p.nombre, 22)),
        datasets: [{
          label: 'Unidades vendidas',
          data: props.topProductos.map(p => p.total_vendido),
          backgroundColor: PALETA,
          borderRadius: 4,
        }],
      },
      options: {
        indexAxis: 'y',
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
          x: { ticks: { color: textColor }, grid: { color: gridColor } },
          y: { ticks: { color: textColor, font: { size: 11 } }, grid: { display: false } },
        },
      },
    })
  }

  // 4. Distribución de usuarios por rol (dona)
  if (canvasRoles.value && Object.keys(props.usuariosPorRol).length) {
    const labelsMapa = { admin: 'Admin', propietario: 'Propietario', vendedor: 'Vendedor', cliente: 'Cliente' }
    const labels = Object.keys(props.usuariosPorRol).map(k => labelsMapa[k] || k)
    const data   = Object.values(props.usuariosPorRol)
    new Chart(canvasRoles.value, {
      type: 'doughnut',
      data: {
        labels,
        datasets: [{ data, backgroundColor: PALETA, borderWidth: 2 }],
      },
      options: {
        responsive: true, maintainAspectRatio: false,
        plugins: {
          legend: { position: 'bottom', labels: { color: textColor, padding: 12 } },
          tooltip: { callbacks: { label: ctx => ` ${ctx.label}: ${ctx.parsed} usuarios` } },
        },
      },
    })
  }

  // 5. Visitas por página (barras horizontales)
  if (canvasVisitas.value && props.topVisitas.length) {
    new Chart(canvasVisitas.value, {
      type: 'bar',
      data: {
        labels: props.topVisitas.map(v => v.page_name || v.page_url),
        datasets: [{
          label: 'Visitas',
          data: props.topVisitas.map(v => v.visit_count),
          backgroundColor: PALETA[4] + 'CC',
          borderColor: PALETA[4],
          borderRadius: 4,
        }],
      },
      options: {
        indexAxis: 'y',
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
          x: { ticks: { color: textColor }, grid: { color: gridColor } },
          y: { ticks: { color: textColor, font: { size: 11 } }, grid: { display: false } },
        },
      },
    })
  }
})

function buildMeses12() {
  const ahora = new Date()
  const resultado = []
  for (let i = 11; i >= 0; i--) {
    const d = new Date(ahora.getFullYear(), ahora.getMonth() - i, 1)
    const mes  = d.getMonth() + 1
    const anio = d.getFullYear()
    const found = props.ventasPorMes.find(v => v.mes === mes && v.anio === anio)
    resultado.push({ label: MESES[mes - 1] + ' ' + String(anio).slice(2), total: found?.total ?? 0 })
  }
  return resultado
}

function truncar(str, max) {
  return str.length > max ? str.slice(0, max) + '…' : str
}

function badgeClass(accion) {
  return {
    LOGIN: 'badge-info', LOGIN_FALLIDO: 'badge-danger',
    LOGOUT: 'badge-secondary', PAGO: 'badge-success',
    INVENTARIO: 'badge-warning', REGISTRO: 'badge-primary',
  }[accion] || 'badge-secondary'
}
</script>

<style scoped>
.admin-page { padding: 1.5rem 0; }
.page-title { font-size: 1.5rem; display: flex; align-items: center; gap: 0.625rem; margin-bottom: 1.5rem; }

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

/* Layout */
.two-col { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem; }
@media (max-width: 900px) { .two-col { grid-template-columns: 1fr; } }
.section-card { margin-bottom: 1.5rem; }
.section-title { font-size: 1.05rem; margin-bottom: 1.25rem; display: flex; align-items: center; gap: 0.5rem; }

/* Charts */
.chart-wrap     { position: relative; height: 260px; }
.chart-wrap-pie { height: 240px; }

/* Bitácora rápida */
.ver-mas { margin-left: auto; font-size: 0.8rem; color: var(--color-primary); text-decoration: none; display: flex; align-items: center; gap: 0.3rem; }
.bit-list { display: flex; flex-direction: column; gap: 0; }
.bit-row { display: grid; grid-template-columns: auto 1fr auto auto; align-items: center; gap: 0.75rem;
  padding: 0.5rem 0; border-bottom: 1px solid var(--border-color); font-size: 0.82rem; }
.bit-desc { color: var(--text-primary); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.bit-user { color: var(--text-secondary); white-space: nowrap; }
.bit-fecha { color: var(--text-secondary); white-space: nowrap; font-size: 0.75rem; }

/* Badges */
.badge { display: inline-block; padding: 0.2rem 0.5rem; border-radius: 4px; font-size: 0.68rem; font-weight: 700; white-space: nowrap; }
.badge-info      { background: color-mix(in srgb, var(--color-info) 20%, transparent); color: var(--color-info); }
.badge-danger    { background: color-mix(in srgb, #e53e3e 20%, transparent); color: #e53e3e; }
.badge-secondary { background: var(--bg-secondary); color: var(--text-secondary); }
.badge-success   { background: color-mix(in srgb, var(--color-success) 20%, transparent); color: var(--color-success); }
.badge-warning   { background: color-mix(in srgb, var(--color-warning) 20%, transparent); color: var(--color-warning); }
.badge-primary   { background: color-mix(in srgb, var(--color-primary) 20%, transparent); color: var(--color-primary); }
</style>
