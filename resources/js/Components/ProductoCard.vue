<template>
  <Link :href="route('catalogo.show', producto.id)" class="producto-card">
    <div class="pc-img-wrap">
      <img
        :src="producto.imagen_principal?.url ?? producto.imagen_url ?? '/img/placeholder.jpg'"
        :alt="producto.nombre"
        loading="lazy"
      />
      <div v-if="producto.destacado" class="pc-badge-destacado">⭐</div>
      <div v-if="producto.es_nueva_coleccion" class="pc-badge-nuevo">Nuevo</div>
    </div>
    <div class="producto-card-body">
      <p class="producto-card-categoria">{{ producto.categoria?.nombre }}</p>
      <h3 :class="['producto-card-nombre', { small: small }]">{{ producto.nombre }}</h3>
      <p class="producto-card-precio">Bs. {{ Number(producto.precio_unitario).toFixed(2) }}</p>
      <p v-if="producto.stock_actual === 0" class="pc-sin-stock">Sin stock</p>
    </div>
  </Link>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
defineProps({
  producto: { type: Object, required: true },
  small:    { type: Boolean, default: false },
})
</script>

<style scoped>
.producto-card { display:block; }
.pc-img-wrap { position:relative; }
.pc-badge-destacado { position:absolute; top:0.5rem; left:0.5rem; background:var(--color-accent); border-radius:50%; width:28px; height:28px; display:flex; align-items:center; justify-content:center; font-size:0.8rem; }
.pc-badge-nuevo { position:absolute; top:0.5rem; right:0.5rem; background:var(--color-primary); color:var(--text-on-primary); padding:0.15rem 0.5rem; border-radius:var(--radius-pill); font-size:0.7rem; font-weight:700; }
.producto-card-categoria { font-size:0.72rem; text-transform:uppercase; color:var(--text-secondary); letter-spacing:0.05em; margin-bottom:0.2rem; }
.producto-card-nombre.small { font-size:0.85rem; }
.pc-sin-stock { font-size:0.75rem; color:var(--color-danger); font-weight:600; margin-top:0.25rem; }
</style>
