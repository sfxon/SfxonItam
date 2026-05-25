import { generateUrl } from '@nextcloud/router'
import axios from '@nextcloud/axios'
import { translate as t } from '@nextcloud/l10n'

export interface DeviceType {
    id: number
    name: string | null
    comment: string
}

export interface DeviceTypeListResponse {
    deviceType: DeviceType[]
    total: number
    page: number
    limit: number
}

export interface DeviceTypePayload {
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

export async function createDeviceType(payload: DeviceTypePayload) {
    const { data } = await axios.post(generateUrl('/apps/sfxonitam/device-type/save'), payload)
    return data
}

export async function deleteDeviceType(id: number): Promise<void> {
    await axios.delete(generateUrl(`/apps/sfxonitam/device-type/${id}`))
}

export async function fetchAllDeviceTypes(params: ListParams): Promise<DeviceTypeListResponse> {
    const { data } = await axios.get(generateUrl('/apps/sfxonitam/device-type/listall'), { params })
    return data
}

export async function fetchDeviceType(id: number): Promise<DeviceType> {
    const { data } = await axios.get(generateUrl(`/apps/sfxonitam/device-type/${id}`))
    return data
}

export async function fetchDeviceTypes(params: ListParams): Promise<DeviceTypeListResponse> {
    const { data } = await axios.get(generateUrl('/apps/sfxonitam/device-type/list'), { params })
    return data
}

export async function findDeviceTypes(params: ListParams, signal: AbortSignal) {
    try {
        const { data } = await axios.post(
            generateUrl('/apps/sfxonitam/device-type/search'),
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

export function getDeviceTypeDetailLink(deviceTypeId: string) {
    return generateUrl(`/apps/sfxonitam/device-type/detail?deviceTypeId=${deviceTypeId}`)
}

export async function updateDeviceType(id: number, payload: DeviceTypePayload) {
    const { data } = await axios.put(generateUrl(`/apps/sfxonitam/device-type/${id}`), payload)
    return data
}