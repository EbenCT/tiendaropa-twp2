<template>
  <AppLayout>
    <div class="container">
      <div class="pedido-show fade-in">
        <Link :href="route('pedidos.historial')" class="back-link">← Volver a mis pedidos</Link>
        <h1 class="page-title">Pedido #{{ pedido.id }}</h1>

        <div class="show-layout">
          <div class="show-main">
            <div class="card">
              <div class="show-header">
                <span :class="['estado-badge', `estado-${pedido.estado?.toLowerCase()}`]">{{ pedido.estado }}</span>
                <span class="show-fecha">{{ formatFecha(pedido.fecha) }}</span>
              </div>

              <h3 class="section-subtitle">Productos</h3>
              <div class="show-items">
                <div v-for="det in pedido.detalles" :key="det.id" class="show-item">
                  <img :src="det.producto?.imagen_principal?.url || det.producto?.imagen_url || '/img/placeholder.jpg'" class="show-item-img" />
                  <div class="show-item-info">
                    <p class="show-item-nombre">{{ det.producto?.nombre }}</p>
                    <p class="show-item-meta">Cantidad: {{ det.cantidad }} · Precio: Bs. {{ Number(det.precio_unitario).toFixed(2) }}</p>
                  </div>
                  <span class="show-item-subtotal">Bs. {{ Number(det.subtotal || det.cantidad * det.precio_unitario).toFixed(2) }}</span>
                </div>
              </div>
            </div>
          </div>

          <div class="show-side">
            <div class="card">
              <h3>Datos de Entrega</h3>
              <p><strong>Dirección:</strong> {{ pedido.direccion }}</p>
              <p><strong>Teléfono:</strong> {{ pedido.telefono }}</p>
              <p v-if="pedido.referencia"><strong>Referencia:</strong> {{ pedido.referencia }}</p>
            </div>
            <div class="card" style="margin-top:1rem">
              <h3>Total del Pedido</h3>
              <p class="show-total">Bs. {{ Number(pedido.venta?.total || calcTotal()).toFixed(2) }}</p>
            </div>

            <div class="card" style="margin-top:1rem">
              <h3>Pago</h3>
              <template v-if="pago">
                <p class="hint" style="margin-bottom:0.5rem">Pasarela: {{ pago.gateway === 'pagofacil' ? 'QR PagoFácil' : 'Stripe' }}</p>
                <span :class="['estado-badge', estaPagado ? 'estado-entregado' : 'estado-pendiente']">
                  {{ estaPagado ? 'Pagado' : (pago.stripe_status === 'failed' ? 'Fallido' : 'Pendiente') }}
                </span>

                <table v-if="pago.modalidad === 'CREDITO' && pago.cuotas?.length" class="cuotas-table">
                  <thead>
                    <tr><th>Cuota</th><th>Monto</th><th>Vence</th><th>Estado</th><th v-if="pago.gateway === 'pagofacil'"></th></tr>
                  </thead>
                  <tbody>
                    <tr v-for="c in pago.cuotas" :key="c.id">
                      <td>{{ c.num_cuota }}</td>
                      <td>Bs. {{ Number(c.monto).toFixed(2) }}</td>
                      <td>{{ formatFecha(c.fecha_vencimiento) }}</td>
                      <td>{{ c.estado }}</td>
                      <td v-if="pago.gateway === 'pagofacil'">
                        <button v-if="puedePagarCuota(c)" class="btn btn-secondary btn-sm" @click="pagarCuota(c)">Pagar</button>
                      </td>
                    </tr>
                  </tbody>
                </table>

                <div v-if="cuotaQr" style="margin-top:1rem">
                  <QrPagoFacil
                    :qr="cuotaQr"
                    :verificando="verificandoCuota"
                    :mensaje-estado="mensajeCuotaEstado"
                    @verificar="verificarCuota"
                  />
                </div>
              </template>
              <p v-else class="hint">Aún no se inició un pago para este pedido.</p>

              <Link v-if="!estaPagado" :href="route('pedidos.pagar', pedido.id)" class="btn btn-primary btn-full" style="margin-top:1rem">
                {{ pago ? 'Reintentar pago' : 'Pagar ahora' }}
              </Link>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import QrPagoFacil from '@/Components/QrPagoFacil.vue'
import { Link } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import axios from 'axios'

const props = defineProps({
  pedido: { type: Object, required: true },
})

const pago = computed(() => (props.pedido.venta?.pagos || [])[0] || null)
const estaPagado = computed(() => pago.value && (pago.value.stripe_status === 'succeeded' || pago.value.pagofacil_status === '2'))

const cuotaQr = ref(null)
const verificandoCuota = ref(false)
const mensajeCuotaEstado = ref('')

function puedePagarCuota(c) {
  return c.estado === 'PENDIENTE' && new Date(c.fecha_vencimiento) <= new Date()
}

async function pagarCuota(c) {
  mensajeCuotaEstado.value = ''
  const { data } = await axios.post(route('pedidos.pagar.pagofacil.cuota-qr', [props.pedido.id, c.id]))
  cuotaQr.value = data
}

async function verificarCuota() {
  verificandoCuota.value = true
  try {
    const { data } = await axios.get(route('pedidos.pagar.pagofacil.estado', props.pedido.id), {
      params: { payment_number: cuotaQr.value.paymentNumber },
    })
    mensajeCuotaEstado.value = data.paymentStatusDescription || 'Estado desconocido'
    if (Number(data.paymentStatus) === 2) {
      window.location.reload()
    }
  } finally {
    verificandoCuota.value = false
  }
}

function formatFecha(f) { return new Date(f).toLocaleDateString('es-BO', { year:'numeric', month:'long', day:'numeric' }) }
function calcTotal() { return (props.pedido.detalles || []).reduce((s, d) => s + (d.subtotal || d.cantidad * d.precio_unitario), 0) }
</script>

<style scoped>
.pedido-show { padding:2rem 0; }
.back-link { display:inline-block; margin-bottom:1rem; font-size:0.875rem; color:var(--color-accent); }
.page-title { font-size:2rem; margin-bottom:2rem; }
.show-layout { display:grid; grid-template-columns:1fr 340px; gap:2rem; align-items:start; }
.show-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem; }
.show-fecha { font-size:0.85rem; color:var(--text-secondary); }
.section-subtitle { font-size:1rem; font-weight:600; margin-bottom:1rem; }
.show-items { display:flex; flex-direction:column; gap:0.75rem; }
.show-item { display:flex; align-items:center; gap:1rem; padding:0.75rem; border:1px solid var(--border-color); border-radius:var(--radius-sm); }
.show-item-img { width:60px; height:75px; object-fit:cover; border-radius:var(--radius-sm); }
.show-item-info { flex:1; }
.show-item-nombre { font-weight:600; font-size:0.9rem; }
.show-item-meta { font-size:0.8rem; color:var(--text-secondary); }
.show-item-subtotal { font-weight:700; color:var(--color-accent); }
.show-side p { font-size:0.9rem; margin-bottom:0.5rem; }
.show-total { font-size:1.75rem; font-weight:700; color:var(--color-accent); margin-top:0.5rem; }
.estado-badge { padding:0.2rem 0.75rem; border-radius:var(--radius-pill); font-size:0.75rem; font-weight:700; text-transform:uppercase; }
.estado-pendiente { background:color-mix(in srgb, var(--color-warning) 20%, var(--bg-card)); color:var(--color-warning); }
.estado-confirmado { background:color-mix(in srgb, var(--color-info) 20%, var(--bg-card)); color:var(--color-info); }
.estado-enviado { background:color-mix(in srgb, var(--color-primary) 20%, var(--bg-card)); color:var(--color-primary); }
.estado-entregado { background:color-mix(in srgb, var(--color-success) 20%, var(--bg-card)); color:var(--color-success); }
.hint { color:var(--text-secondary); font-size:0.875rem; }
.btn-full { width:100%; justify-content:center; padding:0.65rem; display:flex; }
.cuotas-table { width:100%; margin-top:1rem; font-size:0.85rem; border-collapse:collapse; }
.cuotas-table th, .cuotas-table td { text-align:left; padding:0.4rem 0.3rem; border-bottom:1px solid var(--border-color); }
.btn-sm { padding:0.3rem 0.6rem; font-size:0.75rem; }
@media (max-width:768px) { .show-layout { grid-template-columns:1fr; } }
</style>
