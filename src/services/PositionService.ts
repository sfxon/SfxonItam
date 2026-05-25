import { generateUrl } from '@nextcloud/router'
import axios from '@nextcloud/axios'
import { translate as t } from '@nextcloud/l10n'

export interface Position {
    id: number
    name: string | null
    comment: string
}

export interface PositionListResponse {
    position: Position[]
    total: number
    page: number
    limit: number
}

export interface PositionPayload {
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

export async function createPosition(payload: PositionPayload) {
    const { data } = await axios.post(generateUrl('/apps/sfxonitam/position/save'), payload)
    return data
}

export async function deletePosition(id: number): Promise<void> {
    await axios.delete(generateUrl(`/apps/sfxonitam/position/${id}`))
}

export async function fetchAllPositions(params: ListParams): Promise<PositionListResponse> {
    const { data } = await axios.get(generateUrl('/apps/sfxonitam/position/listall'), { params })
    return data
}

export async function fetchPosition(id: number): Promise<Position> {
    const { data } = await axios.get(generateUrl(`/apps/sfxonitam/position/${id}`))
    return data
}

export async function fetchPositions(params: ListParams): Promise<PositionListResponse> {
    const { data } = await axios.get(generateUrl('/apps/sfxonitam/position/list'), { params })
    return data
}

export async function findPositions(params: ListParams, signal: AbortSignal) {
    try {
        const { data } = await axios.post(
            generateUrl('/apps/sfxonitam/position/search'),
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

export function getPositionDetailLink(positionId: string) {
    return generateUrl(`/apps/sfxonitam/position/detail?positionId=${positionId}`)
}

export async function updatePosition(id: number, payload: PositionPayload) {
    const { data } = await axios.put(generateUrl(`/apps/sfxonitam/position/${id}`), payload)
    return data
}