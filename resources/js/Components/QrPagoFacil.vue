<template>
  <div class="qr-pf-box">
    <img :src="'data:image/png;base64,' + qr.qrBase64" alt="QR PagoFácil" class="qr-pf-img" />
    <p class="qr-pf-expira">Válido hasta: {{ formatearFecha(qr.expirationDate) }}</p>
    <p v-if="mensajeEstado" class="qr-pf-estado">Estado: {{ mensajeEstado }}</p>
    <div class="qr-pf-actions">
      <button class="btn btn-secondary" :disabled="verificando" @click="emit('verificar')">
        {{ verificando ? 'Verificando...' : 'Ya pagué, verificar' }}
      </button>
    </div>
  </div>
</template>

<script setup>
import { onMounted, onUnmounted } from 'vue'

defineProps({
  qr: { type: Object, required: true },
  verificando: { type: Boolean, default: false },
  mensajeEstado: { type: String, default: '' },
})

const emit = defineEmits(['verificar'])

function formatearFecha(fecha) {
  if (!fecha) return '—'
  return new Date(fecha.replace(' ', 'T')).toLocaleString('es-BO')
}

let intervalo = null

onMounted(() => {
  // Verificación automática cada 10s durante 3 minutos, sin reemplazar el botón manual.
  let intentos = 0
  intervalo = setInterval(() => {
    intentos++
    if (intentos > 18) {
      clearInterval(intervalo)
      return
    }
    emit('verificar')
  }, 10000)
})

onUnmounted(() => {
  if (intervalo) clearInterval(intervalo)
})
</script>

<style scoped>
.qr-pf-box { display:flex; flex-direction:column; align-items:flex-start; gap:0.75rem; }
.qr-pf-img { width:220px; height:220px; border:1px solid var(--border-color); border-radius:8px; background:#fff; padding:0.5rem; }
.qr-pf-expira { font-size:0.8rem; color:var(--text-secondary); }
.qr-pf-estado { font-size:0.875rem; font-weight:600; }
.qr-pf-actions { display:flex; gap:0.5rem; }
.btn-outline { background:none; border:1px solid var(--border-color); color:var(--text-secondary); padding:0.6rem 1rem; border-radius:6px; cursor:pointer; font-weight:600; }
</style>
