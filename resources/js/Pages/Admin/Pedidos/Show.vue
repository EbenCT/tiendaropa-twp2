<template>
  <AppLayout>
    <div class="container">
      <div class="admin-page fade-in">
        <Link :href="route('admin.pedidos.index')" class="back-link">← Volver a pedidos</Link>
        <h1 class="page-title">Pedido #{{ pedido.id }}</h1>
        <div class="show-layout">
          <div class="card">
            <h3>Cliente</h3>
            <p>{{ pedido.usuario?.nombre }} {{ pedido.usuario?.apellido }}</p>
            <p>{{ pedido.usuario?.email }}</p>
            <h3 style="margin-top:1.5rem">Entrega</h3>
            <p><strong>Dirección:</strong> {{ pedido.direccion }}</p>
            <p><strong>Teléfono:</strong> {{ pedido.telefono }}</p>
            <p v-if="pedido.referencia"><strong>Ref:</strong> {{ pedido.referencia }}</p>
            <h3 style="margin-top:1.5rem">Productos</h3>
            <div v-for="d in pedido.detalles" :key="d.id" class="det-item">
              <img :src="d.producto?.imagen_principal?.url || d.producto?.imagen_url || '/img/placeholder.jpg'" class="det-img" />
              <div><strong>{{ d.producto?.nombre }}</strong><br/>Cant: {{ d.cantidad }} · Bs. {{ Number(d.precio_unitario).toFixed(2) }}</div>
              <span>Bs. {{ Number(d.subtotal || d.cantidad * d.precio_unitario).toFixed(2) }}</span>
            </div>
            <p class="total-line">Total: <strong>Bs. {{ Number(pedido.total || 0).toFixed(2) }}</strong></p>
          </div>
          <div class="card">
            <h3>Cambiar Estado</h3>
            <select :value="pedido.estado" @change="cambiarEstado($event.target.value)" class="input">
              <option value="PENDIENTE">Pendiente</option>
              <option value="CONFIRMADO">Confirmado</option>
              <option value="ENVIADO">Enviado</option>
              <option value="ENTREGADO">Entregado</option>
            </select>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { Link, router } from '@inertiajs/vue3'
const props = defineProps({ pedido: Object })
function cambiarEstado(estado) { router.patch(route('admin.pedidos.estado', props.pedido.id), { estado }, { preserveScroll: true }) }
</script>

<style scoped>
.admin-page { padding:2rem 0; }
.back-link { display:inline-block; margin-bottom:1rem; font-size:0.875rem; color:var(--color-accent); }
.page-title { font-size:1.75rem; margin-bottom:1.5rem; }
.show-layout { display:grid; grid-template-columns:1fr 300px; gap:2rem; align-items:start; }
.det-item { display:flex; align-items:center; gap:1rem; padding:0.5rem 0; border-bottom:1px solid var(--border-color); }
.det-img { width:48px; height:60px; object-fit:cover; border-radius:var(--radius-sm); }
.total-line { font-size:1.25rem; margin-top:1rem; text-align:right; }
@media (max-width:768px) { .show-layout { grid-template-columns:1fr; } }
</style>
