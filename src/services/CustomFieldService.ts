import { generateUrl } from '@nextcloud/router'
import axios from '@nextcloud/axios'
import { translate as t } from '@nextcloud/l10n'

export interface CustomField {
    id: number
    name: string | null
    comment: string
}

export interface CustomFieldListResponse {
    position: CustomField[]
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

export async function fetchCustomFields(customFieldGroupId: number, params: ListParams): Promise<CustomFieldListResponse> {
    const { data } = await axios.get(generateUrl('/apps/sfxonitam/custom-field/list?customFieldGroupId=' + customFieldGroupId), { params })
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
