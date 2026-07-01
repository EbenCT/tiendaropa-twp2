<template>
  <AppLayout>
    <div class="container">
      <div class="favs-page fade-in">
        <h1 class="page-title"><i class="fa-solid fa-heart"></i> Mis Favoritos</h1>

        <div v-if="favoritos.length" class="productos-grid">
          <div v-for="fav in favoritos" :key="fav.id" class="fav-card producto-card">
            <Link :href="route('catalogo.show', fav.producto.id)">
              <div class="pc-img-wrap">
                <img :src="fav.producto.imagen_url || '/img/placeholder.jpg'" :alt="fav.producto.nombre" loading="lazy" />
              </div>
              <div class="producto-card-body">
                <p class="producto-card-categoria">{{ fav.producto.categoria?.nombre }}</p>
                <h3 class="producto-card-nombre">{{ fav.producto.nombre }}</h3>
                <p class="producto-card-precio">Bs. {{ Number(fav.producto.precio_unitario).toFixed(2) }}</p>
              </div>
            </Link>
            <div class="fav-actions">
              <button @click="agregarCarrito(fav.producto.id)" class="btn btn-primary btn-sm"><i class="fa-solid fa-cart-shopping"></i> Agregar</button>
              <button @click="quitarFavorito(fav.producto.id)" class="btn btn-outline btn-sm"><i class="fa-solid fa-xmark"></i> Quitar</button>
            </div>
          </div>
        </div>

        <div v-else class="empty-state">
          <div class="empty-icon"><i class="fa-regular fa-heart"></i></div>
          <p>No tienes productos favoritos aún</p>
          <Link :href="route('catalogo')" class="btn btn-primary" style="margin-top:1rem">Explorar Catálogo</Link>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { Link, router } from '@inertiajs/vue3'

defineProps({
  favoritos: { type: Array, default: () => [] },
})

function agregarCarrito(productoId) {
  router.post(route('carrito.agregar'), { producto_id: productoId, cantidad: 1 }, { preserveScroll: true })
}

function quitarFavorito(productoId) {
  router.post(route('favoritos.toggle', productoId), {}, { preserveScroll: true })
}
</script>

<style scoped>
.favs-page { padding:2rem 0; }
.page-title { font-size:2rem; margin-bottom:2rem; }
.fav-card { position:relative; }
.fav-actions { display:flex; gap:0.5rem; padding:0 1rem 1rem; }
.btn-sm { padding:0.35rem 0.75rem; font-size:0.8rem; }
.empty-state { text-align:center; padding:4rem 0; color:var(--text-secondary); }
.empty-icon { font-size:4rem; margin-bottom:1rem; color:var(--text-secondary); }
</style>
