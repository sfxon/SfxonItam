import { ref, computed } from 'vue'
import { loadState } from '@nextcloud/initial-state'

export function useColumnOrder(listId, allColumns, defaultColumns) {
    const savedOrder = ref(loadState('sfxonitam', `listViewColumnOrder-${listId}`, null))
    const showModal = ref(false)

    const orderedColumns = computed(() => {
        if (!savedOrder.value) {
            return defaultColumns
                .map(key => allColumns.find(c => c.key === key))
                .filter(Boolean)
        }

        return savedOrder.value
            .map(key => allColumns.find(c => c.key === key))
            .filter(Boolean)
    })

    function onSaved(columns) {
        savedOrder.value = columns.map(c => c.key)
    }

    return { orderedColumns, showModal, onSaved }
}