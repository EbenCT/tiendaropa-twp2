import { ref, watch, onMounted } from 'vue'
import { usePage } from '@inertiajs/vue3'
import axios from 'axios'

const TEMAS = ['ninos', 'jovenes', 'adultos']
const HORA_DIA_INICIO   = 7   // 07:00
const HORA_NOCHE_INICIO = 19  // 19:00

// Carga preferencias desde servidor si el usuario está autenticado, o localStorage si es invitado
function cargarPrefs() {
    try {
        const user = usePage().props.auth?.user
        if (user?.pref_tema) {
            return {
                tema:  user.pref_tema   ?? 'adultos',
                modo:  user.pref_modo   ?? 'auto',
                scale: user.pref_escala ?? 1,
            }
        }
    } catch (_) { /* usePage puede no estar disponible fuera del setup */ }

    return {
        tema:  localStorage.getItem('tema')      ?? 'adultos',
        modo:  localStorage.getItem('modo')      ?? 'auto',
        scale: parseFloat(localStorage.getItem('fontScale') ?? '1'),
    }
}

export function useTema() {
    const prefs = cargarPrefs()

    const tema       = ref(prefs.tema)
    const modo       = ref(prefs.modo)
    const modoActual = ref('dia')
    const fontScale  = ref(prefs.scale)

    function getModoAutomatico() {
        const hora = new Date().getHours()
        return hora >= HORA_DIA_INICIO && hora < HORA_NOCHE_INICIO ? 'dia' : 'noche'
    }

    function aplicarClases() {
        const body = document.getElementById('app-body') ?? document.body
        TEMAS.forEach(t => body.classList.remove(`tema-${t}`))
        body.classList.remove('modo-dia', 'modo-noche')

        body.classList.add(`tema-${tema.value}`)

        const modoFinal = modo.value === 'auto' ? getModoAutomatico() : modo.value
        modoActual.value = modoFinal
        body.classList.add(`modo-${modoFinal}`)

        document.documentElement.style.setProperty('--font-scale', fontScale.value.toString())
    }

    function persistir() {
        localStorage.setItem('tema', tema.value)
        localStorage.setItem('modo', modo.value)
        localStorage.setItem('fontScale', fontScale.value.toString())

        // Sincroniza con la BD si el usuario está autenticado
        try {
            const user = usePage().props.auth?.user
            if (user) {
                axios.post('/preferencias', {
                    tema:   tema.value,
                    modo:   modo.value,
                    escala: fontScale.value,
                }).catch(() => { /* silencioso */ })
            }
        } catch (_) { /* fuera de contexto Inertia */ }
    }

    function setTema(nuevoTema) {
        if (!TEMAS.includes(nuevoTema)) return
        tema.value = nuevoTema
        persistir()
        aplicarClases()
    }

    function setModo(nuevoModo) {
        modo.value = nuevoModo
        persistir()
        aplicarClases()
    }

    function toggleModo() {
        const modoFinal = modo.value === 'auto' ? getModoAutomatico() : modo.value
        setModo(modoFinal === 'dia' ? 'noche' : 'dia')
    }

    function escalaFuente(delta) {
        const nuevo = Math.min(Math.max(fontScale.value + delta, 0.8), 1.4)
        fontScale.value = parseFloat(nuevo.toFixed(1))
        persistir()
        aplicarClases()
    }

    function resetFuente() {
        fontScale.value = 1
        persistir()
        aplicarClases()
    }

    let intervalo
    onMounted(() => {
        aplicarClases()
        intervalo = setInterval(() => {
            if (modo.value === 'auto') aplicarClases()
        }, 60_000)
    })

    watch([tema, modo, fontScale], aplicarClases)

    return { tema, modo, modoActual, fontScale, TEMAS, setTema, setModo, toggleModo, escalaFuente, resetFuente }
}
