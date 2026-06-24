<template>
  <AppLayout>
    <div class="container">
      <div class="historial-page fade-in">
        <h1 class="page-title">📋 Mis Pedidos</h1>

        <div v-if="pedidos.data.length" class="pedidos-list">
          <div v-for="pedido in pedidos.data" :key="pedido.id" class="pedido-card card">
            <div class="pedido-header">
              <div>
                <span class="pedido-id">Pedido #{{ pedido.id }}</span>
                <span class="pedido-fecha">{{ formatFecha(pedido.created_at) }}</span>
              </div>
              <span :class="['estado-badge', `estado-${pedido.estado?.toLowerCase()}`]">{{ pedido.estado }}</span>
            </div>
            <div class="pedido-detalles">
              <div v-for="det in pedido.detalles" :key="det.id" class="pedido-detalle">
                <span>{{ det.producto?.nombre }}</span>
                <span>× {{ det.cantidad }}</span>
                <span>Bs. {{ Number(det.subtotal || det.cantidad * det.precio_unitario).toFixed(2) }}</span>
              </div>
            </div>
            <div class="pedido-footer">
              <span class="pedido-total">Total: <strong>Bs. {{ Number(pedido.total || calcularTotal(pedido)).toFixed(2) }}</strong></span>
              <Link :href="route('pedidos.show', pedido.id)" class="btn btn-outline btn-sm">Ver Detalle</Link>
            </div>
          </div>
        </div>

        <div v-else class="empty-state">
          <p>📦 No tienes pedidos aún</p>
          <Link :href="route('catalogo')" class="btn btn-primary" style="margin-top:1rem">Ir al Catálogo</Link>
        </div>

        <!-- Paginación -->
        <div v-if="pedidos.last_page > 1" class="paginacion">
          <template v-for="link in pedidos.links" :key="link.label">
            <Link v-if="link.url" :href="link.url" :class="['pag-link', { active: link.active }]" v-html="link.label" />
            <span v-else class="pag-link disabled" v-html="link.label" />
          </template>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { Link } from '@inertiajs/vue3'

defineProps({
  pedidos: { type: Object, required: true },
})

function formatFecha(fecha) {
  return new Date(fecha).toLocaleDateString('es-BO', { year: 'numeric', month: 'short', day: 'numeric' })
}

function calcularTotal(pedido) {
  return (pedido.detalles || []).reduce((sum, d) => sum + (d.subtotal || d.cantidad * d.precio_unitario), 0)
}
</script>

<style scoped>
.historial-page { padding:2rem 0; }
.page-title { font-size:2rem; margin-bottom:2rem; }
.pedidos-list { display:flex; flex-direction:column; gap:1rem; }
.pedido-card { }
.pedido-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem; }
.pedido-id { font-weight:700; font-size:1rem; margin-right:0.75rem; }
.pedido-fecha { font-size:0.8rem; color:var(--text-secondary); }
.estado-badge { padding:0.2rem 0.75rem; border-radius:var(--radius-pill); font-size:0.75rem; font-weight:700; text-transform:uppercase; }
.estado-pendiente { background:color-mix(in srgb, var(--color-warning) 20%, var(--bg-card)); color:var(--color-warning); }
.estado-confirmado { background:color-mix(in srgb, var(--color-info) 20%, var(--bg-card)); color:var(--color-info); }
.estado-enviado { background:color-mix(in srgb, var(--color-primary) 20%, var(--bg-card)); color:var(--color-primary); }
.estado-entregado { background:color-mix(in srgb, var(--color-success) 20%, var(--bg-card)); color:var(--color-success); }
.pedido-detalles { border-top:1px solid var(--border-color); border-bottom:1px solid var(--border-color); padding:0.75rem 0; margin-bottom:0.75rem; }
.pedido-detalle { display:flex; justify-content:space-between; gap:1rem; font-size:0.875rem; padding:0.2rem 0; }
.pedido-footer { display:flex; justify-content:space-between; align-items:center; }
.pedido-total { font-size:1rem; }
.btn-sm { padding:0.35rem 0.75rem; font-size:0.8rem; }
.paginacion { display:flex; justify-content:center; gap:0.25rem; margin-top:2rem; flex-wrap:wrap; }
.pag-link { padding:0.4rem 0.75rem; border-radius:var(--radius-sm); border:1px solid var(--border-color); background:var(--bg-card); color:var(--text-primary); font-size:0.85rem; text-decoration:none; }
.pag-link.active { background:var(--color-primary); color:var(--text-on-primary); }
.pag-link.disabled { opacity:0.4; pointer-events:none; }
.empty-state { text-align:center; padding:4rem 0; color:var(--text-secondary); }
</style>
