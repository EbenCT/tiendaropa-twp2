<template>
  <div class="producto-card">
    <!-- Zona clickeable → detalle del producto -->
    <Link :href="route('catalogo.show', producto.id)" class="pc-link">
      <div class="pc-img-wrap">
        <img
          :src="producto.imagen_principal?.url ?? producto.imagen_url ?? '/img/placeholder.jpg'"
          :alt="producto.nombre"
          loading="lazy"
        />
        <div v-if="producto.destacado" class="pc-badge-destacado">
          <i class="fa-solid fa-star"></i>
        </div>
        <div v-if="producto.es_nueva_coleccion" class="pc-badge-nuevo">Nuevo</div>
        <div v-if="producto.stock_actual === 0" class="pc-badge-agotado">Agotado</div>
      </div>
      <div class="producto-card-body">
        <p class="producto-card-categoria">{{ producto.categoria?.nombre }}</p>
        <h3 :class="['producto-card-nombre', { small }]">{{ producto.nombre }}</h3>
        <p class="producto-card-precio">Bs. {{ Number(producto.precio_unitario).toFixed(2) }}</p>
      </div>
    </Link>

    <!-- Botones de acción (solo para clientes autenticados) -->
    <div v-if="showActions" class="pc-actions">
      <button
        @click.stop="agregarCarrito"
        class="pc-btn pc-btn-cart"
        :disabled="producto.stock_actual === 0"
        :title="producto.stock_actual === 0 ? 'Sin stock' : 'Agregar al carrito'"
      >
        <i class="fa-solid fa-cart-plus"></i>
        <span>Agregar</span>
      </button>
      <button
        @click.stop="toggleFavorito"
        :class="['pc-btn', 'pc-btn-fav', { active: esFavorito }]"
        title="Favoritos"
      >
        <i :class="esFavorito ? 'fa-solid fa-heart' : 'fa-regular fa-heart'"></i>
      </button>
    </div>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'

const props = defineProps({
  producto: { type: Object, required: true },
  small:    { type: Boolean, default: false },
})

const page = usePage()
const nivel = computed(() => page.props.auth?.user?.nivel ?? 0)
const showActions = computed(() => nivel.value === 1)

const esFavorito = ref(props.producto.es_favorito ?? false)

function agregarCarrito() {
  if (!page.props.auth?.user) { router.visit(route('login')); return }
  const tallaId = props.producto.tallas?.[0]?.id ?? null
  router.post(route('carrito.agregar'), {
    producto_id: props.producto.id,
    talla_id: tallaId,
    cantidad: 1,
  }, { preserveScroll: true })
}

function toggleFavorito() {
  if (!page.props.auth?.user) { router.visit(route('login')); return }
  esFavorito.value = !esFavorito.value
  router.post(route('favoritos.toggle', props.producto.id), {}, { preserveScroll: true })
}
</script>

<style scoped>
.producto-card { display: flex; flex-direction: column; }
.pc-link { display: flex; flex-direction: column; flex: 1; text-decoration: none; }
.pc-img-wrap { position: relative; overflow: hidden; }
.pc-img-wrap img { transition: transform 0.3s ease; }
.producto-card:hover .pc-img-wrap img { transform: scale(1.04); }

.pc-badge-destacado {
  position: absolute; top: 0.5rem; left: 0.5rem;
  background: var(--color-accent); color: var(--color-primary);
  border-radius: 50%; width: 28px; height: 28px;
  display: flex; align-items: center; justify-content: center; font-size: 0.8rem;
}
.pc-badge-nuevo {
  position: absolute; top: 0.5rem; right: 0.5rem;
  background: var(--color-primary); color: var(--text-on-primary);
  padding: 0.15rem 0.5rem; border-radius: var(--radius-pill); font-size: 0.68rem; font-weight: 700;
}
.pc-badge-agotado {
  position: absolute; bottom: 0; left: 0; right: 0;
  background: rgba(0,0,0,0.55); color: white;
  text-align: center; font-size: 0.72rem; font-weight: 700; padding: 0.25rem;
}

.producto-card-categoria { font-size: 0.7rem; text-transform: uppercase; color: var(--text-secondary); letter-spacing: 0.05em; margin-bottom: 0.15rem; }
.producto-card-nombre.small { font-size: 0.85rem; }
.producto-card-body { padding: 0.875rem; flex: 1; }

/* Acciones */
.pc-actions {
  display: flex; gap: 0.5rem; padding: 0 0.875rem 0.875rem;
  opacity: 0; transform: translateY(4px); transition: opacity 0.2s, transform 0.2s;
}
.producto-card:hover .pc-actions { opacity: 1; transform: translateY(0); }

.pc-btn {
  display: flex; align-items: center; gap: 0.35rem;
  padding: 0.4rem 0.75rem; border-radius: var(--radius-sm);
  font-size: 0.8rem; font-weight: 600; cursor: pointer;
  border: none; font-family: var(--font-body); transition: all 0.18s;
}
.pc-btn-cart {
  flex: 1; background: var(--color-primary); color: var(--text-on-primary);
  justify-content: center;
}
.pc-btn-cart:hover:not(:disabled) { background: var(--color-primary-dark); }
.pc-btn-cart:disabled { opacity: 0.4; cursor: not-allowed; }
.pc-btn-fav {
  background: var(--bg-secondary); color: var(--text-secondary);
  border: 1.5px solid var(--border-color);
}
.pc-btn-fav:hover { border-color: var(--color-danger); color: var(--color-danger); }
.pc-btn-fav.active { background: color-mix(in srgb, var(--color-danger) 15%, var(--bg-card)); color: var(--color-danger); border-color: var(--color-danger); }
</style>
