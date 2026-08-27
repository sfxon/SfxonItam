import { ref } from 'vue'

export type SfxonEntitySearchFn = (query: string, signal: AbortSignal) => Promise<void>

export function useSfxonEntitySearch(
    searchFn: SfxonEntitySearchFn | undefined,
    options: { debounceMs?: number; preloadDelayMs?: number } = {},
) {
    const debounceMs = options.debounceMs ?? 300
    const preloadDelayMs = options.preloadDelayMs ?? 150
    const isSearching = ref(false)
    const preloaded = ref(false)
    const hoverTimeout = ref<ReturnType<typeof setTimeout> | null>(null)
    const searchTimeout = ref<ReturnType<typeof setTimeout> | null>(null)
    const abortController = ref<AbortController | null>(null)

    function triggerPreload() {
        if (preloaded.value || !searchFn) {
            return
        }

        preloaded.value = true
        const ctrl = new AbortController()
        searchFn('', ctrl.signal)
    }

    function onMouseEnter() {
        hoverTimeout.value = setTimeout(triggerPreload, preloadDelayMs)
    }

    function onMouseLeave() {
        if (hoverTimeout.value) {
            clearTimeout(hoverTimeout.value)
        }
    }

    async function onSearch(query: string) {
        if (searchTimeout.value) {
            clearTimeout(searchTimeout.value)
        }

        if (abortController.value) {
            abortController.value.abort()
        }

        if (query === null || query.trim() === '') {
            return
        }

        searchTimeout.value = setTimeout(async () => {
            if (!searchFn) {
                return
            }

            abortController.value = new AbortController()
            isSearching.value = true

            try {
                await searchFn(query, abortController.value.signal)
            } finally {
                isSearching.value = false
            }
        }, debounceMs)
    }

    return { isSearching, onSearch, onMouseEnter, onMouseLeave, triggerPreload }
}
