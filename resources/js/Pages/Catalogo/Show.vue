<template>
  <AppLayout>
    <div class="container">
      <div class="producto-detail fade-in">
        <!-- Breadcrumb -->
        <nav class="breadcrumb">
          <Link :href="route('catalogo')">Catálogo</Link>
          <span>›</span>
          <span v-if="producto.categoria">{{ producto.categoria.nombre }}</span>
          <span>›</span>
          <span>{{ producto.nombre }}</span>
        </nav>

        <div class="detail-layout">
          <!-- Imágenes -->
          <div class="detail-images">
            <div class="main-image-wrap">
              <img :src="imagenActiva" :alt="producto.nombre" class="main-image" />
            </div>
            <div v-if="todasImagenes.length > 1" class="thumb-gallery">
              <img
                v-for="(img, i) in todasImagenes" :key="i"
                :src="img"
                :class="['thumb', { active: imagenActiva === img }]"
                @click="imagenActiva = img"
                :alt="`${producto.nombre} - ${i+1}`"
              />
            </div>
          </div>

          <!-- Info -->
          <div class="detail-info">
            <h1 class="detail-nombre">{{ producto.nombre }}</h1>
            <p class="detail-categoria">{{ producto.categoria?.nombre }}</p>

            <div class="detail-precio-wrap">
              <span class="detail-precio">Bs. {{ Number(producto.precio_unitario).toFixed(2) }}</span>
              <span v-if="promoActiva" class="detail-descuento">-{{ promoActiva.descuento }}%</span>
            </div>

            <p class="detail-descripcion">{{ producto.descripcion || 'Sin descripción disponible.' }}</p>

            <!-- Tallas -->
            <div v-if="producto.tallas?.length" class="detail-tallas">
              <p class="detail-label">Talla:</p>
              <div class="tallas-grid">
                <button
                  v-for="t in producto.tallas" :key="t.id"
                  :class="['talla-btn', { active: tallaSeleccionada === t.id, 'sin-stock': t.pivot?.stock === 0 }]"
                  @click="tallaSeleccionada = t.id"
                  :disabled="t.pivot?.stock === 0"
                >
                  {{ t.codigo }}
                  <span class="talla-stock">{{ t.pivot?.stock ?? 0 }}</span>
                </button>
              </div>
            </div>

            <!-- Cantidad -->
            <div class="detail-cantidad">
              <p class="detail-label">Cantidad:</p>
              <div class="cantidad-ctrl">
                <button @click="cantidad = Math.max(1, cantidad - 1)" class="cantidad-btn">−</button>
                <span class="cantidad-val">{{ cantidad }}</span>
                <button @click="cantidad++" class="cantidad-btn">+</button>
              </div>
            </div>

            <!-- Acciones -->
            <div class="detail-actions">
              <button @click="agregarCarrito" class="btn btn-primary btn-lg" :disabled="formCarrito.processing">
                <i class="fa-solid fa-cart-shopping"></i> {{ formCarrito.processing ? 'Agregando...' : 'Agregar al Carrito' }}
              </button>
              <button @click="toggleFavorito" class="btn btn-outline btn-icon" :disabled="formFav.processing" :title="esFavorito ? 'Quitar de favoritos' : 'Agregar a favoritos'">
                <i :class="esFavorito ? 'fa-solid fa-heart' : 'fa-regular fa-heart'"></i>
              </button>
            </div>

            <!-- Métricas -->
            <div class="detail-metricas">
              <div class="metrica">
                <span class="metrica-valor">{{ totalVendido }}</span>
                <span class="metrica-label">unidades vendidas</span>
              </div>
              <div class="metrica">
                <span class="metrica-valor">{{ producto.stock_actual }}</span>
                <span class="metrica-label">en stock</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Relacionados -->
        <section v-if="relacionados.length" class="section-relacionados">
          <h2 class="section-title">Productos Relacionados</h2>
          <div class="productos-grid">
            <ProductoCard v-for="p in relacionados" :key="p.id" :producto="p" />
          </div>
        </section>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import ProductoCard from '@/Components/ProductoCard.vue'
import { Link, useForm, usePage } from '@inertiajs/vue3'

const props = defineProps({
  producto:     { type: Object, required: true },
  relacionados: { type: Array, default: () => [] },
  totalVendido: { type: Number, default: 0 },
})

const todasImagenes = computed(() => {
  const imgs = (props.producto.imagenes || []).map(i => i.url)
  if (!imgs.length && props.producto.imagen_url) imgs.push(props.producto.imagen_url)
  return imgs.length ? imgs : ['/img/placeholder.jpg']
})

const imagenActiva = ref(todasImagenes.value[0])
const tallaSeleccionada = ref(null)
const cantidad = ref(1)

const promoActiva = computed(() => {
  return (props.producto.promociones || []).find(p =>
    p.activo && new Date(p.fecha_inicio) <= new Date() && new Date(p.fecha_fin) >= new Date()
  )
})

const user = computed(() => usePage().props.auth.user)

const esFavorito = ref(false)

const formCarrito = useForm({})
const formFav = useForm({})

function agregarCarrito() {
  if (!user.value) { window.location.href = route('login'); return }
  formCarrito
    .transform(() => ({
      producto_id: props.producto.id,
      talla_id: tallaSeleccionada.value,
      cantidad: cantidad.value,
    }))
    .post(route('carrito.agregar'), { preserveScroll: true })
}

function toggleFavorito() {
  if (!user.value) { window.location.href = route('login'); return }
  formFav.post(route('favoritos.toggle', props.producto.id), { preserveScroll: true })
  esFavorito.value = !esFavorito.value
}
</script>

<style scoped>
.producto-detail { padding:2rem 0; }
.breadcrumb { display:flex; gap:0.5rem; align-items:center; font-size:0.85rem; color:var(--text-secondary); margin-bottom:2rem; }
.breadcrumb a { color:var(--color-accent); }
.detail-layout { display:grid; grid-template-columns:1fr 1fr; gap:3rem; margin-bottom:3rem; }
.main-image-wrap { border-radius:var(--radius); overflow:hidden; background:var(--bg-secondary); }
.main-image { width:100%; aspect-ratio:3/4; object-fit:cover; }
.thumb-gallery { display:flex; gap:0.5rem; margin-top:0.75rem; overflow-x:auto; }
.thumb { width:64px; height:80px; object-fit:cover; border-radius:var(--radius-sm); border:2px solid transparent; cursor:pointer; transition:border-color 0.2s; }
.thumb.active { border-color:var(--color-primary); }
.detail-nombre { font-size:1.75rem; margin-bottom:0.25rem; }
.detail-categoria { color:var(--text-secondary); font-size:0.9rem; text-transform:uppercase; letter-spacing:0.05em; margin-bottom:1rem; }
.detail-precio-wrap { display:flex; align-items:center; gap:0.75rem; margin-bottom:1.25rem; }
.detail-precio { font-size:1.75rem; font-weight:700; color:var(--color-accent); }
.detail-descuento { background:var(--color-danger); color:white; padding:0.2rem 0.6rem; border-radius:var(--radius-pill); font-weight:700; font-size:0.85rem; }
.detail-descripcion { color:var(--text-secondary); line-height:1.7; margin-bottom:1.5rem; }
.detail-label { font-weight:600; font-size:0.875rem; margin-bottom:0.5rem; }
.tallas-grid { display:flex; flex-wrap:wrap; gap:0.5rem; margin-bottom:1.25rem; }
.talla-btn { padding:0.4rem 0.75rem; border:2px solid var(--border-color); border-radius:var(--radius-sm); background:var(--bg-card); cursor:pointer; font-family:var(--font-body); font-weight:600; font-size:0.85rem; transition:all 0.2s; display:flex; flex-direction:column; align-items:center; gap:0.1rem; }
.talla-btn:hover { border-color:var(--color-primary); }
.talla-btn.active { border-color:var(--color-primary); background:var(--color-primary); color:var(--text-on-primary); }
.talla-btn.sin-stock { opacity:0.4; cursor:not-allowed; text-decoration:line-through; }
.talla-stock { font-size:0.65rem; color:var(--text-secondary); }
.talla-btn.active .talla-stock { color:rgba(255,255,255,0.7); }
.detail-cantidad { margin-bottom:1.5rem; }
.cantidad-ctrl { display:flex; align-items:center; gap:0.5rem; }
.cantidad-btn { width:36px; height:36px; border:1px solid var(--border-color); border-radius:var(--radius-sm); background:var(--bg-card); cursor:pointer; font-size:1.1rem; font-weight:700; display:flex; align-items:center; justify-content:center; transition:all 0.2s; }
.cantidad-btn:hover { background:var(--color-primary); color:var(--text-on-primary); }
.cantidad-val { font-size:1.1rem; font-weight:600; min-width:2rem; text-align:center; }
.detail-actions { display:flex; gap:0.75rem; margin-bottom:2rem; }
.btn-lg { padding:0.875rem 2rem; font-size:1rem; }
.btn-icon { padding:0.875rem; font-size:1.25rem; }
.detail-metricas { display:flex; gap:2rem; padding:1.25rem; background:var(--bg-secondary); border-radius:var(--radius); }
.metrica { display:flex; flex-direction:column; align-items:center; }
.metrica-valor { font-size:1.5rem; font-weight:700; color:var(--color-accent); }
.metrica-label { font-size:0.75rem; color:var(--text-secondary); text-transform:uppercase; }
.section-relacionados { margin-top:3rem; }
.section-title { font-size:1.5rem; margin-bottom:1.5rem; }
@media (max-width:768px) { .detail-layout { grid-template-columns:1fr; gap:1.5rem; } }
</style>
