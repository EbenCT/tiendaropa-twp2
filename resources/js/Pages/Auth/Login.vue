<template>
  <AppLayout>
    <div class="container">
      <div class="auth-page fade-in">
        <div class="auth-card card">
          <div class="auth-header">
            <h1 class="auth-title">Iniciar Sesión</h1>
            <p class="auth-subtitle">Ingresa a tu cuenta para continuar</p>
          </div>

          <form @submit.prevent="submit" class="auth-form">
            <div class="form-group">
              <label for="email" class="form-label">Correo electrónico</label>
              <input
                id="email"
                v-model="form.email"
                type="email"
                class="input"
                placeholder="tu@email.com"
                autocomplete="email"
              />
              <p v-if="form.errors.email" class="form-error">{{ form.errors.email }}</p>
            </div>

            <div class="form-group">
              <label for="password" class="form-label">Contraseña</label>
              <input
                id="password"
                v-model="form.password"
                type="password"
                class="input"
                placeholder="••••••••"
                autocomplete="current-password"
              />
              <p v-if="form.errors.password" class="form-error">{{ form.errors.password }}</p>
            </div>

            <div class="form-check">
              <label class="check-label">
                <input type="checkbox" v-model="form.recordarme" />
                <span>Recordarme</span>
              </label>
            </div>

            <button type="submit" class="btn btn-primary btn-full" :disabled="form.processing">
              {{ form.processing ? 'Ingresando...' : 'Ingresar' }}
            </button>

            <p class="auth-link-text">
              ¿No tienes cuenta?
              <Link :href="route('registro')" class="auth-link">Regístrate aquí</Link>
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
  email: '',
  password: '',
  recordarme: false,
})

function submit() {
  form.post(route('login'), {
    onFinish: () => form.reset('password'),
  })
}
</script>

<style scoped>
.auth-page { display:flex; justify-content:center; padding:3rem 0; }
.auth-card { max-width:440px; width:100%; }
.auth-header { text-align:center; margin-bottom:2rem; }
.auth-title { font-size:1.75rem; margin-bottom:0.5rem; }
.auth-subtitle { color:var(--text-secondary); font-size:0.9rem; }
.auth-form { display:flex; flex-direction:column; gap:1.25rem; }
.form-group { display:flex; flex-direction:column; gap:0.375rem; }
.form-label { font-weight:600; font-size:0.875rem; color:var(--text-primary); }
.form-error { color:var(--color-danger); font-size:0.8rem; margin-top:0.2rem; }
.form-check { display:flex; align-items:center; }
.check-label { display:flex; align-items:center; gap:0.5rem; cursor:pointer; font-size:0.875rem; color:var(--text-secondary); }
.btn-full { width:100%; justify-content:center; padding:0.75rem; font-size:1rem; }
.auth-link-text { text-align:center; font-size:0.875rem; color:var(--text-secondary); }
.auth-link { font-weight:600; }
</style>
