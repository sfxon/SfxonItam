import { onMounted, reactive, ref, watch } from 'vue'
import { loadState } from '@nextcloud/initial-state'
import { useListState } from '@/composables/useListState'
import { useRelatedEntities } from '@/composables/useRelatedEntities'
import type { RelationMeta } from '@/composables/useRelatedEntities'

type ListState = ReturnType<typeof useListState>

export interface ListFetchParams {
    orderBy: ListState['orderBy']
    direction: ListState['orderDirection']
    page: ListState['page']
    limit: ListState['limit']
    filters: Record<string, any[]>
}

export interface ListResponse {
    total: number
    data: {
        mainData: any[]
        relations: any
    }
}

export interface EntityListOptions<T extends { id: any }> {
    fetch: (params: ListFetchParams) => Promise<ListResponse>
    remove: (id: T['id']) => Promise<unknown>
    mapRow?: (raw: any) => T
    errorMessage: string
}

export function useEntityList<T extends { id: any }>(options: EntityListOptions<T>) {
    const listState = useListState()
    const items = ref<T[]>([]) as { value: T[] }
    const loading = ref(false)
    const error = ref<string | null>(null)
    const itemToDelete = ref<T | null>(null) as { value: T | null }
    const filterValues = reactive<Record<string, { value: any }[]>>({})
    const appliedFilters = ref<Record<string, any[]>>({})
    const entityMeta = loadState<Record<string, RelationMeta>>('sfxonitam', 'entityMeta', {})
    const {
        data: relatedEntityData,
        applyRelations,
        searchFns: relatedEntitySearchFns,
        detailUrl,
    } = useRelatedEntities(entityMeta)

    function relatedEntityOf(key: string): string | undefined {
        const entity = key.replace(/Id$/, '')

        return entity in entityMeta ? entity : undefined
    }

    async function load() {
        error.value = null

        try {
            const response = await options.fetch({
                orderBy: listState.orderBy,
                direction: listState.orderDirection,
                page: listState.page,
                limit: listState.limit,
                filters: appliedFilters.value,
            })

            items.value = response.data.mainData.map((row) => (options.mapRow ? options.mapRow(row) : row))
            listState.total = response.total
            applyRelations(response.data.relations)
        } catch (e) {
            error.value = options.errorMessage
            console.error(e)
        }
    }

    async function reload() {
        loading.value = true

        try {
            await load()
        } finally {
            loading.value = false
        }
    }

    function applyFilters() {
        appliedFilters.value = Object.fromEntries(
            Object.entries(filterValues).map(([key, entries]) => [key, entries.map((e) => e.value)]),
        )
        items.value = []

        if (listState.page === 1) {
            reload()
        } else {
            listState.page = 1
        }
    }

    function requestDelete(item: T) {
        itemToDelete.value = item
    }

    function cancelDelete() {
        itemToDelete.value = null
    }

    async function confirmDelete() {
        if (!itemToDelete.value) {
            return
        }
        await options.remove(itemToDelete.value.id)
        itemToDelete.value = null
        await load()
    }

    watch(
        () => [listState.orderBy, listState.orderDirection, listState.page, listState.limit],
        () => reload(),
    )

    onMounted(reload)

    return {
        listState,
        items,
        loading,
        error,
        filterValues,
        applyFilters,
        itemToDelete,
        requestDelete,
        cancelDelete,
        confirmDelete,
        relatedEntityData,
        relatedEntitySearchFns,
        relatedEntityOf,
        detailUrl,
    }
}
