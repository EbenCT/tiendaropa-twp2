<template>
  <AppLayout>
    <div class="container">
      <div class="admin-page fade-in">

        <h1 class="page-title">
          <i class="fa-solid fa-clipboard-list"></i> Bitácora del Sistema
        </h1>

        <!-- Filtros -->
        <div class="card filtros-card">
          <div class="filtros-row">
            <div class="filtro-item">
              <label class="form-label">Módulo</label>
              <select v-model="filtros.modulo" @change="aplicar" class="input">
                <option value="">Todos</option>
                <option v-for="m in modulos" :key="m" :value="m">{{ m }}</option>
              </select>
            </div>
            <div class="filtro-item">
              <label class="form-label">Acción</label>
              <select v-model="filtros.accion" @change="aplicar" class="input">
                <option value="">Todas</option>
                <option v-for="a in acciones" :key="a" :value="a">{{ a }}</option>
              </select>
            </div>
            <button @click="limpiar" class="btn btn-outline" style="align-self:flex-end">
              <i class="fa-solid fa-rotate-left"></i> Limpiar
            </button>
          </div>
        </div>

        <!-- Tabla -->
        <div class="card">
          <div class="table-wrap">
            <table class="tabla">
              <thead>
                <tr>
                  <th>Fecha/Hora</th>
                  <th>Usuario</th>
                  <th>Acción</th>
                  <th>Módulo</th>
                  <th>Descripción</th>
                  <th>IP</th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="!registros.data.length">
                  <td colspan="6" class="empty-cell">Sin registros en la bitácora.</td>
                </tr>
                <tr v-for="r in registros.data" :key="r.id" :class="rowClass(r.accion)">
                  <td class="td-fecha">{{ formatFecha(r.created_at) }}</td>
                  <td class="td-usuario">
                    <span v-if="r.usuario">{{ r.usuario.nombre }} {{ r.usuario.apellido }}</span>
                    <span v-else class="text-muted">—</span>
                  </td>
                  <td>
                    <span class="badge" :class="badgeClass(r.accion)">{{ r.accion }}</span>
                  </td>
                  <td><span class="modulo-tag">{{ r.modulo || '—' }}</span></td>
                  <td class="td-desc">{{ r.descripcion }}</td>
                  <td class="td-ip">{{ r.ip }}</td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Paginación -->
          <div v-if="registros.last_page > 1" class="paginacion">
            <button
              v-for="link in registros.links"
              :key="link.label"
              @click="link.url && irA(link.url)"
              class="btn-pag"
              :class="{ active: link.active, disabled: !link.url }"
              v-html="link.label"
            />
          </div>
        </div>

      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import { router } from '@inertiajs/vue3'

const props = defineProps({
  registros: { type: Object, default: () => ({ data: [], links: [], last_page: 1 }) },
  modulos:   { type: Array, default: () => [] },
  acciones:  { type: Array, default: () => [] },
  filtros:   { type: Object, default: () => ({}) },
})

const filtros = ref({ modulo: props.filtros.modulo || '', accion: props.filtros.accion || '' })

function aplicar() {
  const params = {}
  if (filtros.value.modulo) params.modulo = filtros.value.modulo
  if (filtros.value.accion) params.accion = filtros.value.accion
  router.get(route('admin.bitacora'), params, { preserveState: true })
}

function limpiar() {
  filtros.value = { modulo: '', accion: '' }
  router.get(route('admin.bitacora'), {}, { preserveState: true })
}

function irA(url) {
  router.visit(url, { preserveState: true })
}

function formatFecha(iso) {
  if (!iso) return '—'
  const d = new Date(iso)
  return d.toLocaleString('es-BO', { dateStyle: 'short', timeStyle: 'medium' })
}

function rowClass(accion) {
  if (accion === 'LOGIN_FALLIDO') return 'row-danger'
  if (accion === 'PAGO') return 'row-success'
  return ''
}

function badgeClass(accion) {
  const mapa = {
    LOGIN: 'badge-info',
    LOGIN_FALLIDO: 'badge-danger',
    LOGOUT: 'badge-secondary',
    PAGO: 'badge-success',
    INVENTARIO: 'badge-warning',
    REGISTRO: 'badge-primary',
  }
  return mapa[accion] || 'badge-secondary'
}
</script>

<style scoped>
.admin-page { padding: 1.5rem 0; }
.page-title { font-size: 1.5rem; display: flex; align-items: center; gap: 0.625rem; margin-bottom: 1.5rem; }

.filtros-card { margin-bottom: 1.5rem; padding: 1.25rem; }
.filtros-row { display: flex; gap: 1rem; align-items: flex-end; flex-wrap: wrap; }
.filtro-item { display: flex; flex-direction: column; min-width: 160px; }

.table-wrap { overflow-x: auto; }
.tabla { width: 100%; border-collapse: collapse; font-size: 0.83rem; }
.tabla th { padding: 0.6rem 0.75rem; text-align: left; font-size: 0.72rem; font-weight: 700;
  text-transform: uppercase; color: var(--text-secondary); border-bottom: 2px solid var(--border-color); }
.tabla td { padding: 0.55rem 0.75rem; border-bottom: 1px solid var(--border-color); vertical-align: top; }

.row-danger td { background: color-mix(in srgb, var(--color-danger, #e53e3e) 8%, transparent); }
.row-success td { background: color-mix(in srgb, var(--color-success) 6%, transparent); }

.td-fecha { white-space: nowrap; color: var(--text-secondary); font-size: 0.78rem; }
.td-ip    { font-size: 0.75rem; color: var(--text-secondary); white-space: nowrap; }
.td-desc  { max-width: 320px; }
.td-usuario { white-space: nowrap; }
.text-muted { color: var(--text-secondary); }
.empty-cell { text-align: center; padding: 2rem; color: var(--text-secondary); }

.badge { display: inline-block; padding: 0.2rem 0.5rem; border-radius: 4px;
  font-size: 0.7rem; font-weight: 700; letter-spacing: 0.03em; }
.badge-info      { background: color-mix(in srgb, var(--color-info) 20%, transparent); color: var(--color-info); }
.badge-danger    { background: color-mix(in srgb, #e53e3e 20%, transparent); color: #e53e3e; }
.badge-secondary { background: var(--bg-secondary); color: var(--text-secondary); }
.badge-success   { background: color-mix(in srgb, var(--color-success) 20%, transparent); color: var(--color-success); }
.badge-warning   { background: color-mix(in srgb, var(--color-warning) 20%, transparent); color: var(--color-warning); }
.badge-primary   { background: color-mix(in srgb, var(--color-primary) 20%, transparent); color: var(--color-primary); }

.modulo-tag { font-size: 0.72rem; font-weight: 600; color: var(--text-secondary); text-transform: uppercase; }

.paginacion { display: flex; gap: 0.25rem; padding: 1rem; flex-wrap: wrap; }
.btn-pag { padding: 0.3rem 0.6rem; border: 1px solid var(--border-color); border-radius: 4px;
  background: var(--bg-card); cursor: pointer; font-size: 0.8rem; color: var(--text-primary); }
.btn-pag.active { background: var(--color-primary); color: white; border-color: var(--color-primary); }
.btn-pag.disabled { opacity: 0.4; cursor: default; }
</style>
