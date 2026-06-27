<template>
  <AppLayout>
    <div class="container">
      <div class="admin-page fade-in">
        <Link :href="route('admin.inventario.index')" class="back-link">← Volver a inventario</Link>
        <h1 class="page-title">Registrar Movimiento</h1>
        <form @submit.prevent="submit" class="form-card card" style="max-width:500px">
          <div class="form-stack">
            <div class="form-group">
              <label class="form-label">Producto *</label>
              <select v-model="form.producto_id" class="input">
                <option value="">Selecciona un producto</option>
                <option v-for="p in productos" :key="p.id" :value="p.id">{{ p.nombre }} (stock: {{ p.stock_actual }})</option>
              </select>
              <p v-if="form.errors.producto_id" class="form-error">{{ form.errors.producto_id }}</p>
            </div>
            <div class="form-group">
              <label class="form-label">Tipo *</label>
              <select v-model="form.tipo" class="input">
                <option value="entrada">Entrada</option>
                <option value="salida">Salida</option>
              </select>
            </div>
            <div class="form-group">
              <label class="form-label">Cantidad *</label>
              <input v-model.number="form.cantidad" class="input" type="number" min="1" />
              <p v-if="form.errors.cantidad" class="form-error">{{ form.errors.cantidad }}</p>
            </div>
            <div class="form-group">
              <label class="form-label">Técnica</label>
              <select v-model="form.tecnica" class="input">
                <option value="PROMEDIO">Promedio</option>
                <option value="PEPS">FIFO (PEPS)</option>
                <option value="UEPS">LIFO (UEPS)</option>
              </select>
            </div>
            <button type="submit" class="btn btn-primary" :disabled="form.processing">
              {{ form.processing ? 'Registrando...' : 'Registrar Movimiento' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { Link, useForm } from '@inertiajs/vue3'
defineProps({ productos: Array })
const form = useForm({ producto_id: '', tipo: 'entrada', cantidad: 1, tecnica: 'PROMEDIO' })
function submit() { form.post(route('admin.inventario.store')) }
</script>

<style scoped>
.admin-page { padding:2rem 0; }
.back-link { display:inline-block; margin-bottom:1rem; font-size:0.875rem; color:var(--color-accent); }
.page-title { font-size:1.75rem; margin-bottom:1.5rem; }
.form-stack { display:flex; flex-direction:column; gap:1.25rem; }
.form-group { display:flex; flex-direction:column; gap:0.375rem; }
.form-label { font-weight:600; font-size:0.875rem; }
.form-error { color:var(--color-danger); font-size:0.8rem; }
</style>
