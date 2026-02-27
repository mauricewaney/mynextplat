import { ref, onBeforeUnmount } from 'vue'

export function useTooltip(autoHideMs = 2000) {
    const active = ref(null)
    let timer = null

    function clearTimer() {
        if (timer) {
            clearTimeout(timer)
            timer = null
        }
    }

    function startTimer() {
        clearTimer()
        timer = setTimeout(() => { active.value = null }, autoHideMs)
    }

    function toggle(id) {
        if (active.value === id) {
            active.value = null
            clearTimer()
        } else {
            active.value = id
            startTimer()
        }
    }

    function show(id) {
        active.value = id
        startTimer()
    }

    function hide() {
        active.value = null
        clearTimer()
    }

    function isVisible(id) {
        return active.value === id
    }

    onBeforeUnmount(clearTimer)

    return { active, toggle, show, hide, isVisible }
}
