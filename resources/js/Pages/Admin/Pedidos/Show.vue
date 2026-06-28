<template>
  <AppLayout>
    <div class="container">
      <div class="admin-page fade-in">
        <Link :href="route('admin.pedidos.index')" class="back-link">← Volver a pedidos</Link>
        <h1 class="page-title">Pedido #{{ pedido.id }}</h1>
        <div class="show-layout">
          <div class="card">
            <h3>Cliente</h3>
            <p>{{ pedido.usuario?.nombre }} {{ pedido.usuario?.apellido }}</p>
            <p>{{ pedido.usuario?.email }}</p>
            <h3 style="margin-top:1.5rem">Entrega</h3>
            <p><strong>Dirección:</strong> {{ pedido.direccion }}</p>
            <p><strong>Teléfono:</strong> {{ pedido.telefono }}</p>
            <p v-if="pedido.referencia"><strong>Ref:</strong> {{ pedido.referencia }}</p>
            <h3 style="margin-top:1.5rem">Productos</h3>
            <div v-for="d in pedido.detalles" :key="d.id" class="det-item">
              <img :src="d.producto?.imagen_principal?.url || d.producto?.imagen_url || '/img/placeholder.jpg'" class="det-img" />
              <div><strong>{{ d.producto?.nombre }}</strong><br/>Cant: {{ d.cantidad }} · Bs. {{ Number(d.precio_unitario).toFixed(2) }}</div>
              <span>Bs. {{ Number(d.subtotal || d.cantidad * d.precio_unitario).toFixed(2) }}</span>
            </div>
            <p class="total-line">Total: <strong>Bs. {{ Number(pedido.venta?.total || 0).toFixed(2) }}</strong></p>
          </div>
          <div class="card">
            <h3>Cambiar Estado</h3>
            <select :value="pedido.estado" @change="cambiarEstado($event.target.value)" class="input">
              <option value="PENDIENTE">Pendiente</option>
              <option value="CONFIRMADO">Confirmado</option>
              <option value="ENVIADO">Enviado</option>
              <option value="ENTREGADO">Entregado</option>
            </select>

            <h3 style="margin-top:1.5rem">Pago (solo lectura)</h3>
            <template v-if="pago">
              <span :class="['pago-badge', pago.stripe_status === 'succeeded' ? 'pago-ok' : 'pago-pend']">
                {{ pago.stripe_status === 'succeeded' ? 'Pagado' : (pago.stripe_status === 'failed' ? 'Fallido' : 'Pendiente') }}
              </span>
              <table v-if="pago.modalidad === 'CREDITO' && pago.cuotas?.length" class="cuotas-table">
                <thead><tr><th>Cuota</th><th>Monto</th><th>Vence</th><th>Estado</th></tr></thead>
                <tbody>
                  <tr v-for="c in pago.cuotas" :key="c.id">
                    <td>{{ c.num_cuota }}</td>
                    <td>Bs. {{ Number(c.monto).toFixed(2) }}</td>
                    <td>{{ c.fecha_vencimiento }}</td>
                    <td>{{ c.estado }}</td>
                  </tr>
                </tbody>
              </table>
            </template>
            <p v-else class="hint">El cliente aún no inició un pago.</p>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { Link, router } from '@inertiajs/vue3'
import { computed } from 'vue'
const props = defineProps({ pedido: Object })
const pago = computed(() => (props.pedido.venta?.pagos || [])[0] || null)
function cambiarEstado(estado) { router.patch(route('admin.pedidos.estado', props.pedido.id), { estado }, { preserveScroll: true }) }
</script>

<style scoped>
.admin-page { padding:2rem 0; }
.back-link { display:inline-block; margin-bottom:1rem; font-size:0.875rem; color:var(--color-accent); }
.page-title { font-size:1.75rem; margin-bottom:1.5rem; }
.show-layout { display:grid; grid-template-columns:1fr 300px; gap:2rem; align-items:start; }
.det-item { display:flex; align-items:center; gap:1rem; padding:0.5rem 0; border-bottom:1px solid var(--border-color); }
.det-img { width:48px; height:60px; object-fit:cover; border-radius:var(--radius-sm); }
.total-line { font-size:1.25rem; margin-top:1rem; text-align:right; }
.hint { color:var(--text-secondary); font-size:0.875rem; }
.pago-badge { padding:0.2rem 0.75rem; border-radius:var(--radius-pill); font-size:0.75rem; font-weight:700; text-transform:uppercase; }
.pago-ok { background:color-mix(in srgb, var(--color-success) 20%, var(--bg-card)); color:var(--color-success); }
.pago-pend { background:color-mix(in srgb, var(--color-warning) 20%, var(--bg-card)); color:var(--color-warning); }
.cuotas-table { width:100%; margin-top:1rem; font-size:0.8rem; border-collapse:collapse; }
.cuotas-table th, .cuotas-table td { text-align:left; padding:0.3rem; border-bottom:1px solid var(--border-color); }
@media (max-width:768px) { .show-layout { grid-template-columns:1fr; } }
</style>
