import { ref, computed, toValue } from 'vue'
import type { MaybeRefOrGetter } from 'vue'
import { loadState } from '@nextcloud/initial-state'

interface ColumnLike {
    key: string
}

export function useColumnOrder<C extends ColumnLike>(
    listId: string,
    allColumns: MaybeRefOrGetter<C[]>,
    defaultColumns: MaybeRefOrGetter<string[]>,
) {
    const savedOrder = ref<string[] | null>(loadState('sfxonitam', `listViewColumnOrder-${listId}`, null))
    const showModal = ref(false)

    function resolve(keys: string[]): C[] {
        const columnsByKey = new Map(toValue(allColumns).map((c) => [c.key, c]))

        return keys
            .map((key) => columnsByKey.get(key))
            .filter((c): c is C => c !== undefined)
    }

    const orderedColumns = computed<C[]>(() => {
        const saved = Array.isArray(savedOrder.value) ? savedOrder.value : null

        if (saved) {
            const resolved = resolve(saved)

            if (resolved.length > 0 || saved.length === 0) {
                return resolved
            }
        }

        return resolve(toValue(defaultColumns))
    })

    function onSaved(columns: ColumnLike[]) {
        savedOrder.value = columns.map((c) => c.key)
    }

    return { orderedColumns, showModal, onSaved }
}