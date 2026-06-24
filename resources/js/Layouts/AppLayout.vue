<template>
  <div class="app-wrapper">

    <!-- ── HEADER ──────────────────────────────────────────── -->
    <header class="app-header">
      <div class="container" style="display:flex; align-items:center; gap:1rem; width:100%; max-width:100%">

        <!-- Logo -->
        <Link :href="route('home')" class="header-logo">
          <span class="logo-icon">👗</span>
          <span class="logo-text">TiendaRopa</span>
        </Link>

        <!-- Buscador global (siempre visible) -->
        <div class="buscador-wrapper" ref="buscadorRef">
          <div class="buscador-input-wrap">
            <span class="buscador-icon">🔍</span>
            <input
              v-model="queryBusqueda"
              @input="onBuscar"
              @focus="mostrarResultados = true"
              type="text"
              placeholder="Buscar productos, categorías, acciones..."
              class="buscador-input"
              autocomplete="off"
            />
            <button v-if="queryBusqueda" @click="limpiarBusqueda" class="buscador-clear">✕</button>
          </div>

          <!-- Dropdown resultados -->
          <div v-if="mostrarResultados && (resultados.productos?.length || resultados.categorias?.length || resultados.acciones?.length)" class="buscador-dropdown slide-down">
            <div v-if="resultados.productos?.length">
              <p class="dropdown-label">Productos</p>
              <a v-for="p in resultados.productos" :key="p.id" :href="p.url" class="dropdown-item" @click="limpiarBusqueda">
                <img v-if="p.imagen" :src="p.imagen" :alt="p.nombre" class="dropdown-img" />
                <div>
                  <p class="dropdown-item-nombre">{{ p.nombre }}</p>
                  <p class="dropdown-item-precio">Bs. {{ p.precio }}</p>
                </div>
              </a>
            </div>
            <div v-if="resultados.categorias?.length">
              <p class="dropdown-label">Categorías</p>
              <a v-for="c in resultados.categorias" :key="c.id" :href="c.url" class="dropdown-item" @click="limpiarBusqueda">
                <span>📂</span> {{ c.nombre }}
              </a>
            </div>
            <div v-if="resultados.acciones?.length">
              <p class="dropdown-label">Acciones del sistema</p>
              <a v-for="a in resultados.acciones" :key="a.url" :href="a.url" class="dropdown-item" @click="limpiarBusqueda">
                <span>⚙️</span> {{ a.label }}
              </a>
            </div>
          </div>
        </div>

        <!-- Spacer -->
        <div style="flex:1" />

        <!-- Menú dinámico de navegación -->
        <nav class="header-nav">
          <template v-for="item in $page.props.menu" :key="item.id">
            <!-- Ítem con submenú -->
            <div v-if="item.hijos?.length" class="nav-dropdown-wrap">
              <button class="nav-link nav-dropdown-btn">
                {{ item.label }} <span>▾</span>
              </button>
              <div class="nav-dropdown">
                <Link v-for="hijo in item.hijos" :key="hijo.id"
                  :href="hijo.route ? route(hijo.route) : '#'"
                  class="nav-dropdown-item">
                  {{ hijo.label }}
                </Link>
              </div>
            </div>
            <!-- Ítem simple -->
            <Link v-else
              :href="item.route ? route(item.route) : '#'"
              class="nav-link">
              {{ item.label }}
            </Link>
          </template>
        </nav>

        <!-- Carrito (solo clientes auth) -->
        <Link v-if="$page.props.auth.user?.nivel >= 1" :href="route('carrito.index')" class="header-icon-btn" title="Mi carrito">
          🛒
          <span v-if="carritoCount > 0" class="badge">{{ carritoCount }}</span>
        </Link>

        <!-- Selector de tema + accesibilidad -->
        <div class="header-controles">
          <!-- Temas -->
          <div class="tema-selector">
            <button
              v-for="t in ['ninos','jovenes','adultos']" :key="t"
              :class="['tema-btn', `tema-btn-${t}`, { activo: tema === t }]"
              :title="t === 'ninos' ? 'Tema Niños' : t === 'jovenes' ? 'Tema Jóvenes' : 'Tema Adultos'"
              @click="setTema(t)"
            />
          </div>

          <!-- Modo día/noche -->
          <button class="header-icon-btn" @click="toggleModo" :title="`Modo ${modoActual === 'dia' ? 'noche' : 'día'}`">
            {{ modoActual === 'dia' ? '🌙' : '☀️' }}
          </button>

          <!-- Accesibilidad tamaño fuente -->
          <div class="accesibilidad-ctrl">
            <button class="font-btn" @click="escalaFuente(-0.1)" title="Reducir texto">A-</button>
            <button class="font-btn" @click="resetFuente" title="Tamaño normal">A</button>
            <button class="font-btn" @click="escalaFuente(0.1)" title="Ampliar texto">A+</button>
          </div>
        </div>

        <!-- Usuario -->
        <div class="header-user">
          <template v-if="$page.props.auth.user">
            <div class="user-dropdown-wrap">
              <button class="header-icon-btn user-btn">
                👤 {{ $page.props.auth.user.nombre }}
              </button>
              <div class="user-dropdown">
                <Link v-if="$page.props.auth.user.nivel >= 1" :href="route('pedidos.historial')" class="user-dropdown-item">Mis Pedidos</Link>
                <Link v-if="$page.props.auth.user.nivel >= 1" :href="route('favoritos.index')" class="user-dropdown-item">Favoritos</Link>
                <hr class="user-dropdown-divider" />
                <form @submit.prevent="logout" style="display:contents">
                  <button type="submit" class="user-dropdown-item user-logout">Cerrar Sesión</button>
                </form>
              </div>
            </div>
          </template>
          <template v-else>
            <Link :href="route('login')" class="btn btn-outline" style="padding:0.4rem 1rem; font-size:0.85rem">Ingresar</Link>
            <Link :href="route('registro')" class="btn btn-primary" style="padding:0.4rem 1rem; font-size:0.85rem">Registrarse</Link>
          </template>
        </div>
      </div>
    </header>

    <!-- ── FLASH MESSAGES ──────────────────────────────────── -->
    <div v-if="$page.props.flash.success || $page.props.flash.error || $page.props.flash.info" class="container" style="margin-top:1rem">
      <div v-if="$page.props.flash.success" class="alert alert-success fade-in">✅ {{ $page.props.flash.success }}</div>
      <div v-if="$page.props.flash.error"   class="alert alert-error fade-in">❌ {{ $page.props.flash.error }}</div>
      <div v-if="$page.props.flash.info"    class="alert alert-info fade-in">ℹ️ {{ $page.props.flash.info }}</div>
    </div>

    <!-- ── CONTENIDO PRINCIPAL ─────────────────────────────── -->
    <main class="page-content">
      <slot />
    </main>

    <!-- ── FOOTER ──────────────────────────────────────────── -->
    <footer class="app-footer">
      <div class="container" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:0.5rem">
        <span>© 2026 TiendaRopa — INF-513 Tecnología Web</span>
        <span class="visitas-counter">
          👁️ Esta página ha sido visitada
          <strong>{{ $page.props.pageVisits.toLocaleString() }}</strong>
          {{ $page.props.pageVisits === 1 ? 'vez' : 'veces' }}
        </span>
      </div>
    </footer>

  </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import { useTema } from '@/composables/useTema'
import axios from 'axios'

const { tema, modoActual, setTema, toggleModo, escalaFuente, resetFuente } = useTema()

// ── Buscador global ──────────────────────────────────────────
const queryBusqueda   = ref('')
const resultados      = ref({})
const mostrarResultados = ref(false)
const buscadorRef     = ref(null)
let debounceTimer     = null

async function onBuscar() {
  clearTimeout(debounceTimer)
  if (queryBusqueda.value.length < 2) { resultados.value = {}; return }
  debounceTimer = setTimeout(async () => {
    try {
      const { data } = await axios.get(route('buscar'), { params: { q: queryBusqueda.value } })
      resultados.value = data
    } catch {}
  }, 300)
}

function limpiarBusqueda() {
  queryBusqueda.value = ''
  resultados.value = {}
  mostrarResultados.value = false
}

// Cerrar dropdown al hacer clic fuera
function clickFuera(e) {
  if (buscadorRef.value && !buscadorRef.value.contains(e.target)) mostrarResultados.value = false
}
onMounted(() => document.addEventListener('click', clickFuera))
onBeforeUnmount(() => document.removeEventListener('click', clickFuera))

// ── Carrito badge ────────────────────────────────────────────
const carritoCount = ref(0)
// Se actualizará desde eventos

// ── Logout ───────────────────────────────────────────────────
function logout() { router.post(route('logout')) }
</script>

<style scoped>
/* Logo */
.header-logo { display:flex; align-items:center; gap:0.5rem; text-decoration:none; white-space:nowrap; }
.logo-icon { font-size:1.5rem; }
.logo-text { font-family:var(--font-heading); font-weight:700; font-size:1.1rem; color:var(--text-on-primary); }

/* Buscador */
.buscador-wrapper { position:relative; flex:1; max-width:460px; min-width:200px; }
.buscador-input-wrap { position:relative; display:flex; align-items:center; }
.buscador-icon { position:absolute; left:0.75rem; font-size:0.9rem; pointer-events:none; }
.buscador-input {
  width:100%; padding:0.5rem 2.5rem 0.5rem 2.25rem;
  background:rgba(255,255,255,0.15); border:1.5px solid rgba(255,255,255,0.3);
  border-radius:var(--radius-pill); color:white; font-family:var(--font-body); font-size:0.875rem;
  transition:all 0.2s;
}
.buscador-input::placeholder { color:rgba(255,255,255,0.6); }
.buscador-input:focus { outline:none; background:rgba(255,255,255,0.25); border-color:rgba(255,255,255,0.6); }
.buscador-clear { position:absolute; right:0.75rem; background:none; border:none; color:rgba(255,255,255,0.7); cursor:pointer; font-size:0.8rem; }
.buscador-dropdown {
  position:absolute; top:calc(100% + 0.5rem); left:0; right:0;
  background:var(--bg-card); border:1px solid var(--border-color); border-radius:var(--radius);
  box-shadow:var(--shadow); max-height:400px; overflow-y:auto; z-index:200;
}
.dropdown-label { padding:0.5rem 1rem; font-size:0.7rem; font-weight:700; text-transform:uppercase; color:var(--text-secondary); border-bottom:1px solid var(--border-color); }
.dropdown-item { display:flex; align-items:center; gap:0.75rem; padding:0.625rem 1rem; color:var(--text-primary); transition:background 0.15s; }
.dropdown-item:hover { background:var(--bg-secondary); }
.dropdown-img { width:36px; height:36px; object-fit:cover; border-radius:var(--radius-sm); }
.dropdown-item-nombre { font-size:0.875rem; font-weight:500; }
.dropdown-item-precio { font-size:0.75rem; color:var(--color-accent); }

/* Nav */
.header-nav { display:flex; align-items:center; gap:0.25rem; }
.nav-link { padding:0.4rem 0.75rem; border-radius:var(--radius-sm); color:rgba(255,255,255,0.85); font-size:0.875rem; font-weight:500; transition:all 0.2s; white-space:nowrap; background:none; border:none; cursor:pointer; font-family:var(--font-body); }
.nav-link:hover { color:white; background:rgba(255,255,255,0.15); }
.nav-dropdown-wrap { position:relative; }
.nav-dropdown-btn { display:flex; align-items:center; gap:0.25rem; }
.nav-dropdown { display:none; position:absolute; top:100%; left:0; background:var(--bg-card); border:1px solid var(--border-color); border-radius:var(--radius-sm); min-width:160px; box-shadow:var(--shadow); z-index:150; }
.nav-dropdown-wrap:hover .nav-dropdown { display:block; }
.nav-dropdown-item { display:block; padding:0.5rem 1rem; color:var(--text-primary); font-size:0.875rem; transition:background 0.15s; }
.nav-dropdown-item:hover { background:var(--bg-secondary); }

/* Header controles */
.header-controles { display:flex; align-items:center; gap:0.75rem; }
.header-icon-btn { position:relative; background:rgba(255,255,255,0.15); border:none; border-radius:var(--radius-sm); padding:0.4rem 0.6rem; color:white; cursor:pointer; font-size:1rem; display:flex; align-items:center; gap:0.25rem; transition:background 0.2s; }
.header-icon-btn:hover { background:rgba(255,255,255,0.25); }

/* User dropdown */
.header-user { display:flex; align-items:center; gap:0.5rem; }
.user-dropdown-wrap { position:relative; }
.user-btn { font-size:0.85rem; white-space:nowrap; }
.user-dropdown { display:none; position:absolute; right:0; top:100%; background:var(--bg-card); border:1px solid var(--border-color); border-radius:var(--radius-sm); min-width:160px; box-shadow:var(--shadow); z-index:150; }
.user-dropdown-wrap:hover .user-dropdown { display:block; }
.user-dropdown-item { display:block; width:100%; padding:0.5rem 1rem; color:var(--text-primary); font-size:0.875rem; text-align:left; background:none; border:none; cursor:pointer; font-family:var(--font-body); transition:background 0.15s; }
.user-dropdown-item:hover { background:var(--bg-secondary); }
.user-logout { color:var(--color-danger); }
.user-dropdown-divider { border:none; border-top:1px solid var(--border-color); margin:0.25rem 0; }

/* Visitas */
.visitas-counter { font-size:0.8rem; }

@media (max-width:768px) {
  .header-nav { display:none; }
  .buscador-wrapper { max-width:100%; }
  .logo-text { display:none; }
}
</style>
