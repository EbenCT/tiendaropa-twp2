<template>
  <AppLayout>
    <div class="container">
      <div class="admin-page fade-in">

        <!-- Encabezado -->
        <div class="page-header">
          <h1 class="page-title">
            <i class="fa-solid fa-chart-bar"></i> Reportes de Ventas
          </h1>
          <button @click="imprimirPDF" class="btn btn-outline">
            <i class="fa-solid fa-file-pdf"></i> Exportar PDF
          </button>
        </div>

        <!-- Filtros -->
        <div class="card filtros-card" id="no-print">
          <div class="filtros-row">
            <div class="filtro-item">
              <label class="form-label">Año</label>
              <select v-model="filtros.anio" @change="aplicar" class="input">
                <option v-for="a in aniosDisponibles" :key="a" :value="a">{{ a }}</option>
              </select>
            </div>
            <div class="filtro-item">
              <label class="form-label">Mes</label>
              <select v-model="filtros.mes" @change="aplicar" class="input">
                <option value="">Todos los meses</option>
                <option v-for="(nombre, i) in nombresMeses" :key="i+1" :value="i+1">{{ nombre }}</option>
              </select>
            </div>
            <div class="filtro-item">
              <label class="form-label">Categoría</label>
              <select v-model="filtros.categoria" @change="aplicar" class="input">
                <option value="">Todas las categorías</option>
                <option v-for="c in categorias" :key="c.id" :value="c.id">{{ c.nombre }}</option>
              </select>
            </div>
            <button @click="limpiar" class="btn btn-outline" style="align-self:flex-end">
              <i class="fa-solid fa-rotate-left"></i> Limpiar
            </button>
          </div>
        </div>

        <!-- Resumen KPIs -->
        <div class="kpi-grid">
          <div class="kpi-card card">
            <div class="kpi-icon" style="background:color-mix(in srgb,var(--color-primary) 15%,var(--bg-card))">
              <i class="fa-solid fa-receipt" style="color:var(--color-primary)"></i>
            </div>
            <div>
              <p class="kpi-valor">{{ totalVentas }}</p>
              <p class="kpi-label">Ventas {{ periodoLabel }}</p>
            </div>
          </div>
          <div class="kpi-card card">
            <div class="kpi-icon" style="background:color-mix(in srgb,var(--color-success) 15%,var(--bg-card))">
              <i class="fa-solid fa-money-bill-wave" style="color:var(--color-success)"></i>
            </div>
            <div>
              <p class="kpi-valor">Bs. {{ Number(totalAnual).toLocaleString('es-BO', {minimumFractionDigits:2}) }}</p>
              <p class="kpi-label">Ingresos {{ periodoLabel }}</p>
            </div>
          </div>
          <div class="kpi-card card">
            <div class="kpi-icon" style="background:color-mix(in srgb,var(--color-accent) 20%,var(--bg-card))">
              <i class="fa-solid fa-tags" style="color:var(--color-accent)"></i>
            </div>
            <div>
              <p class="kpi-valor">Bs. {{ totalVentas > 0 ? Number(totalAnual / totalVentas).toFixed(2) : '0.00' }}</p>
              <p class="kpi-label">Ticket promedio</p>
            </div>
          </div>
        </div>

        <!-- Gráfico barras mensuales -->
        <div class="card section-card">
          <h2 class="section-title">
            <i class="fa-solid fa-chart-column"></i>
            Ventas por mes — {{ anio }}
          </h2>
          <div v-if="mesesCompletos.some(m => m.total > 0)" class="chart-bars">
            <div v-for="mes in mesesCompletos" :key="mes.mes" class="chart-bar-row">
              <span class="chart-mes">{{ mes.nombreCorto }}</span>
              <div class="chart-bar-wrap">
                <div class="chart-bar" :style="{ width: barWidth(mes.total) }">
                  <span v-if="mes.total > 0" class="chart-bar-label">Bs. {{ Number(mes.total).toFixed(0) }}</span>
                </div>
              </div>
              <span class="chart-cantidad">{{ mes.cantidad }} vtas.</span>
            </div>
          </div>
          <div v-else class="empty-state">
            <i class="fa-solid fa-chart-area empty-icon"></i>
            <p>Sin ventas registradas para este periodo.</p>
          </div>
        </div>

        <!-- Ventas por categoría -->
        <div v-if="ventasPorCategoria.length" class="card section-card">
          <h2 class="section-title">
            <i class="fa-solid fa-layer-group"></i> Por Categoría
          </h2>
          <div class="categoria-table">
            <div class="cat-row cat-header">
              <span>Categoría</span>
              <span>Unidades</span>
              <span>Total Bs.</span>
              <span>% del total</span>
            </div>
            <div v-for="c in ventasPorCategoria" :key="c.categoria" class="cat-row">
              <span class="cat-nombre">{{ c.categoria }}</span>
              <span>{{ c.total_uds }}</span>
              <span class="cat-monto">Bs. {{ Number(c.total_bs).toFixed(2) }}</span>
              <div class="cat-bar-wrap">
                <div class="cat-bar" :style="{ width: catBarWidth(c.total_bs) }"></div>
                <span class="cat-pct">{{ catPct(c.total_bs) }}%</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Top productos -->
        <div v-if="topProductos.length" class="card section-card">
          <h2 class="section-title">
            <i class="fa-solid fa-trophy"></i> Top 10 Productos
          </h2>
          <div class="top-table">
            <div class="top-row top-header">
              <span>#</span>
              <span>Producto</span>
              <span>Uds. vendidas</span>
              <span>Ingresos Bs.</span>
            </div>
            <div v-for="(p, i) in topProductos" :key="p.id" class="top-row">
              <span class="top-rank" :class="i < 3 ? `rank-${i+1}` : ''">{{ i+1 }}</span>
              <span class="top-nombre">{{ p.nombre }}</span>
              <span class="top-uds">{{ p.total_uds }}</span>
              <span class="top-monto">Bs. {{ Number(p.total_bs || 0).toFixed(2) }}</span>
            </div>
          </div>
        </div>

      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import { router } from '@inertiajs/vue3'

const props = defineProps({
  ventasPorMes:       { type: Array,  default: () => [] },
  topProductos:       { type: Array,  default: () => [] },
  ventasPorCategoria: { type: Array,  default: () => [] },
  anio:               { type: Number, default: 2026 },
  mes:                { type: Number, default: null },
  categoriaFiltro:    { type: Number, default: null },
  aniosDisponibles:   { type: Array,  default: () => [2026] },
  categorias:         { type: Array,  default: () => [] },
  totalAnual:         { type: Number, default: 0 },
  totalVentas:        { type: Number, default: 0 },
})

const filtros = ref({
  anio:      props.anio,
  mes:       props.mes ?? '',
  categoria: props.categoriaFiltro ?? '',
})

const nombresMeses = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre']
const mesesCortos  = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic']

const periodoLabel = computed(() => {
  if (filtros.value.mes) return `${nombresMeses[filtros.value.mes - 1]} ${filtros.value.anio}`
  return String(filtros.value.anio)
})

const mesesCompletos = computed(() =>
  nombresMeses.map((nombre, i) => {
    const data = props.ventasPorMes.find(v => v.mes === i + 1)
    return { mes: i + 1, nombre, nombreCorto: mesesCortos[i], cantidad: data?.cantidad || 0, total: data?.total || 0 }
  })
)

const maxTotal   = computed(() => Math.max(...mesesCompletos.value.map(m => m.total), 1))
const maxCatBs   = computed(() => Math.max(...props.ventasPorCategoria.map(c => c.total_bs), 1))
const totalCatBs = computed(() => props.ventasPorCategoria.reduce((s, c) => s + c.total_bs, 0))

function barWidth(val)    { return Math.round((val / maxTotal.value) * 100) + '%' }
function catBarWidth(val) { return Math.round((val / maxCatBs.value) * 100) + '%' }
function catPct(val)      { return totalCatBs.value > 0 ? (val / totalCatBs.value * 100).toFixed(1) : '0.0' }

function aplicar() {
  const params = { anio: filtros.value.anio }
  if (filtros.value.mes) params.mes = filtros.value.mes
  if (filtros.value.categoria) params.categoria = filtros.value.categoria
  router.get(route('admin.reportes'), params, { preserveState: true })
}

function limpiar() {
  filtros.value = { anio: new Date().getFullYear(), mes: '', categoria: '' }
  router.get(route('admin.reportes'), { anio: filtros.value.anio }, { preserveState: true })
}

function imprimirPDF() {
  window.print()
}
</script>

<style scoped>
.admin-page { padding: 1.5rem 0; }
.page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; }
.page-title { font-size: 1.5rem; display: flex; align-items: center; gap: 0.625rem; }

/* Filtros */
.filtros-card { margin-bottom: 1.5rem; padding: 1.25rem; }
.filtros-row { display: flex; gap: 1rem; align-items: flex-end; flex-wrap: wrap; }
.filtro-item { display: flex; flex-direction: column; min-width: 140px; }

/* KPIs */
.kpi-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1.5rem; }
.kpi-card { display: flex; align-items: center; gap: 1rem; padding: 1.25rem; }
.kpi-icon { width: 48px; height: 48px; border-radius: var(--radius); display: flex; align-items: center; justify-content: center; font-size: 1.2rem; flex-shrink: 0; }
.kpi-valor { font-size: 1.4rem; font-weight: 700; color: var(--text-primary); }
.kpi-label { font-size: 0.75rem; color: var(--text-secondary); margin-top: 0.1rem; }

/* Secciones */
.section-card { margin-bottom: 1.5rem; }
.section-title { font-size: 1.05rem; margin-bottom: 1.25rem; display: flex; align-items: center; gap: 0.5rem; color: var(--text-primary); }

/* Gráfico barras */
.chart-bars { display: flex; flex-direction: column; gap: 0.5rem; }
.chart-bar-row { display: flex; align-items: center; gap: 0.75rem; }
.chart-mes { width: 34px; font-size: 0.78rem; font-weight: 600; color: var(--text-secondary); text-align: right; }
.chart-bar-wrap { flex: 1; height: 26px; background: var(--bg-secondary); border-radius: 4px; overflow: hidden; }
.chart-bar { height: 100%; background: linear-gradient(90deg, var(--color-primary), var(--color-accent)); border-radius: 4px; display: flex; align-items: center; justify-content: flex-end; padding-right: 0.5rem; transition: width 0.5s ease; min-width: 4px; }
.chart-bar-label { font-size: 0.7rem; color: white; font-weight: 600; white-space: nowrap; }
.chart-cantidad { font-size: 0.75rem; color: var(--text-secondary); min-width: 65px; }

/* Categorías */
.categoria-table { display: flex; flex-direction: column; gap: 0.5rem; }
.cat-row { display: grid; grid-template-columns: 2fr 1fr 1.5fr 2fr; align-items: center; gap: 0.75rem; padding: 0.5rem 0; border-bottom: 1px solid var(--border-color); font-size: 0.875rem; }
.cat-header { font-size: 0.72rem; font-weight: 700; text-transform: uppercase; color: var(--text-secondary); border-bottom: 2px solid var(--border-color); }
.cat-nombre { font-weight: 500; }
.cat-monto { font-weight: 600; color: var(--color-accent); }
.cat-bar-wrap { display: flex; align-items: center; gap: 0.5rem; }
.cat-bar { height: 8px; background: linear-gradient(90deg, var(--color-primary), var(--color-accent)); border-radius: 4px; min-width: 4px; max-width: 120px; transition: width 0.4s; }
.cat-pct { font-size: 0.75rem; color: var(--text-secondary); white-space: nowrap; }

/* Top productos */
.top-table { display: flex; flex-direction: column; gap: 0; }
.top-row { display: grid; grid-template-columns: 32px 2fr 1fr 1.5fr; align-items: center; gap: 0.75rem; padding: 0.6rem 0; border-bottom: 1px solid var(--border-color); font-size: 0.875rem; }
.top-header { font-size: 0.72rem; font-weight: 700; text-transform: uppercase; color: var(--text-secondary); border-bottom: 2px solid var(--border-color); }
.top-rank { width: 28px; height: 28px; border-radius: 50%; background: var(--bg-secondary); display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.78rem; }
.rank-1 { background: #FFD700; color: #333; }
.rank-2 { background: #C0C0C0; color: #333; }
.rank-3 { background: #CD7F32; color: white; }
.top-nombre { font-weight: 500; }
.top-uds { font-weight: 600; }
.top-monto { font-weight: 600; color: var(--color-accent); }

.empty-state { text-align: center; padding: 2.5rem; color: var(--text-secondary); }
.empty-icon { font-size: 2.5rem; margin-bottom: 0.75rem; opacity: 0.4; display: block; }

/* Print */
@media print {
  #no-print { display: none !important; }
  .page-header button { display: none; }
  .card { box-shadow: none; border: 1px solid #ccc; }
  .app-sidebar, .topbar { display: none !important; }
  .app-main { padding: 0; }
  .chart-bar { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
}
</style>
