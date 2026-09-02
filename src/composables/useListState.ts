import { reactive, computed } from 'vue'

export function useListState(defaultLimit = 25, defaultOrderBy = 'name') {
    const state = reactive({
        page: 1,
        limit: defaultLimit,
        orderBy: defaultOrderBy,
        orderDirection: 'ASC' as 'ASC' | 'DESC',
        total: 0,
        totalPages: computed(() => Math.max(1, Math.ceil(state.total / state.limit))),
        sortBy(col: string) {
            if (state.orderBy === col) {
                state.orderDirection = state.orderDirection === 'ASC' ? 'DESC' : 'ASC'
            } else {
                state.orderBy = col
                state.orderDirection = 'ASC'
            }
            state.page = 1
        },
        setLimit(newLimit: number) {
            if (newLimit === state.limit) {
                return
            }

            const firstVisibleIndex = (state.page - 1) * state.limit

            state.limit = newLimit

            state.page = Math.min(
                Math.max(1, Math.floor(firstVisibleIndex / newLimit) + 1),
                state.totalPages,
            )
        },
    })
    return state
}
