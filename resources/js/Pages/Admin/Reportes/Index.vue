<template>
  <AppLayout>
    <div class="container">
      <div class="admin-page fade-in">
        <h1 class="page-title">📊 Reportes de Ventas</h1>

        <!-- Selector de año -->
        <div class="filtros-inline card" style="margin-bottom:2rem">
          <label class="form-label" style="margin:0">Año:</label>
          <select v-model="anioSeleccionado" @change="cambiarAnio" class="input" style="max-width:120px">
            <option v-for="a in aniosDisponibles" :key="a" :value="a">{{ a }}</option>
          </select>
        </div>

        <!-- Resumen anual -->
        <div class="resumen-anual">
          <div class="stat-card card">
            <span class="stat-icon">🧾</span>
            <span class="stat-valor">{{ totalVentas }}</span>
            <span class="stat-label">Ventas en {{ anio }}</span>
          </div>
          <div class="stat-card card">
            <span class="stat-icon">💰</span>
            <span class="stat-valor">Bs. {{ Number(totalAnual).toFixed(2) }}</span>
            <span class="stat-label">Ganancias {{ anio }}</span>
          </div>
        </div>

        <!-- Tabla de ventas por mes -->
        <div class="card">
          <h2 style="margin-bottom:1.25rem">Ventas Mensuales — {{ anio }}</h2>
          <div class="chart-bars">
            <div v-for="mes in mesesCompletos" :key="mes.mes" class="chart-bar-row">
              <span class="chart-mes">{{ mes.nombre }}</span>
              <div class="chart-bar-wrap">
                <div class="chart-bar" :style="{ width: barWidth(mes.total) }">
                  <span v-if="mes.total > 0" class="chart-bar-label">Bs. {{ Number(mes.total).toFixed(0) }}</span>
                </div>
              </div>
              <span class="chart-cantidad">{{ mes.cantidad }} ventas</span>
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
  ventasPorMes:     { type: Array, default: () => [] },
  anio:             { type: Number, default: 2026 },
  aniosDisponibles: { type: Array, default: () => [2026] },
  totalAnual:       { type: Number, default: 0 },
  totalVentas:      { type: Number, default: 0 },
})

const anioSeleccionado = ref(props.anio)

const nombresMeses = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre']

const mesesCompletos = computed(() => {
  return nombresMeses.map((nombre, i) => {
    const data = props.ventasPorMes.find(v => v.mes === i + 1)
    return { mes: i + 1, nombre, cantidad: data?.cantidad || 0, total: data?.total || 0 }
  })
})

const maxTotal = computed(() => Math.max(...mesesCompletos.value.map(m => m.total), 1))
function barWidth(val) { return Math.round((val / maxTotal.value) * 100) + '%' }

function cambiarAnio() {
  router.get(route('admin.reportes'), { anio: anioSeleccionado.value }, { preserveState: true })
}
</script>

<style scoped>
.admin-page { padding:2rem 0; }
.page-title { font-size:1.75rem; margin-bottom:1.5rem; }
.filtros-inline { display:flex; gap:0.75rem; align-items:center; }
.resumen-anual { display:grid; grid-template-columns:1fr 1fr; gap:1.25rem; margin-bottom:2rem; }
.stat-card { display:flex; flex-direction:column; align-items:center; gap:0.5rem; padding:1.5rem; }
.stat-icon { font-size:2rem; }
.stat-valor { font-size:1.75rem; font-weight:700; color:var(--color-accent); }
.stat-label { font-size:0.8rem; color:var(--text-secondary); text-transform:uppercase; }
.chart-bars { display:flex; flex-direction:column; gap:0.5rem; }
.chart-bar-row { display:flex; align-items:center; gap:0.75rem; }
.chart-mes { width:90px; font-size:0.85rem; font-weight:500; text-align:right; }
.chart-bar-wrap { flex:1; height:28px; background:var(--bg-secondary); border-radius:4px; overflow:hidden; }
.chart-bar { height:100%; background:linear-gradient(90deg, var(--color-primary), var(--color-accent)); border-radius:4px; display:flex; align-items:center; justify-content:flex-end; padding-right:0.5rem; transition:width 0.5s ease; min-width:0; }
.chart-bar-label { font-size:0.7rem; color:white; font-weight:600; white-space:nowrap; }
.chart-cantidad { font-size:0.8rem; color:var(--text-secondary); min-width:80px; }
</style>
