<template>
  <AppLayout>
    <div class="container">
      <div class="pagar-page fade-in">
        <Link :href="route('pedidos.show', pedido.id)" class="back-link">← Volver al pedido</Link>
        <h1 class="page-title">💳 Pagar Pedido #{{ pedido.id }}</h1>

        <div class="pagar-layout">
          <div class="pagar-main card">
            <div class="gateway-tabs">
              <button :class="['gateway-btn', { active: gateway === 'pagofacil' }]" @click="gateway = 'pagofacil'">
                🔲 QR PagoFácil
              </button>
              <button :class="['gateway-btn', { active: gateway === 'stripe' }]" @click="gateway = 'stripe'">
                💳 Tarjeta (Stripe)
              </button>
            </div>

            <!-- ══════════════ PAGOFÁCIL ══════════════ -->
            <template v-if="gateway === 'pagofacil'">
              <div class="tabs">
                <button :class="['tab-btn', { active: tabPf === 'unico' }]" @click="tabPf = 'unico'">Pago único</button>
                <button :class="['tab-btn', { active: tabPf === 'cuotas' }]" @click="tabPf = 'cuotas'">Plan de cuotas</button>
              </div>

              <!-- Pago único QR -->
              <div v-if="tabPf === 'unico'" class="tab-panel">
                <p class="hint">Genera un código QR y págalo desde tu app bancaria (QR Simple interbancario, modo de pruebas).</p>

                <button v-if="!qrUnico" class="btn btn-primary" :disabled="cargandoPf" @click="generarQrUnico">
                  {{ cargandoPf ? 'Generando QR...' : 'Generar QR para pagar' }}
                </button>

                <QrPagoFacil
                  v-else
                  :qr="qrUnico"
                  :verificando="verificandoPf"
                  :mensaje-estado="mensajeEstadoUnico"
                  @verificar="() => verificarEstadoQr(qrUnico.paymentNumber, mensajeEstadoUnico)"
                />
              </div>

              <!-- Plan de cuotas QR -->
              <div v-else class="tab-panel">
                <div v-if="!qrCuotas" class="form-group">
                  <label class="form-label">Número de cuotas</label>
                  <div class="cuotas-options">
                    <label v-for="n in opcionesCuotas" :key="n" class="cuota-option">
                      <input type="radio" v-model.number="numCuotasPf" :value="n" />
                      {{ n }} cuotas de Bs. {{ (totalBs / n).toFixed(2) }}
                    </label>
                  </div>
                  <p v-if="opcionesCuotas.length === 0" class="form-error">
                    El total del pedido no alcanza el mínimo de Bs. 50 por cuota.
                  </p>
                  <p class="hint">Cada cuota genera su propio QR cuando llega su fecha — no hay cobro automático, tú la pagas escaneando el QR.</p>
                  <button class="btn btn-primary" :disabled="cargandoPf || !numCuotasPf" @click="confirmarCuotasPf">
                    {{ cargandoPf ? 'Creando plan...' : 'Confirmar plan y generar QR de la cuota 1' }}
                  </button>
                </div>

                <template v-else>
                  <p class="hint">QR de la cuota 1 de {{ numCuotasPf }}. Las demás cuotas se pagan desde el detalle del pedido cuando vencen.</p>
                  <QrPagoFacil
                    :qr="qrCuotas"
                    :verificando="verificandoPf"
                    :mensaje-estado="mensajeEstadoCuotas"
                    @verificar="() => verificarEstadoQr(qrCuotas.paymentNumber, mensajeEstadoCuotas)"
                  />
                </template>
              </div>
            </template>

            <!-- ══════════════ STRIPE ══════════════ -->
            <template v-else>
              <div class="tabs">
                <button :class="['tab-btn', { active: tabStripe === 'unico' }]" @click="tabStripe = 'unico'">Pago único</button>
                <button
                  :class="['tab-btn', { active: tabStripe === 'cuotas' }]"
                  :disabled="metodosPago.length === 0"
                  @click="tabStripe = 'cuotas'"
                >
                  Plan de cuotas
                </button>
              </div>

              <div v-if="tabStripe === 'unico'" class="tab-panel">
                <p class="hint">Serás redirigido al checkout seguro de Stripe (modo de prueba).</p>
                <button class="btn btn-primary" :disabled="cargandoUnico" @click="pagarUnico">
                  {{ cargandoUnico ? 'Generando pago...' : 'Pagar ahora con Stripe' }}
                </button>

                <div v-if="checkoutUrl" class="qr-box">
                  <p class="hint">O escanea este código con tu celular para pagar desde otro dispositivo (usa el mismo checkout seguro de Stripe, no es un método de pago distinto):</p>
                  <canvas ref="qrCanvas"></canvas>
                </div>
              </div>

              <div v-else class="tab-panel">
                <div v-if="metodosPago.length === 0" class="hint">
                  Registra un método de pago primero para poder pagar en cuotas.
                  <Link :href="route('metodos-pago.index')" class="link">Ir a Métodos de Pago</Link>
                </div>
                <template v-else>
                  <div class="form-group">
                    <label class="form-label">Número de cuotas</label>
                    <div class="cuotas-options">
                      <label v-for="n in opcionesCuotas" :key="n" class="cuota-option">
                        <input type="radio" v-model.number="form.num_cuotas" :value="n" />
                        {{ n }} cuotas de Bs. {{ (totalBs / n).toFixed(2) }}
                      </label>
                    </div>
                    <p v-if="opcionesCuotas.length === 0" class="form-error">
                      El total del pedido no alcanza el mínimo de Bs. 50 por cuota.
                    </p>
                  </div>
                  <div class="form-group">
                    <label class="form-label">Método de pago</label>
                    <div class="cuotas-options">
                      <label v-for="m in metodosPago" :key="m.id" class="cuota-option">
                        <input type="radio" v-model.number="form.metodo_pago_usuario_id" :value="m.id" />
                        {{ m.brand }} **** {{ m.last4 }}
                      </label>
                    </div>
                  </div>
                  <button
                    class="btn btn-primary"
                    :disabled="form.processing || !form.num_cuotas || !form.metodo_pago_usuario_id"
                    @click="confirmarCuotas"
                  >
                    Confirmar plan de cuotas
                  </button>
                </template>
              </div>
            </template>
          </div>

          <div class="pagar-resumen card">
            <h3>Resumen</h3>
            <div class="resumen-items">
              <div v-for="det in pedido.detalles" :key="det.id" class="resumen-item">
                <span>{{ det.producto?.nombre }} × {{ det.cantidad }}</span>
                <span>Bs. {{ Number(det.subtotal || det.cantidad * det.precio_unitario).toFixed(2) }}</span>
              </div>
            </div>
            <hr class="resumen-divider" />
            <div class="resumen-total-line">
              <span>Total</span>
              <span>Bs. {{ totalBs.toFixed(2) }}</span>
            </div>
            <p v-if="gateway === 'stripe'" class="resumen-usd">≈ $us. {{ totalUsd.toFixed(2) }} (se cobra en USD vía Stripe)</p>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import QrPagoFacil from '@/Components/QrPagoFacil.vue'
import { Link, useForm } from '@inertiajs/vue3'
import { computed, nextTick, ref } from 'vue'
import axios from 'axios'
import QRCode from 'qrcode'

const props = defineProps({
  pedido: { type: Object, required: true },
  totalUsd: { type: Number, default: 0 },
  metodosPago: { type: Array, default: () => [] },
})

const gateway = ref('pagofacil')
const tabPf = ref('unico')
const tabStripe = ref('unico')

const totalBs = computed(() => Number(props.pedido.venta?.total || 0))
const totalUsd = computed(() => props.totalUsd)
const opcionesCuotas = computed(() => [2, 3, 6].filter(n => totalBs.value / n >= 50))

// ── PagoFácil ──────────────────────────────────────────────────────
// qrUnico y qrCuotas se mantienen separados: son dos transacciones independientes
// (paymentNumber distinto) y no deben mezclarse al cambiar de pestaña.
const cargandoPf = ref(false)
const verificandoPf = ref(false)
const qrUnico = ref(null)
const qrCuotas = ref(null)
const numCuotasPf = ref(null)
const mensajeEstadoUnico = ref('')
const mensajeEstadoCuotas = ref('')

async function generarQrUnico() {
  cargandoPf.value = true
  mensajeEstadoUnico.value = ''
  try {
    const { data } = await axios.post(route('pedidos.pagar.pagofacil.unico', props.pedido.id))
    qrUnico.value = data
  } finally {
    cargandoPf.value = false
  }
}

async function confirmarCuotasPf() {
  cargandoPf.value = true
  mensajeEstadoCuotas.value = ''
  try {
    const { data } = await axios.post(route('pedidos.pagar.pagofacil.cuotas', props.pedido.id), {
      num_cuotas: numCuotasPf.value,
    })
    qrCuotas.value = data
  } finally {
    cargandoPf.value = false
  }
}

async function verificarEstadoQr(paymentNumber, mensajeRef) {
  verificandoPf.value = true
  try {
    const { data } = await axios.get(route('pedidos.pagar.pagofacil.estado', props.pedido.id), {
      params: { payment_number: paymentNumber },
    })
    mensajeRef.value = data.paymentStatusDescription || 'Estado desconocido'
    if (Number(data.paymentStatus) === 2) {
      window.location.href = route('pedidos.show', props.pedido.id)
    }
  } finally {
    verificandoPf.value = false
  }
}

// ── Stripe ─────────────────────────────────────────────────────────
const cargandoUnico = ref(false)
const checkoutUrl = ref(null)
const qrCanvas = ref(null)

const form = useForm({
  num_cuotas: null,
  metodo_pago_usuario_id: null,
})

async function pagarUnico() {
  cargandoUnico.value = true
  try {
    const { data } = await axios.post(route('pedidos.pagar.unico', props.pedido.id))
    checkoutUrl.value = data.url
    await nextTick()
    if (qrCanvas.value) {
      QRCode.toCanvas(qrCanvas.value, data.url, { width: 200 })
    }
    window.location.href = data.url
  } finally {
    cargandoUnico.value = false
  }
}

function confirmarCuotas() {
  form.post(route('pedidos.pagar.cuotas', props.pedido.id))
}
</script>

<style scoped>
.pagar-page { padding:2rem 0; }
.back-link { display:inline-block; margin-bottom:1rem; font-size:0.875rem; color:var(--color-accent); }
.page-title { font-size:2rem; margin-bottom:2rem; }
.pagar-layout { display:grid; grid-template-columns:1fr 340px; gap:2rem; align-items:start; }
.gateway-tabs { display:flex; gap:0.5rem; margin-bottom:1rem; }
.gateway-btn { flex:1; padding:0.75rem 1rem; border:2px solid var(--border-color); border-radius:8px; background:none; cursor:pointer; font-weight:700; color:var(--text-secondary); }
.gateway-btn.active { border-color:var(--color-accent); color:var(--color-accent); background:rgba(0,0,0,0.03); }
.tabs { display:flex; gap:0.5rem; margin-bottom:1.5rem; border-bottom:1px solid var(--border-color); }
.tab-btn { padding:0.6rem 1rem; background:none; border:none; border-bottom:2px solid transparent; cursor:pointer; font-weight:600; color:var(--text-secondary); }
.tab-btn.active { color:var(--color-accent); border-bottom-color:var(--color-accent); }
.tab-btn:disabled { opacity:0.4; cursor:not-allowed; }
.tab-panel { display:flex; flex-direction:column; gap:1rem; }
.hint { font-size:0.875rem; color:var(--text-secondary); }
.link { color:var(--color-accent); font-weight:600; }
.qr-box { display:flex; flex-direction:column; gap:0.5rem; align-items:flex-start; margin-top:1rem; }
.form-group { display:flex; flex-direction:column; gap:0.5rem; }
.form-label { font-weight:600; font-size:0.875rem; }
.cuotas-options { display:flex; flex-direction:column; gap:0.5rem; }
.cuota-option { display:flex; align-items:center; gap:0.5rem; font-size:0.9rem; cursor:pointer; }
.form-error { color:var(--color-danger); font-size:0.8rem; }
.pagar-resumen h3 { margin-bottom:1rem; }
.resumen-items { display:flex; flex-direction:column; gap:0.5rem; }
.resumen-item { display:flex; justify-content:space-between; font-size:0.875rem; }
.resumen-divider { border:none; border-top:1px solid var(--border-color); margin:0.75rem 0; }
.resumen-total-line { display:flex; justify-content:space-between; font-weight:700; font-size:1.1rem; }
.resumen-usd { font-size:0.8rem; color:var(--text-secondary); margin-top:0.5rem; }
@media (max-width:768px) { .pagar-layout { grid-template-columns:1fr; } }
</style>
