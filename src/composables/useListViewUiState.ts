import { onMounted, onUnmounted, ref, watch } from 'vue'
import { emit as emitEventBus, subscribe as subscribeEventBus, unsubscribe as unsubscribeEventBus } from '@nextcloud/event-bus'
import { loadState } from '@nextcloud/initial-state'
import { saveUiState } from '@/services/ListViewSettings'

interface ListViewUiState {
    filterSidebarOpen?: boolean
    navigationOpen?: boolean
}

export function useListViewUiState(viewId: string) {
    const initial = loadState<ListViewUiState>(
        'sfxonitam',
        'listViewUiState-' + viewId,
        { filterSidebarOpen: true, navigationOpen: true },
    )

    const filterSidebarOpen = ref(initial.filterSidebarOpen ?? true)
    const navigationOpen = ref(initial.navigationOpen ?? true)

    watch([filterSidebarOpen, navigationOpen], () => {
        saveUiState(viewId, {
            filterSidebarOpen: filterSidebarOpen.value,
            navigationOpen: navigationOpen.value,
        }).catch((e) => console.warn('Could not save UI state:', e))
    })

    function onNavigationToggled({ open }: { open: boolean }) {
        navigationOpen.value = open
    }

    onMounted(() => {
        subscribeEventBus('navigation-toggled', onNavigationToggled)
        emitEventBus('toggle-navigation', { open: navigationOpen.value })
    })

    onUnmounted(() => {
        unsubscribeEventBus('navigation-toggled', onNavigationToggled)
    })

    return { filterSidebarOpen, navigationOpen }
}
