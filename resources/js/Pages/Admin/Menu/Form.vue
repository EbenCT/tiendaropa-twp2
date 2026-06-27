<template>
  <AppLayout>
    <div class="container">
      <div class="admin-page fade-in">
        <Link :href="route('admin.menu.index')" class="back-link">← Volver al menú</Link>
        <h1 class="page-title">{{ item ? 'Editar Ítem de Menú' : 'Nuevo Ítem de Menú' }}</h1>

        <form @submit.prevent="submit" class="form-card card" style="max-width:560px">
          <div class="form-stack">
            <div class="form-group">
              <label class="form-label">Label *</label>
              <input v-model="form.label" class="input" placeholder="Ej: Promociones" />
              <p v-if="form.errors.label" class="form-error">{{ form.errors.label }}</p>
            </div>
            <div class="form-group">
              <label class="form-label">Nombre de ruta (Ziggy) *</label>
              <input v-model="form.route_name" class="input" placeholder="Ej: catalogo.show" />
              <p v-if="form.errors.route_name" class="form-error">{{ form.errors.route_name }}</p>
            </div>
            <div class="form-group">
              <label class="form-label">Ícono</label>
              <input v-model="form.icon" class="input" placeholder="Ej: shopping-cart" />
            </div>
            <div class="form-row">
              <div class="form-group">
                <label class="form-label">Nivel mínimo de rol *</label>
                <select v-model.number="form.role_nivel_minimo" class="input">
                  <option :value="0">0 - Invitado</option>
                  <option :value="1">1 - Cliente</option>
                  <option :value="2">2 - Vendedor</option>
                  <option :value="3">3 - Propietario</option>
                  <option :value="4">4 - Admin</option>
                </select>
                <p v-if="form.errors.role_nivel_minimo" class="form-error">{{ form.errors.role_nivel_minimo }}</p>
              </div>
              <div class="form-group">
                <label class="form-label">Orden *</label>
                <input v-model.number="form.orden" class="input" type="number" min="0" />
                <p v-if="form.errors.orden" class="form-error">{{ form.errors.orden }}</p>
              </div>
            </div>
            <div class="form-group">
              <label class="form-label">Ítem padre (opcional)</label>
              <select v-model="form.parent_id" class="input">
                <option :value="null">— Ninguno (ítem de primer nivel) —</option>
                <option v-for="p in padres" :key="p.id" :value="p.id">{{ p.label }}</option>
              </select>
              <p v-if="form.errors.parent_id" class="form-error">{{ form.errors.parent_id }}</p>
            </div>
            <label v-if="item" class="check-label">
              <input type="checkbox" v-model="form.activo" /> Activo
            </label>
            <button type="submit" class="btn btn-primary" :disabled="form.processing">
              {{ form.processing ? 'Guardando...' : (item ? 'Actualizar' : 'Crear Ítem') }}
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

const props = defineProps({
  item:   { type: Object, default: null },
  padres: { type: Array, default: () => [] },
})

const form = useForm({
  label: props.item?.label || '',
  route_name: props.item?.route_name || '',
  icon: props.item?.icon || '',
  role_nivel_minimo: props.item?.role_nivel_minimo ?? 0,
  parent_id: props.item?.parent_id ?? null,
  orden: props.item?.orden ?? 0,
  activo: props.item?.activo ?? true,
})

function submit() {
  if (props.item) form.put(route('admin.menu.update', props.item.id))
  else form.post(route('admin.menu.store'))
}
</script>

<style scoped>
.admin-page { padding:2rem 0; }
.back-link { display:inline-block; margin-bottom:1rem; font-size:0.875rem; color:var(--color-accent); }
.page-title { font-size:1.75rem; margin-bottom:1.5rem; }
.form-stack { display:flex; flex-direction:column; gap:1.25rem; }
.form-row { display:grid; grid-template-columns:1fr 1fr; gap:1rem; }
.form-group { display:flex; flex-direction:column; gap:0.375rem; }
.form-label { font-weight:600; font-size:0.875rem; }
.form-error { color:var(--color-danger); font-size:0.8rem; }
.check-label { display:flex; align-items:center; gap:0.5rem; cursor:pointer; }
</style>
