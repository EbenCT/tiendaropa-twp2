<template>
  <AppLayout>
    <!-- Hero -->
    <section class="hero fade-in">
      <div class="container">
        <div class="hero-content">
          <h1 class="hero-title">Tu Estilo,<br/><span class="hero-accent">Tu Manera</span></h1>
          <p class="hero-subtitle">Descubre nuestra colección de ropa para hombre, mujer y niños</p>
          <div class="hero-ctas">
            <Link :href="route('catalogo')" class="btn btn-accent hero-cta">Ver Catálogo</Link>
            <Link v-if="!$page.props.auth.user" :href="route('registro')" class="btn btn-outline hero-cta" style="color:white; border-color:white">Crear Cuenta</Link>
          </div>
        </div>
      </div>
    </section>

    <div class="container">

      <!-- Productos Destacados -->
      <section v-if="destacados.length" class="section fade-in">
        <h2 class="section-title"><i class="fa-solid fa-star"></i> Productos Destacados</h2>
        <div class="productos-grid">
          <ProductoCard v-for="p in destacados" :key="p.id" :producto="p" />
        </div>
      </section>

      <!-- Nueva Colección -->
      <section v-if="nuevaColeccion.length" class="section fade-in">
        <h2 class="section-title"><i class="fa-solid fa-wand-magic-sparkles"></i> Nueva Colección</h2>
        <div class="productos-grid">
          <ProductoCard v-for="p in nuevaColeccion" :key="p.id" :producto="p" />
        </div>
      </section>

      <!-- Promociones -->
      <section v-if="promociones.length" class="section fade-in">
        <h2 class="section-title"><i class="fa-solid fa-tag"></i> Promociones Activas</h2>
        <div class="promociones-grid">
          <div v-for="promo in promociones" :key="promo.id" class="promo-card card">
            <div class="promo-header">
              <h3>{{ promo.nombre }}</h3>
              <span class="promo-badge">-{{ promo.descuento }}%</span>
            </div>
            <p class="promo-desc">{{ promo.descripcion }}</p>
            <div class="productos-grid" style="margin-top:1rem" v-if="promo.productos?.length">
              <ProductoCard v-for="p in promo.productos" :key="p.id" :producto="p" :small="true" />
            </div>
          </div>
        </div>
      </section>

      <!-- Estado vacío -->
      <section v-if="!destacados.length && !nuevaColeccion.length && !promociones.length" class="empty-home">
        <p><i class="fa-solid fa-bag-shopping"></i> Próximamente nuevos productos</p>
        <Link :href="route('catalogo')" class="btn btn-primary" style="margin-top:1rem">Explorar Catálogo</Link>
      </section>

    </div>
  </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import ProductoCard from '@/Components/ProductoCard.vue'
import { Link } from '@inertiajs/vue3'

defineProps({
  destacados:     { type: Array, default: () => [] },
  nuevaColeccion: { type: Array, default: () => [] },
  promociones:    { type: Array, default: () => [] },
})
</script>

<style scoped>
.hero {
  background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-secondary) 100%);
  padding: 5rem 0;
  margin-bottom: 3rem;
}
.hero-content { max-width: 600px; }
.hero-title { font-size: clamp(2.5rem, 6vw, 4rem); color: white; margin-bottom: 1rem; }
.hero-accent { color: var(--color-accent); }
.hero-subtitle { font-size: 1.15rem; color: rgba(255,255,255,0.85); margin-bottom: 2rem; }
.hero-ctas { display: flex; gap: 1rem; flex-wrap: wrap; }
.hero-cta { font-size: 1rem; padding: 0.75rem 2rem; }
.section { margin-bottom: 3rem; }
.section-title { font-size: 1.75rem; margin-bottom: 1.5rem; }
.promociones-grid { display: grid; gap: 1.5rem; }
.promo-card { }
.promo-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem; }
.promo-badge { background: var(--color-danger); color: white; padding: 0.25rem 0.75rem; border-radius: var(--radius-pill); font-weight: 700; font-size: 0.9rem; }
.promo-desc { color: var(--text-secondary); font-size: 0.9rem; }
.empty-home { text-align: center; padding: 4rem 0; color: var(--text-secondary); font-size: 1.1rem; }
</style>
