import { generateUrl } from '@nextcloud/router'
import axios from '@nextcloud/axios'

export interface CustomFieldGroup {
    id: number
    name: string | null
    comment: string
}

export interface CustomFieldGroupListResponse {
    position: CustomFieldGroup[]
    total: number
    page: number
    limit: number
}

export interface CustomFieldGroupPayload {
    entityName: string
    name: string
    comment: string
}

export interface ListParams {
    orderBy: string
    direction: string
    page: number
    limit: number
}

export async function fetchCustomFieldGroups(options: ListParams): Promise<CustomFieldGroupListResponse> {
    const params = new URLSearchParams()
    params.append('orderBy', options.orderBy)
    params.append('direction', options.direction)
    params.append('page', String(options.page))
    params.append('limit', String(options.limit))

    for (const [key, values] of Object.entries(options.filters ?? {})) {
        for (const value of values) {
            params.append(`filters[${key}][]`, value)
        }
    }

    const url = generateUrl('/apps/sfxonitam/custom-field-group/search') + '?' + params.toString()
    const { data } = await axios.get(url)
    return data
}
