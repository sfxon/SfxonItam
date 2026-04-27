import { reactive, computed } from 'vue'

export function useListState(defaultLimit = 20) {
    const state = reactive({
        page: 1,
        limit: defaultLimit,
        orderBy: 'name',
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
        }
    })
    return state
}
