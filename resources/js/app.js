import { createApp, h } from 'vue'
import { createInertiaApp } from '@inertiajs/vue3'
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers'
import { ZiggyVue } from '../../vendor/tightenco/ziggy/dist/vue.m'

// Importar estilos globales
import '../css/app.css'

// Fallback global para imágenes rotas (las URLs heredadas del proyecto Java en
// `imagen_url`/`producto_imagen` no resuelven a un host real) — evita íconos de
// imagen rota en catálogo, home, carrito, favoritos, pedidos y admin.
const IMG_PLACEHOLDER = 'data:image/svg+xml;utf8,' + encodeURIComponent(
    '<svg xmlns="http://www.w3.org/2000/svg" width="300" height="400" viewBox="0 0 300 400">' +
    '<rect width="300" height="400" fill="#e2e2e2"/>' +
    '<text x="50%" y="50%" font-family="sans-serif" font-size="16" fill="#999" text-anchor="middle" dominant-baseline="middle">Sin imagen</text>' +
    '</svg>'
)
document.addEventListener('error', (event) => {
    const target = event.target
    if (target instanceof HTMLImageElement && target.src !== IMG_PLACEHOLDER) {
        target.src = IMG_PLACEHOLDER
    }
}, true)

createInertiaApp({
    title: (title) => title ? `${title} — Tienda de Ropa` : 'Tienda de Ropa',

    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue')
        ),

    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .mount(el)
    },

    progress: {
        color: '#6366f1',
        showSpinner: true,
    },
})
