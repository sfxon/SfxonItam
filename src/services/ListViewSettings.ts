import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'

export async function fetchColumnOrder(listId) {
    const { data } = await axios.get(generateUrl('/apps/sfxonitam/listview-settings/{listId}', { listId }))
    return data.ocs.data.columns
}

export async function saveColumnOrder(listId, columns) {
    await axios.put(generateUrl('/apps/sfxonitam/listview-settings/{listId}', { listId }), { columns })
}

export async function fetchUiState(viewId) {
    const { data } = await axios.get(generateUrl('/apps/sfxonitam/listview-settings/{viewId}/ui-state', { viewId }))
    return data.ocs.data.uiState
}

export async function saveUiState(viewId, uiState) {
    await axios.put(generateUrl('/apps/sfxonitam/listview-settings/{viewId}/ui-state', { viewId }), { uiState })
}