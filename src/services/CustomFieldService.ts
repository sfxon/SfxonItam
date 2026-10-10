import { generateUrl } from '@nextcloud/router'
import axios from '@nextcloud/axios'
import { translate as t } from '@nextcloud/l10n'

export interface CustomField {
    id: number
    name: string | null
    comment: string
    technicalName?: string | null
    customFieldGroupId?: number | string | null
    type?: string | null
    position?: number
}

export interface CustomFieldListResponse {
    result: {
        mainData: CustomField[]
        relations: Record<string, unknown>
    }
    total: number
    page: number
    limit: number
}

export interface CustomFieldPayload {
    name: string
    purchaseDate: string | null
    comment: string
}

export interface ListParams {
    orderBy: string
    direction: string
    page: number
    limit: number
    filters?: Record<string, any>
}

export async function createCustomField(payload: CustomFieldPayload) {
    const { data } = await axios.post(generateUrl('/apps/sfxonitam/custom-field/save'), payload)
    return data
}

export async function deleteCustomField(id: number): Promise<void> {
    await axios.delete(generateUrl(`/apps/sfxonitam/custom-field/${id}`))
}

export async function fetchCustomField(id: number): Promise<CustomField> {
    const { data } = await axios.get(generateUrl(`/apps/sfxonitam/custom-field/${id}`))
    return data
}

export async function fetchCustomFields(customFieldGroupId: number, options: ListParams): Promise<CustomFieldListResponse> {
    const params = new URLSearchParams()
    params.append('customFieldGroupId', String(customFieldGroupId))
    params.append('orderBy', options.orderBy)
    params.append('direction', options.direction)
    params.append('page', String(options.page))
    params.append('limit', String(options.limit))

    // PHP expects filters[key][]=value.
    for (const [key, values] of Object.entries(options.filters ?? {})) {
        for (const value of values) {
            params.append(`filters[${key}][]`, value)
        }
    }

    const { data } = await axios.get(generateUrl('/apps/sfxonitam/custom-field/list') + '?' + params.toString())
    return data
}

export async function findCustomFields(params: ListParams, signal: AbortSignal) {
    try {
        const { data } = await axios.post(
            generateUrl('/apps/sfxonitam/custom-field/search'),
            params,
            { signal },
        )
        return data
    } catch (error) {
        if (axios.isCancel(error)) {
            // Cancel old requests.
            return null
        }
        console.error('Search failed:', error)
    }

    return null
}

export function getCustomFieldDetailLink(customFieldId: string) {
    return generateUrl(`/apps/sfxonitam/custom-field/detail?customFieldId=${customFieldId}`)
}

export async function updateCustomField(id: string, payload: CustomFieldPayload) {
    const { data } = await axios.put(generateUrl(`/apps/sfxonitam/custom-field/${id}`), payload)
    return data
}
