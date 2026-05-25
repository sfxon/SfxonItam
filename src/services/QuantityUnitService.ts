import { generateUrl } from '@nextcloud/router'
import axios from '@nextcloud/axios'
import { translate as t } from '@nextcloud/l10n'

export interface QuantityUnit {
    id: number
    name: string | null
    comment: string
}

export interface QuantityUnitListResponse {
    quantityUnit: QuantityUnit[]
    total: number
    page: number
    limit: number
}

export interface QuantityUnitPayload {
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

export async function createQuantityUnit(payload: QuantityUnitPayload) {
    const { data } = await axios.post(generateUrl('/apps/sfxonitam/quantity-unit/save'), payload)
    return data
}

export async function deleteQuantityUnit(id: number): Promise<void> {
    await axios.delete(generateUrl(`/apps/sfxonitam/quantity-unit/${id}`))
}

export async function fetchAllQuantityUnits(params: ListParams): Promise<QuantityUnitListResponse> {
    const { data } = await axios.get(generateUrl('/apps/sfxonitam/quantity-unit/listall'), { params })
    return data
}

export async function findQuantityUnits(params: ListParams, signal: AbortSignal) {
    try {
        const { data } = await axios.post(
            generateUrl('/apps/sfxonitam/quantity-unit/search'),
            params,
            { signal },
        )
        return data
    } catch (error) {
        if (axios.isCancel(error)) {
            // Veraltete Anfrage – einfach ignorieren
            return null
        }
        console.error('Suche fehlgeschlagen:', error)
    }

    return null
}

export async function fetchQuantityUnit(id: number): Promise<QuantityUnit> {
    const { data } = await axios.get(generateUrl(`/apps/sfxonitam/quantity-unit/${id}`))
    return data
}

export async function fetchQuantityUnits(params: ListParams): Promise<QuantityUnitListResponse> {
    const { data } = await axios.get(generateUrl('/apps/sfxonitam/quantity-unit/list'), { params })
    return data
}

export function getQuantityUnitDetailLink(quantityUnitId: string) {
    return generateUrl(`/apps/sfxonitam/quantity-unit/detail?quantityUnitId=${quantityUnitId}`)
}

export async function updateQuantityUnit(id: number, payload: QuantityUnitPayload) {
    const { data } = await axios.put(generateUrl(`/apps/sfxonitam/quantity-unit/${id}`), payload)
    return data
}
