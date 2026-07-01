import { ref, watch, onMounted } from 'vue'

const TEMAS = ['ninos', 'jovenes', 'adultos']
const HORA_DIA_INICIO   = 7   // 07:00
const HORA_NOCHE_INICIO = 19  // 19:00

export function useTema() {
    const tema       = ref(localStorage.getItem('tema') ?? 'adultos')
    const modo       = ref(localStorage.getItem('modo') ?? 'auto') // 'auto' | 'dia' | 'noche'
    const modoActual = ref('dia')
    const fontScale  = ref(parseFloat(localStorage.getItem('fontScale') ?? '1'))

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

    function setTema(nuevoTema) {
        if (!TEMAS.includes(nuevoTema)) return
        tema.value = nuevoTema
        localStorage.setItem('tema', nuevoTema)
        aplicarClases()
    }

    function setModo(nuevoModo) {
        modo.value = nuevoModo
        localStorage.setItem('modo', nuevoModo)
        aplicarClases()
    }

    function toggleModo() {
        const modoFinal = modo.value === 'auto' ? getModoAutomatico() : modo.value
        setModo(modoFinal === 'dia' ? 'noche' : 'dia')
    }

    function escalaFuente(delta) {
        const nuevo = Math.min(Math.max(fontScale.value + delta, 0.8), 1.4)
        fontScale.value = parseFloat(nuevo.toFixed(1))
        localStorage.setItem('fontScale', fontScale.value.toString())
        aplicarClases()
    }

    function resetFuente() {
        fontScale.value = 1
        localStorage.setItem('fontScale', '1')
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
