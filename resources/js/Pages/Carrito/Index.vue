<template>
  <AppLayout>
    <div class="container">
      <div class="carrito-page fade-in">
        <h1 class="page-title">🛒 Mi Carrito</h1>

        <div v-if="items.length" class="carrito-layout">
          <div class="carrito-items">
            <div v-for="item in items" :key="item.id" class="carrito-item card">
              <img :src="item.producto.imagen || '/img/placeholder.jpg'" :alt="item.producto.nombre" class="item-img" />
              <div class="item-info">
                <h3 class="item-nombre">{{ item.producto.nombre }}</h3>
                <p class="item-categoria">{{ item.producto.categoria }}</p>
                <p v-if="item.talla" class="item-talla">Talla: <strong>{{ item.talla.codigo }}</strong></p>
                <p class="item-precio">Bs. {{ Number(item.producto.precio_unitario).toFixed(2) }}</p>
              </div>
              <div class="item-cantidad">
                <button @click="actualizar(item.id, item.cantidad - 1)" :disabled="item.cantidad <= 1" class="cantidad-btn">−</button>
                <span class="cantidad-val">{{ item.cantidad }}</span>
                <button @click="actualizar(item.id, item.cantidad + 1)" class="cantidad-btn">+</button>
              </div>
              <div class="item-subtotal">
                <p class="subtotal-label">Subtotal</p>
                <p class="subtotal-valor">Bs. {{ Number(item.subtotal).toFixed(2) }}</p>
              </div>
              <button @click="eliminar(item.id)" class="item-eliminar" title="Eliminar">🗑️</button>
            </div>
          </div>

          <div class="carrito-resumen card">
            <h3>Resumen del Pedido</h3>
            <div class="resumen-linea">
              <span>Productos ({{ items.length }})</span>
              <span>Bs. {{ Number(total).toFixed(2) }}</span>
            </div>
            <div class="resumen-linea">
              <span>Envío</span>
              <span class="envio-gratis">Gratis</span>
            </div>
            <hr class="resumen-divider" />
            <div class="resumen-linea resumen-total">
              <span>Total</span>
              <span>Bs. {{ Number(total).toFixed(2) }}</span>
            </div>
            <Link :href="route('pedidos.create')" class="btn btn-primary btn-full" style="margin-top:1rem">
              Realizar Pedido
            </Link>
          </div>
        </div>

        <div v-else class="empty-state">
          <p class="empty-icon">🛒</p>
          <p>Tu carrito está vacío</p>
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
  items: { type: Array, default: () => [] },
  total: { type: Number, default: 0 },
})

function actualizar(id, cantidad) {
  if (cantidad < 1) return
  router.patch(route('carrito.actualizar', id), { cantidad }, { preserveScroll: true })
}

function eliminar(id) {
  router.delete(route('carrito.eliminar', id), { preserveScroll: true })
}
</script>

<style scoped>
.carrito-page { padding:2rem 0; }
.page-title { font-size:2rem; margin-bottom:2rem; }
.carrito-layout { display:grid; grid-template-columns:1fr 360px; gap:2rem; align-items:start; }
.carrito-items { display:flex; flex-direction:column; gap:1rem; }
.carrito-item { display:flex; align-items:center; gap:1rem; padding:1rem; }
.item-img { width:80px; height:100px; object-fit:cover; border-radius:var(--radius-sm); }
.item-info { flex:1; }
.item-nombre { font-size:1rem; font-weight:600; margin-bottom:0.15rem; }
.item-categoria { font-size:0.75rem; color:var(--text-secondary); text-transform:uppercase; }
.item-talla { font-size:0.8rem; color:var(--text-secondary); }
.item-precio { font-size:0.9rem; color:var(--color-accent); font-weight:600; margin-top:0.25rem; }
.item-cantidad { display:flex; align-items:center; gap:0.4rem; }
.cantidad-btn { width:32px; height:32px; border:1px solid var(--border-color); border-radius:var(--radius-sm); background:var(--bg-card); cursor:pointer; font-size:1rem; font-weight:700; display:flex; align-items:center; justify-content:center; }
.cantidad-btn:hover { background:var(--color-primary); color:var(--text-on-primary); }
.cantidad-btn:disabled { opacity:0.4; cursor:not-allowed; }
.cantidad-val { min-width:1.5rem; text-align:center; font-weight:600; }
.item-subtotal { text-align:right; min-width:100px; }
.subtotal-label { font-size:0.7rem; color:var(--text-secondary); text-transform:uppercase; }
.subtotal-valor { font-size:1.1rem; font-weight:700; color:var(--color-accent); }
.item-eliminar { background:none; border:none; cursor:pointer; font-size:1.1rem; opacity:0.6; transition:opacity 0.2s; }
.item-eliminar:hover { opacity:1; }
.carrito-resumen h3 { margin-bottom:1rem; }
.resumen-linea { display:flex; justify-content:space-between; margin-bottom:0.5rem; font-size:0.9rem; }
.resumen-total { font-weight:700; font-size:1.1rem; }
.envio-gratis { color:var(--color-success); font-weight:600; }
.resumen-divider { border:none; border-top:1px solid var(--border-color); margin:0.75rem 0; }
.btn-full { width:100%; justify-content:center; }
.empty-state { text-align:center; padding:4rem 0; color:var(--text-secondary); }
.empty-icon { font-size:4rem; margin-bottom:1rem; }
@media (max-width:768px) { .carrito-layout { grid-template-columns:1fr; } .carrito-item { flex-wrap:wrap; } }
</style>
