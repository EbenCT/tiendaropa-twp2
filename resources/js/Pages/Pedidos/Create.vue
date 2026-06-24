<template>
  <AppLayout>
    <div class="container">
      <div class="checkout-page fade-in">
        <h1 class="page-title">📦 Confirmar Pedido</h1>

        <div class="checkout-layout">
          <!-- Formulario de envío -->
          <div class="checkout-form card">
            <h2>Datos de Entrega</h2>
            <form @submit.prevent="submit" class="form-stack">
              <div class="form-group">
                <label class="form-label">Dirección de entrega *</label>
                <textarea v-model="form.direccion" class="input" rows="3" placeholder="Calle, número, zona, ciudad..."></textarea>
                <p v-if="form.errors.direccion" class="form-error">{{ form.errors.direccion }}</p>
              </div>
              <div class="form-group">
                <label class="form-label">Teléfono de contacto *</label>
                <input v-model="form.telefono" class="input" type="text" placeholder="7xxxxxxx" />
                <p v-if="form.errors.telefono" class="form-error">{{ form.errors.telefono }}</p>
              </div>
              <div class="form-group">
                <label class="form-label">Referencia (opcional)</label>
                <input v-model="form.referencia" class="input" type="text" placeholder="Cerca de..." />
              </div>
              <button type="submit" class="btn btn-primary btn-full" :disabled="form.processing">
                {{ form.processing ? 'Procesando...' : 'Confirmar Pedido' }}
              </button>
            </form>
          </div>

          <!-- Resumen -->
          <div class="checkout-resumen card">
            <h3>Resumen</h3>
            <div class="resumen-items">
              <div v-for="item in items" :key="item.id" class="resumen-item">
                <span>{{ item.producto?.nombre }} × {{ item.cantidad }}</span>
                <span>Bs. {{ Number(item.cantidad * item.producto?.precio_unitario).toFixed(2) }}</span>
              </div>
            </div>
            <hr class="resumen-divider" />
            <div class="resumen-total-line">
              <span>Total</span>
              <span>Bs. {{ Number(total).toFixed(2) }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { useForm } from '@inertiajs/vue3'

defineProps({
  items: { type: Array, default: () => [] },
  total: { type: Number, default: 0 },
})

const form = useForm({
  direccion: '',
  telefono: '',
  referencia: '',
})

function submit() {
  form.post(route('pedidos.store'))
}
</script>

<style scoped>
.checkout-page { padding:2rem 0; }
.page-title { font-size:2rem; margin-bottom:2rem; }
.checkout-layout { display:grid; grid-template-columns:1fr 380px; gap:2rem; align-items:start; }
.checkout-form h2 { margin-bottom:1.5rem; font-size:1.25rem; }
.form-stack { display:flex; flex-direction:column; gap:1.25rem; }
.form-group { display:flex; flex-direction:column; gap:0.375rem; }
.form-label { font-weight:600; font-size:0.875rem; }
.form-error { color:var(--color-danger); font-size:0.8rem; }
.btn-full { width:100%; justify-content:center; padding:0.75rem; }
.checkout-resumen h3 { margin-bottom:1rem; }
.resumen-items { display:flex; flex-direction:column; gap:0.5rem; }
.resumen-item { display:flex; justify-content:space-between; font-size:0.875rem; }
.resumen-divider { border:none; border-top:1px solid var(--border-color); margin:0.75rem 0; }
.resumen-total-line { display:flex; justify-content:space-between; font-weight:700; font-size:1.1rem; }
@media (max-width:768px) { .checkout-layout { grid-template-columns:1fr; } }
</style>
