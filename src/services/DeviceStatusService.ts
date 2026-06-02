import { generateUrl } from '@nextcloud/router'
import axios from '@nextcloud/axios'
import { translate as t } from '@nextcloud/l10n'

export interface DeviceStatus {
    id: number
    name: string | null
    comment: string
}

export interface DeviceStatusListResponse {
    deviceStatis: DeviceStatus[]
    total: number
    page: number
    limit: number
}

export interface DeviceStatusPayload {
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

export async function createDeviceStatus(payload: DeviceStatusPayload) {
    const { data } = await axios.post(generateUrl('/apps/sfxonitam/device-status/save'), payload)
    return data
}

export async function deleteDeviceStatus(id: number): Promise<void> {
    await axios.delete(generateUrl(`/apps/sfxonitam/device-status/${id}`))
}

export async function fetchAllDeviceStatis(params: ListParams): Promise<DeviceStatusListResponse> {
    const { data } = await axios.get(generateUrl('/apps/sfxonitam/device-status/listall'), { params })
    return data
}

export async function fetchDeviceStatus(id: number): Promise<DeviceStatus> {
    const { data } = await axios.get(generateUrl(`/apps/sfxonitam/device-status/${id}`))
    return data
}

export async function fetchDeviceStatis(params: ListParams): Promise<DeviceStatusListResponse> {
    const { data } = await axios.get(generateUrl('/apps/sfxonitam/device-status/list'), { params })
    return data
}

export async function findDeviceStatis(params: ListParams, signal: AbortSignal) {
    try {
        const { data } = await axios.post(
            generateUrl('/apps/sfxonitam/device-status/search'),
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

export function getDeviceStatusDetailLink(deviceStatusId: string) {
    return generateUrl(`/apps/sfxonitam/device-status/detail?deviceStatusId=${deviceStatusId}`)
}

export async function updateDeviceStatus(id: number, payload: DeviceStatusPayload) {
    const { data } = await axios.put(generateUrl(`/apps/sfxonitam/device-status/${id}`), payload)
    return data
}
