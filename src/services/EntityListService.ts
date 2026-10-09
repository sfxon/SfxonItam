import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'

export interface EntityListParams {
    orderBy?: string
    direction?: 'ASC' | 'DESC'
    page?: number
    limit?: number
    filters?: Record<string, unknown[]>
}

export async function fetchEntityList(route: string, params: EntityListParams, signal?: AbortSignal) {
    const response = await axios.get(generateUrl(`/apps/sfxonitam/${route}/list`), { params, signal })

    return response.data
}
