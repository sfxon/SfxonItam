import { ref } from 'vue'

const DEFAULT_HIDE_DELAY = 3500 // ms

export function useSaveBadge(hideDelay: number = DEFAULT_HIDE_DELAY) {
    const visible = ref(false)
    const isReflash = ref(false)
    const flashKey = ref(0) // Changes with each trigger; re-triggers the CSS animation.

    let hideTimer: ReturnType<typeof setTimeout> | null = null

    function trigger() {
        if (visible.value) {
            // Retrigger: with emphasized highlight animation.
            isReflash.value = true
            flashKey.value++
            setTimeout(() => { isReflash.value = false }, 600)
        } else {
            visible.value = true
            isReflash.value = false
            flashKey.value++
        }

        if (hideTimer) {
            clearTimeout(hideTimer)
        }

        hideTimer = setTimeout(() => {
            visible.value = false
        }, hideDelay)
    }

    function reset() {
        if (hideTimer) {
            clearTimeout(hideTimer)
        }

        hideTimer = null
        visible.value = false
        isReflash.value = false
    }

    return { visible, isReflash, flashKey, trigger, reset }
}
