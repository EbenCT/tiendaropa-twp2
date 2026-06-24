<template>
  <AppLayout>
    <div class="container">
      <div class="admin-page fade-in">
        <Link :href="route('admin.usuarios.index')" class="back-link">← Volver a usuarios</Link>
        <h1 class="page-title">{{ usuario ? 'Editar Usuario' : 'Nuevo Usuario' }}</h1>
        <form @submit.prevent="submit" class="form-card card" style="max-width:560px">
          <div class="form-stack">
            <div class="form-row">
              <div class="form-group">
                <label class="form-label">CI *</label>
                <input v-model="form.ci" class="input" :disabled="!!usuario" />
                <p v-if="form.errors.ci" class="form-error">{{ form.errors.ci }}</p>
              </div>
              <div class="form-group">
                <label class="form-label">Teléfono</label>
                <input v-model="form.telefono" class="input" />
              </div>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label class="form-label">Nombre *</label>
                <input v-model="form.nombre" class="input" />
                <p v-if="form.errors.nombre" class="form-error">{{ form.errors.nombre }}</p>
              </div>
              <div class="form-group">
                <label class="form-label">Apellido *</label>
                <input v-model="form.apellido" class="input" />
                <p v-if="form.errors.apellido" class="form-error">{{ form.errors.apellido }}</p>
              </div>
            </div>
            <div class="form-group">
              <label class="form-label">Email *</label>
              <input v-model="form.email" class="input" type="email" />
              <p v-if="form.errors.email" class="form-error">{{ form.errors.email }}</p>
            </div>
            <div class="form-group">
              <label class="form-label">{{ usuario ? 'Nueva contraseña (dejar vacío para mantener)' : 'Contraseña *' }}</label>
              <input v-model="form.password" class="input" type="password" />
              <p v-if="form.errors.password" class="form-error">{{ form.errors.password }}</p>
            </div>
            <div class="form-group">
              <label class="form-label">Rol *</label>
              <select v-model="form.rol_nuevo" class="input">
                <option v-for="r in rolesDisponibles" :key="r.value" :value="r.value">{{ r.label }}</option>
              </select>
            </div>
            <label v-if="usuario" class="check-label">
              <input type="checkbox" v-model="form.activo" /> Activo
            </label>
            <button type="submit" class="btn btn-primary" :disabled="form.processing">
              {{ form.processing ? 'Guardando...' : (usuario ? 'Actualizar' : 'Crear Usuario') }}
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
const props = defineProps({ usuario: Object, rolesDisponibles: Array })
const form = useForm({
  ci: props.usuario?.ci || '',
  nombre: props.usuario?.nombre || '',
  apellido: props.usuario?.apellido || '',
  email: props.usuario?.email || '',
  telefono: props.usuario?.telefono || '',
  password: '',
  rol_nuevo: props.usuario?.rol_nuevo || 'cliente',
  activo: props.usuario?.activo ?? true,
})
function submit() {
  if (props.usuario) form.put(route('admin.usuarios.update', props.usuario.id))
  else form.post(route('admin.usuarios.store'))
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
