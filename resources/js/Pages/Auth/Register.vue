<template>
  <AppLayout>
    <div class="container">
      <div class="auth-page fade-in">
        <div class="auth-card card">
          <div class="auth-header">
            <h1 class="auth-title">Crear Cuenta</h1>
            <p class="auth-subtitle">Únete y comienza a comprar</p>
          </div>

          <form @submit.prevent="submit" class="auth-form">
            <div class="form-row">
              <div class="form-group">
                <label for="ci" class="form-label">Carnet de Identidad</label>
                <input id="ci" v-model="form.ci" type="text" class="input" placeholder="12345678" />
                <p v-if="form.errors.ci" class="form-error">{{ form.errors.ci }}</p>
              </div>
              <div class="form-group">
                <label for="telefono" class="form-label">Teléfono (opcional)</label>
                <input id="telefono" v-model="form.telefono" type="text" class="input" placeholder="7xxxxxxx" />
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label for="nombre" class="form-label">Nombre</label>
                <input id="nombre" v-model="form.nombre" type="text" class="input" placeholder="Juan" />
                <p v-if="form.errors.nombre" class="form-error">{{ form.errors.nombre }}</p>
              </div>
              <div class="form-group">
                <label for="apellido" class="form-label">Apellido</label>
                <input id="apellido" v-model="form.apellido" type="text" class="input" placeholder="Pérez" />
                <p v-if="form.errors.apellido" class="form-error">{{ form.errors.apellido }}</p>
              </div>
            </div>

            <div class="form-group">
              <label for="email" class="form-label">Correo electrónico</label>
              <input id="email" v-model="form.email" type="email" class="input" placeholder="tu@email.com" />
              <p v-if="form.errors.email" class="form-error">{{ form.errors.email }}</p>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label for="password" class="form-label">Contraseña</label>
                <input id="password" v-model="form.password" type="password" class="input" placeholder="Min. 8 caracteres" />
                <p v-if="form.errors.password" class="form-error">{{ form.errors.password }}</p>
              </div>
              <div class="form-group">
                <label for="password_confirmation" class="form-label">Confirmar contraseña</label>
                <input id="password_confirmation" v-model="form.password_confirmation" type="password" class="input" placeholder="Repite la contraseña" />
              </div>
            </div>

            <button type="submit" class="btn btn-primary btn-full" :disabled="form.processing">
              {{ form.processing ? 'Creando cuenta...' : 'Crear Cuenta' }}
            </button>

            <p class="auth-link-text">
              ¿Ya tienes cuenta?
              <Link :href="route('login')" class="auth-link">Inicia sesión</Link>
            </p>
          </form>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { Link, useForm } from '@inertiajs/vue3'

const form = useForm({
  ci: '',
  nombre: '',
  apellido: '',
  email: '',
  telefono: '',
  password: '',
  password_confirmation: '',
})

function submit() {
  form.post(route('registro'), {
    onFinish: () => form.reset('password', 'password_confirmation'),
  })
}
</script>

<style scoped>
.auth-page { display:flex; justify-content:center; padding:3rem 0; }
.auth-card { max-width:560px; width:100%; }
.auth-header { text-align:center; margin-bottom:2rem; }
.auth-title { font-size:1.75rem; margin-bottom:0.5rem; }
.auth-subtitle { color:var(--text-secondary); font-size:0.9rem; }
.auth-form { display:flex; flex-direction:column; gap:1.25rem; }
.form-row { display:grid; grid-template-columns:1fr 1fr; gap:1rem; }
.form-group { display:flex; flex-direction:column; gap:0.375rem; }
.form-label { font-weight:600; font-size:0.875rem; color:var(--text-primary); }
.form-error { color:var(--color-danger); font-size:0.8rem; margin-top:0.2rem; }
.btn-full { width:100%; justify-content:center; padding:0.75rem; font-size:1rem; }
.auth-link-text { text-align:center; font-size:0.875rem; color:var(--text-secondary); }
.auth-link { font-weight:600; }
@media (max-width:500px) { .form-row { grid-template-columns:1fr; } }
</style>
