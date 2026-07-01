<template>
  <div class="app-shell">

    <!-- ── TOPBAR ──────────────────────────────────────────── -->
    <header class="topbar">

      <!-- Hamburger / Collapse toggle -->
      <button class="topbar-btn menu-toggle" @click="onMenuToggle" :title="sidebarCollapsed ? 'Expandir menú' : 'Colapsar menú'">
        <i class="fa-solid fa-bars"></i>
      </button>

      <!-- Logo (mobile) -->
      <Link :href="route('home')" class="topbar-logo">
        <i class="fa-solid fa-shirt"></i>
        <span class="topbar-logo-text">TiendaRopa</span>
      </Link>

      <!-- Buscador global -->
      <div class="buscador-wrapper" ref="buscadorRef">
        <div class="buscador-input-wrap">
          <i class="fa-solid fa-magnifying-glass buscador-icon"></i>
          <input
            v-model="queryBusqueda"
            @input="onBuscar"
            @focus="mostrarResultados = true"
            type="text"
            placeholder="Buscar en el sistema..."
            class="buscador-input"
            autocomplete="off"
          />
          <button v-if="queryBusqueda" @click="limpiarBusqueda" class="buscador-clear">
            <i class="fa-solid fa-xmark"></i>
          </button>
        </div>

        <!-- Dropdown resultados -->
        <div v-if="mostrarResultados && (resultados.productos?.length || resultados.categorias?.length || resultados.usuarios?.length || resultados.acciones?.length)" class="buscador-dropdown slide-down">
          <div v-if="resultados.productos?.length">
            <p class="dropdown-label"><i class="fa-solid fa-shirt"></i> Productos</p>
            <Link v-for="p in resultados.productos" :key="p.id" :href="p.url" class="dropdown-item" @click="limpiarBusqueda">
              <img v-if="p.imagen" :src="p.imagen" :alt="p.nombre" class="dropdown-img" />
              <div>
                <p class="dropdown-item-nombre">{{ p.nombre }}</p>
                <p class="dropdown-item-meta">Bs. {{ p.precio }}</p>
              </div>
            </Link>
          </div>
          <div v-if="resultados.categorias?.length">
            <p class="dropdown-label"><i class="fa-solid fa-folder-open"></i> Categorías</p>
            <Link v-for="c in resultados.categorias" :key="c.id" :href="c.url" class="dropdown-item" @click="limpiarBusqueda">
              <i class="fa-solid fa-folder" style="width:20px; color:var(--color-accent)"></i>
              <p class="dropdown-item-nombre">{{ c.nombre }}</p>
            </Link>
          </div>
          <div v-if="resultados.usuarios?.length">
            <p class="dropdown-label"><i class="fa-solid fa-users"></i> Usuarios</p>
            <Link v-for="u in resultados.usuarios" :key="u.id" :href="u.url" class="dropdown-item" @click="limpiarBusqueda">
              <i class="fa-solid fa-circle-user" style="width:20px; font-size:1.1rem; color:var(--color-primary)"></i>
              <div>
                <p class="dropdown-item-nombre">{{ u.nombre }}</p>
                <p class="dropdown-item-meta">{{ u.rol }}</p>
              </div>
            </Link>
          </div>
          <div v-if="resultados.acciones?.length">
            <p class="dropdown-label"><i class="fa-solid fa-gear"></i> Acciones del sistema</p>
            <Link v-for="a in resultados.acciones" :key="a.url" :href="a.url" class="dropdown-item" @click="limpiarBusqueda">
              <i class="fa-solid fa-arrow-right" style="width:20px; color:var(--color-secondary)"></i>
              <p class="dropdown-item-nombre">{{ a.label }}</p>
            </Link>
          </div>
        </div>
      </div>

      <div style="flex:1" />

      <!-- Controles derechos -->
      <div class="topbar-controles">

        <!-- Carrito (solo clientes nivel=1) -->
        <Link
          v-if="$page.props.auth.user?.nivel === 1"
          :href="route('carrito.index')"
          class="topbar-btn"
          title="Mi carrito"
        >
          <i class="fa-solid fa-cart-shopping"></i>
          <span v-if="carritoCount > 0" class="badge">{{ carritoCount }}</span>
        </Link>

        <!-- Selector de tema -->
        <div class="tema-selector">
          <button
            v-for="t in ['ninos','jovenes','adultos']" :key="t"
            :class="['tema-btn', `tema-btn-${t}`, { activo: tema === t }]"
            :title="t === 'ninos' ? 'Tema Niños' : t === 'jovenes' ? 'Tema Jóvenes' : 'Tema Adultos'"
            @click="setTema(t)"
          />
        </div>

        <!-- Modo día/noche -->
        <button class="topbar-btn" @click="toggleModo" :title="modoActual === 'dia' ? 'Modo noche' : 'Modo día'">
          <i :class="modoActual === 'dia' ? 'fa-solid fa-moon' : 'fa-solid fa-sun'"></i>
        </button>

        <!-- Accesibilidad tamaño fuente -->
        <div class="accesibilidad-ctrl">
          <button class="font-btn" @click="escalaFuente(-0.1)" title="Reducir texto">A-</button>
          <button class="font-btn" @click="resetFuente" title="Tamaño normal">A</button>
          <button class="font-btn" @click="escalaFuente(0.1)" title="Ampliar texto">A+</button>
        </div>

        <!-- Usuario -->
        <div v-if="$page.props.auth.user" class="user-dropdown-wrap">
          <button class="topbar-btn user-btn">
            <i class="fa-solid fa-circle-user"></i>
            <span class="user-name">{{ $page.props.auth.user.nombre }}</span>
            <i class="fa-solid fa-chevron-down" style="font-size:0.65rem;opacity:0.7"></i>
          </button>
          <div class="user-dropdown">
            <div class="user-dropdown-header">
              <span class="user-dropdown-name">{{ $page.props.auth.user.nombre }} {{ $page.props.auth.user.apellido }}</span>
              <span class="user-dropdown-rol">{{ $page.props.auth.user.rol }}</span>
            </div>
            <hr class="user-dropdown-divider" />
            <Link v-if="$page.props.auth.user.nivel === 1" :href="route('pedidos.historial')" class="user-dropdown-item">
              <i class="fa-solid fa-clock-rotate-left"></i> Mis Pedidos
            </Link>
            <Link v-if="$page.props.auth.user.nivel === 1" :href="route('favoritos.index')" class="user-dropdown-item">
              <i class="fa-solid fa-heart"></i> Favoritos
            </Link>
            <hr class="user-dropdown-divider" />
            <form @submit.prevent="logout" style="display:contents">
              <button type="submit" class="user-dropdown-item user-logout">
                <i class="fa-solid fa-right-from-bracket"></i> Cerrar Sesión
              </button>
            </form>
          </div>
        </div>
        <template v-else>
          <Link :href="route('login')" class="btn btn-outline topbar-auth-btn">Ingresar</Link>
          <Link :href="route('registro')" class="btn btn-primary topbar-auth-btn">Registrarse</Link>
        </template>

      </div>
    </header>

    <!-- ── BODY = SIDEBAR + MAIN ──────────────────────────── -->
    <div class="app-body">

      <!-- Overlay mobile -->
      <div v-if="sidebarOpen" class="sidebar-overlay" @click="sidebarOpen = false"></div>

      <!-- SIDEBAR -->
      <aside :class="['app-sidebar', { 'sidebar-open': sidebarOpen, 'sidebar-collapsed': sidebarCollapsed }]">

        <!-- Logo en sidebar -->
        <Link :href="route('home')" class="sidebar-logo" @click="sidebarOpen = false">
          <i class="fa-solid fa-shirt sidebar-logo-icon"></i>
          <span class="sidebar-logo-text">TiendaRopa</span>
        </Link>

        <div class="sidebar-divider"></div>

        <!-- Navegación -->
        <nav class="sidebar-nav">
          <template v-for="item in $page.props.menu" :key="item.id">

            <!-- Ítem con submenú -->
            <div v-if="item.hijos?.length" class="sidebar-group">
              <button
                :class="['sidebar-group-btn', { expanded: openGroups.includes(item.id) }]"
                @click="toggleGroup(item.id)"
                :title="sidebarCollapsed ? item.label : undefined"
              >
                <i :class="['sidebar-icon', getIcon(item.route, item.label)]"></i>
                <span class="sidebar-text">{{ item.label }}</span>
                <i class="fa-solid fa-chevron-right sidebar-chevron"></i>
              </button>
              <div v-show="openGroups.includes(item.id) && !sidebarCollapsed" class="sidebar-children">
                <Link
                  v-for="hijo in item.hijos" :key="hijo.id"
                  :href="hijo.route ? route(hijo.route) : '#'"
                  :class="['sidebar-link', 'sidebar-child', { active: isActive(hijo.route) }]"
                  @click="sidebarOpen = false"
                  :title="hijo.label"
                >
                  <i :class="['sidebar-icon', getIcon(hijo.route, hijo.label)]"></i>
                  <span class="sidebar-text">{{ hijo.label }}</span>
                </Link>
              </div>
            </div>

            <!-- Ítem simple -->
            <Link
              v-else
              :href="item.route ? route(item.route) : '#'"
              :class="['sidebar-link', { active: isActive(item.route) }]"
              @click="sidebarOpen = false"
              :title="item.label"
            >
              <i :class="['sidebar-icon', getIcon(item.route, item.label)]"></i>
              <span class="sidebar-text">{{ item.label }}</span>
            </Link>

          </template>
        </nav>

        <div style="flex:1"></div>
        <div class="sidebar-divider"></div>

        <!-- Footer del sidebar -->
        <div class="sidebar-footer">
          <template v-if="$page.props.auth.user">
            <div class="sidebar-user-info" :title="sidebarCollapsed ? ($page.props.auth.user.nombre + ' — ' + $page.props.auth.user.rol) : undefined">
              <i class="fa-solid fa-circle-user sidebar-icon"></i>
              <div class="sidebar-text">
                <p class="sidebar-user-name">{{ $page.props.auth.user.nombre }}</p>
                <p class="sidebar-user-rol">{{ $page.props.auth.user.rol }}</p>
              </div>
            </div>
            <form @submit.prevent="logout">
              <button type="submit" class="sidebar-link sidebar-logout" title="Cerrar Sesión">
                <i class="fa-solid fa-right-from-bracket sidebar-icon"></i>
                <span class="sidebar-text">Cerrar Sesión</span>
              </button>
            </form>
          </template>
          <template v-else>
            <Link :href="route('login')" class="sidebar-link" @click="sidebarOpen = false" title="Ingresar">
              <i class="fa-solid fa-right-to-bracket sidebar-icon"></i>
              <span class="sidebar-text">Ingresar</span>
            </Link>
            <Link :href="route('registro')" class="sidebar-link" @click="sidebarOpen = false" title="Registrarse">
              <i class="fa-solid fa-user-plus sidebar-icon"></i>
              <span class="sidebar-text">Registrarse</span>
            </Link>
          </template>
        </div>

      </aside>

      <!-- ── CONTENIDO PRINCIPAL ──────────────────────────── -->
      <div class="app-main">

        <!-- Flash messages -->
        <div v-if="$page.props.flash.success || $page.props.flash.error || $page.props.flash.info" class="flash-container">
          <div v-if="$page.props.flash.success" class="alert alert-success fade-in">
            <i class="fa-solid fa-circle-check"></i> {{ $page.props.flash.success }}
          </div>
          <div v-if="$page.props.flash.error" class="alert alert-error fade-in">
            <i class="fa-solid fa-circle-xmark"></i> {{ $page.props.flash.error }}
          </div>
          <div v-if="$page.props.flash.info" class="alert alert-info fade-in">
            <i class="fa-solid fa-circle-info"></i> {{ $page.props.flash.info }}
          </div>
        </div>

        <main class="page-content">
          <slot />
        </main>

        <!-- Footer -->
        <footer class="app-footer">
          <span>© 2026 TiendaRopa — INF-513 Tecnología Web</span>
          <span class="visitas-counter">
            <i class="fa-solid fa-eye"></i>
            <strong>{{ $page.props.pageVisits.toLocaleString() }}</strong>
            {{ $page.props.pageVisits === 1 ? 'visita' : 'visitas' }}
          </span>
        </footer>

      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
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

function clickFuera(e) {
  if (buscadorRef.value && !buscadorRef.value.contains(e.target)) mostrarResultados.value = false
}
onMounted(() => document.addEventListener('click', clickFuera))
onBeforeUnmount(() => document.removeEventListener('click', clickFuera))

// ── Carrito badge ────────────────────────────────────────────
const carritoCount = computed(() => usePage().props.carritoCount ?? 0)

// ── Sidebar ──────────────────────────────────────────────────
const sidebarOpen      = ref(false)
const sidebarCollapsed = ref(localStorage.getItem('sidebarCollapsed') === 'true')
const openGroups       = ref([])

function onMenuToggle() {
  if (window.innerWidth >= 900) {
    sidebarCollapsed.value = !sidebarCollapsed.value
    localStorage.setItem('sidebarCollapsed', sidebarCollapsed.value)
  } else {
    sidebarOpen.value = !sidebarOpen.value
  }
}

function toggleGroup(id) {
  const idx = openGroups.value.indexOf(id)
  if (idx === -1) openGroups.value.push(id)
  else openGroups.value.splice(idx, 1)
}

function isActive(routeName) {
  if (!routeName) return false
  try { return route().current(routeName) } catch { return false }
}

// Auto-expandir el grupo que contiene la ruta activa
onMounted(() => {
  usePage().props.menu?.forEach(item => {
    if (item.hijos?.some(h => isActive(h.route))) {
      if (!openGroups.value.includes(item.id)) openGroups.value.push(item.id)
    }
  })
})

// ── Iconos ───────────────────────────────────────────────────
const iconMap = {
  'home':                    'fa-solid fa-house',
  'catalogo':                'fa-solid fa-shirt',
  'catalogo.index':          'fa-solid fa-shirt',
  'promociones':             'fa-solid fa-tag',
  'carrito.index':           'fa-solid fa-cart-shopping',
  'favoritos.index':         'fa-solid fa-heart',
  'pedidos.historial':       'fa-solid fa-clock-rotate-left',
  'admin.productos.index':   'fa-solid fa-box',
  'admin.inventario.index':  'fa-solid fa-warehouse',
  'admin.pedidos.index':     'fa-solid fa-clipboard-list',
  'admin.usuarios.index':    'fa-solid fa-users',
  'admin.reportes':          'fa-solid fa-chart-bar',
  'admin.estadisticas':      'fa-solid fa-chart-line',
  'admin.menu.index':        'fa-solid fa-sliders',
}

const labelIconMap = {
  'Gestión':   'fa-solid fa-layer-group',
  'Sistema':   'fa-solid fa-gear',
  'Inicio':    'fa-solid fa-house',
  'Catálogo':  'fa-solid fa-shirt',
}

function getIcon(routeName, label) {
  return iconMap[routeName] || labelIconMap[label] || 'fa-solid fa-circle-dot'
}

// ── Logout ───────────────────────────────────────────────────
function logout() { router.post(route('logout')) }
</script>

<style scoped>
/* ── Shell ──────────────────────────────────────────────── */
.app-shell { display: flex; flex-direction: column; height: 100vh; overflow: hidden; }

/* ── Topbar ─────────────────────────────────────────────── */
.topbar {
  height: 60px;
  background: var(--bg-header);
  color: var(--text-on-primary);
  display: flex; align-items: center; gap: 0.75rem;
  padding: 0 1.25rem;
  position: sticky; top: 0; z-index: 200;
  box-shadow: var(--shadow);
  flex-shrink: 0;
}

.menu-toggle { display: flex; }

.topbar-logo {
  display: flex; align-items: center; gap: 0.5rem;
  text-decoration: none; color: var(--text-on-primary); white-space: nowrap;
  font-family: var(--font-heading); font-weight: 700; font-size: 1.05rem;
}
.topbar-logo-text { display: none; }

/* Buscador */
.buscador-wrapper { position: relative; flex: 1; max-width: 480px; min-width: 180px; }
.buscador-input-wrap { position: relative; display: flex; align-items: center; }
.buscador-icon { position: absolute; left: 0.75rem; font-size: 0.85rem; pointer-events: none; opacity: 0.7; }
.buscador-input {
  width: 100%; padding: 0.45rem 2.25rem 0.45rem 2.1rem;
  background: rgba(255,255,255,0.12); border: 1.5px solid rgba(255,255,255,0.2);
  border-radius: var(--radius-pill); color: white; font-family: var(--font-body); font-size: 0.875rem;
  transition: all 0.2s;
}
.buscador-input::placeholder { color: rgba(255,255,255,0.55); }
.buscador-input:focus { outline: none; background: rgba(255,255,255,0.22); border-color: rgba(255,255,255,0.5); }
.buscador-clear { position: absolute; right: 0.75rem; background: none; border: none; color: rgba(255,255,255,0.6); cursor: pointer; font-size: 0.8rem; }
.buscador-dropdown {
  position: absolute; top: calc(100% + 0.5rem); left: 0; right: 0;
  background: var(--bg-card); border: 1px solid var(--border-color);
  border-radius: var(--radius); box-shadow: var(--shadow); max-height: 380px; overflow-y: auto; z-index: 300;
}
.dropdown-label { padding: 0.5rem 1rem; font-size: 0.7rem; font-weight: 700; text-transform: uppercase; color: var(--text-secondary); border-bottom: 1px solid var(--border-color); }
.dropdown-item { display: flex; align-items: center; gap: 0.75rem; padding: 0.6rem 1rem; color: var(--text-primary); transition: background 0.15s; }
.dropdown-item:hover { background: var(--bg-secondary); }
.dropdown-img { width: 36px; height: 36px; object-fit: cover; border-radius: var(--radius-sm); }
.dropdown-item-nombre { font-size: 0.875rem; font-weight: 500; }
.dropdown-item-meta { font-size: 0.75rem; color: var(--color-accent); }
.dropdown-label { display: flex; align-items: center; gap: 0.4rem; }

/* Controles topbar */
.topbar-controles { display: flex; align-items: center; gap: 0.5rem; }
.topbar-btn {
  position: relative; background: rgba(255,255,255,0.12); border: none;
  border-radius: var(--radius-sm); padding: 0.4rem 0.65rem;
  color: white; cursor: pointer; font-size: 0.95rem;
  display: flex; align-items: center; gap: 0.35rem; transition: background 0.2s;
  text-decoration: none;
}
.topbar-btn:hover { background: rgba(255,255,255,0.22); }
.topbar-auth-btn { padding: 0.35rem 0.875rem; font-size: 0.83rem; }
/* Botones de auth en topbar: siempre visibles sobre el fondo del header */
.topbar .btn-outline {
  border-color: rgba(255,255,255,0.5);
  color: white;
}
.topbar .btn-outline:hover {
  background: rgba(255,255,255,0.2);
  color: white;
}

/* User dropdown */
.user-name { font-size: 0.83rem; max-width: 100px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.user-dropdown-wrap { position: relative; }
.user-dropdown {
  display: none; position: absolute; right: 0; top: calc(100% + 0.5rem);
  background: var(--bg-card); border: 1px solid var(--border-color);
  border-radius: var(--radius); min-width: 200px; box-shadow: var(--shadow); z-index: 250;
}
.user-dropdown-wrap:hover .user-dropdown { display: block; }
.user-dropdown-header { padding: 0.75rem 1rem 0.5rem; }
.user-dropdown-name { font-weight: 600; font-size: 0.875rem; color: var(--text-primary); }
.user-dropdown-rol { font-size: 0.73rem; color: var(--text-secondary); text-transform: capitalize; }
.user-dropdown-item {
  display: flex; align-items: center; gap: 0.625rem;
  width: 100%; padding: 0.55rem 1rem; color: var(--text-primary);
  font-size: 0.875rem; text-align: left; background: none; border: none;
  cursor: pointer; font-family: var(--font-body); transition: background 0.15s;
}
.user-dropdown-item:hover { background: var(--bg-secondary); }
.user-logout { color: var(--color-danger); }
.user-dropdown-divider { border: none; border-top: 1px solid var(--border-color); margin: 0.2rem 0; }

/* Temas */
.tema-selector { display: flex; gap: 0.35rem; }
.tema-btn { width: 22px; height: 22px; border-radius: 50%; border: 2px solid transparent; cursor: pointer; transition: border-color 0.2s, transform 0.2s; }
.tema-btn:hover, .tema-btn.activo { border-color: white; transform: scale(1.2); }
.tema-btn-ninos   { background: linear-gradient(135deg, #FF6B6B, #4ECDC4); }
.tema-btn-jovenes { background: linear-gradient(135deg, #7C3AED, #06B6D4); }
.tema-btn-adultos { background: linear-gradient(135deg, #1C1C1C, #C9A84C); }

/* Accesibilidad */
.accesibilidad-ctrl { display: flex; gap: 0.2rem; }
.font-btn {
  padding: 0.2rem 0.45rem; border-radius: var(--radius-sm);
  border: 1px solid rgba(255,255,255,0.25); background: rgba(255,255,255,0.1);
  color: white; cursor: pointer; font-family: var(--font-body); font-weight: 600; font-size: 0.78rem;
  transition: background 0.2s;
}
.font-btn:hover { background: rgba(255,255,255,0.25); }

/* ── Body + Sidebar + Main ─────────────────────────────── */
.app-body { display: flex; height: calc(100vh - 60px); overflow: hidden; }

.sidebar-overlay { display: none; }

.app-sidebar {
  width: 240px; flex-shrink: 0;
  background: var(--bg-card); border-right: 1px solid var(--border-color);
  display: flex; flex-direction: column;
  height: 100%; overflow-y: auto;
  transition: width 0.28s ease;
}

/* Collapsed: icon-only mode */
.app-sidebar.sidebar-collapsed { width: 60px; overflow-x: hidden; }
.app-sidebar.sidebar-collapsed .sidebar-logo { justify-content: center; padding: 1rem 0; }
.app-sidebar.sidebar-collapsed .sidebar-text { display: none; }
.app-sidebar.sidebar-collapsed .sidebar-chevron { display: none; }
.app-sidebar.sidebar-collapsed .sidebar-link,
.app-sidebar.sidebar-collapsed .sidebar-group-btn {
  justify-content: center; padding: 0.7rem 0;
  border-left: none;
}
.app-sidebar.sidebar-collapsed .sidebar-link.active {
  background: color-mix(in srgb, var(--color-primary) 15%, var(--bg-card));
  border-left: none; border-radius: 0;
}
.app-sidebar.sidebar-collapsed .sidebar-icon { width: auto; font-size: 1rem; }
.app-sidebar.sidebar-collapsed .sidebar-user-info { justify-content: center; padding: 0.75rem 0; }
.app-sidebar.sidebar-collapsed .sidebar-children { display: none; }

/* Logo sidebar */
.sidebar-logo {
  display: flex; align-items: center; gap: 0.625rem;
  padding: 1.1rem 1.25rem 0.9rem;
  text-decoration: none; color: var(--text-primary);
  font-family: var(--font-heading); font-weight: 700; font-size: 1rem;
}
.sidebar-logo-icon { color: var(--color-primary); font-size: 1.25rem; }
.sidebar-logo-text { color: var(--text-primary); }

.sidebar-divider { border: none; border-top: 1px solid var(--border-color); margin: 0; }

/* Nav sidebar */
.sidebar-nav { display: flex; flex-direction: column; padding: 0.5rem 0; }

.sidebar-link {
  display: flex; align-items: center; gap: 0.75rem;
  padding: 0.625rem 1.25rem; color: var(--text-secondary);
  font-size: 0.875rem; font-weight: 500; text-decoration: none;
  border: none; background: none; cursor: pointer; font-family: var(--font-body);
  transition: background 0.15s, color 0.15s;
  border-left: 3px solid transparent;
}
.sidebar-link:hover { background: var(--bg-secondary); color: var(--text-primary); }
.sidebar-link.active {
  background: color-mix(in srgb, var(--color-primary) 12%, var(--bg-card));
  color: var(--color-primary); border-left-color: var(--color-primary);
  font-weight: 600;
}

/* Sidebar group (parent with children) */
.sidebar-group-btn {
  display: flex; align-items: center; gap: 0.75rem;
  width: 100%; padding: 0.625rem 1.25rem;
  color: var(--text-secondary); font-size: 0.875rem; font-weight: 500;
  background: none; border: none; cursor: pointer; font-family: var(--font-body);
  transition: background 0.15s, color 0.15s;
  border-left: 3px solid transparent;
}
.sidebar-group-btn:hover { background: var(--bg-secondary); color: var(--text-primary); }
.sidebar-group-btn span { flex: 1; text-align: left; }
.sidebar-chevron { font-size: 0.7rem; transition: transform 0.25s; opacity: 0.6; }
.sidebar-group-btn.expanded .sidebar-chevron { transform: rotate(90deg); }

.sidebar-children { background: color-mix(in srgb, var(--bg-secondary) 50%, var(--bg-card)); }
.sidebar-child { padding-left: 2.5rem; font-size: 0.85rem; }

.sidebar-icon { width: 16px; text-align: center; font-size: 0.875rem; opacity: 0.8; flex-shrink: 0; }
.sidebar-link.active .sidebar-icon { opacity: 1; }

/* User info en sidebar footer */
.sidebar-footer { padding: 0.75rem 0; }
.sidebar-user-info { display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem 1.25rem; }
.sidebar-user-info .sidebar-icon { font-size: 1.5rem; width: 28px; color: var(--color-primary); }
.sidebar-user-name { font-size: 0.85rem; font-weight: 600; color: var(--text-primary); }
.sidebar-user-rol { font-size: 0.72rem; color: var(--text-secondary); text-transform: capitalize; }
.sidebar-logout { color: var(--color-danger) !important; }
.sidebar-logout:hover { background: color-mix(in srgb, var(--color-danger) 10%, var(--bg-card)) !important; }

/* ── Main content area ──────────────────────────────────── */
.app-main { flex: 1; display: flex; flex-direction: column; overflow: hidden; min-width: 0; }

.flash-container { padding: 0.75rem 1.5rem 0; flex-shrink: 0; }

.page-content { flex: 1; padding: 1.5rem; overflow-y: auto; }

/* Footer */
.app-footer {
  background: var(--bg-secondary); border-top: 1px solid var(--border-color);
  padding: 0.75rem 1.5rem; display: flex; justify-content: space-between;
  align-items: center; flex-wrap: wrap; gap: 0.5rem;
  color: var(--text-secondary); font-size: 0.78rem;
}
.visitas-counter { display: flex; align-items: center; gap: 0.4rem; }

/* ── Badge carrito ──────────────────────────────────────── */
.badge {
  position: absolute; top: -4px; right: -4px;
  min-width: 16px; height: 16px; padding: 0 0.25rem;
  border-radius: var(--radius-pill); background: var(--color-accent);
  color: var(--color-primary); font-size: 0.65rem; font-weight: 700;
  display: flex; align-items: center; justify-content: center;
}

/* ── Responsive ─────────────────────────────────────────── */
@media (max-width: 900px) {
  .topbar-logo-text { display: inline; }

  .app-sidebar {
    position: fixed; left: 0; top: 60px;
    height: calc(100vh - 60px); z-index: 190;
    transform: translateX(-100%);
    width: 240px !important; /* nunca colapsar en mobile */
  }
  .app-sidebar.sidebar-open { transform: translateX(0); box-shadow: 4px 0 20px rgba(0,0,0,0.25); }
  /* Restaurar elementos ocultos por collapsed en desktop */
  .app-sidebar .sidebar-text { display: inline !important; }
  .app-sidebar .sidebar-chevron { display: inline !important; }
  .app-sidebar .sidebar-link,
  .app-sidebar .sidebar-group-btn { justify-content: flex-start !important; padding: 0.625rem 1.25rem !important; border-left: 3px solid transparent !important; }
  .app-sidebar .sidebar-icon { width: 16px !important; }

  .sidebar-overlay {
    display: block; position: fixed; inset: 0; top: 60px;
    background: rgba(0,0,0,0.45); z-index: 185;
  }

  .user-name { display: none; }
  .accesibilidad-ctrl { display: none; }
}

@media (max-width: 600px) {
  .tema-selector { display: none; }
  .topbar-auth-btn { padding: 0.3rem 0.6rem; font-size: 0.78rem; }
}
</style>
