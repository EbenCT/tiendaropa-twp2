<template>
  <AppLayout>
    <div class="container">
      <div class="metodos-page fade-in">
        <h1 class="page-title">💳 Mis Métodos de Pago</h1>

        <div class="metodos-list">
          <div v-for="m in metodos" :key="m.id" class="card metodo-card">
            <div>
              <strong>{{ m.brand }} **** {{ m.last4 }}</strong>
              <span v-if="m.es_principal" class="badge-principal">Principal</span>
            </div>
            <div class="metodo-actions">
              <button v-if="!m.es_principal" class="btn btn-outline btn-sm" @click="marcarPrincipal(m.id)">Marcar como principal</button>
              <button class="btn btn-danger btn-sm" @click="eliminar(m.id)">Eliminar</button>
            </div>
          </div>
          <p v-if="metodos.length === 0" class="hint">No tienes métodos de pago guardados.</p>
        </div>

        <div class="card add-card">
          <h3>Agregar nueva tarjeta</h3>
          <button v-if="!mostrandoForm" class="btn btn-primary" @click="iniciarFormulario">Agregar tarjeta</button>
          <div v-else>
            <div id="payment-element"></div>
            <button class="btn btn-primary" style="margin-top:1rem" :disabled="guardando" @click="confirmarTarjeta">
              {{ guardando ? 'Guardando...' : 'Guardar tarjeta' }}
            </button>
            <p v-if="error" class="form-error">{{ error }}</p>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { router, usePage } from '@inertiajs/vue3'
import { ref } from 'vue'
import axios from 'axios'
import { loadStripe } from '@stripe/stripe-js'

defineProps({
  metodos: { type: Array, default: () => [] },
})

const page = usePage()
const mostrandoForm = ref(false)
const guardando = ref(false)
const error = ref(null)
let stripe = null
let elements = null

async function iniciarFormulario() {
  mostrandoForm.value = true
  const { data } = await axios.post(route('metodos-pago.setup-intent'))

  stripe = await loadStripe(page.props.stripe.publishableKey)
  elements = stripe.elements({ clientSecret: data.clientSecret })
  const paymentElement = elements.create('payment')
  paymentElement.mount('#payment-element')
}

async function confirmarTarjeta() {
  guardando.value = true
  error.value = null

  const { error: stripeError } = await stripe.confirmSetup({
    elements,
    confirmParams: { return_url: route('metodos-pago.index') },
  })

  if (stripeError) {
    error.value = stripeError.message
    guardando.value = false
  }
}

function marcarPrincipal(id) {
  router.patch(route('metodos-pago.principal', id), {}, { preserveScroll: true })
}

function eliminar(id) {
  router.delete(route('metodos-pago.eliminar', id), { preserveScroll: true })
}
</script>

<style scoped>
.metodos-page { padding:2rem 0; }
.page-title { font-size:2rem; margin-bottom:2rem; }
.metodos-list { display:flex; flex-direction:column; gap:1rem; margin-bottom:2rem; }
.metodo-card { display:flex; justify-content:space-between; align-items:center; }
.badge-principal { margin-left:0.75rem; padding:0.15rem 0.6rem; border-radius:var(--radius-pill); font-size:0.7rem; font-weight:700; text-transform:uppercase; background:color-mix(in srgb, var(--color-success) 20%, var(--bg-card)); color:var(--color-success); }
.metodo-actions { display:flex; gap:0.5rem; }
.btn-sm { padding:0.35rem 0.75rem; font-size:0.8rem; }
.btn-danger { background:var(--color-danger); color:#fff; border:none; }
.hint { color:var(--text-secondary); font-size:0.9rem; }
.add-card h3 { margin-bottom:1rem; }
.form-error { color:var(--color-danger); font-size:0.85rem; margin-top:0.75rem; }
</style>
