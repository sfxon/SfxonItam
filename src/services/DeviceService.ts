import { generateUrl } from '@nextcloud/router'
import axios from '@nextcloud/axios'
import { translate as t } from '@nextcloud/l10n'

export interface Device {
    id: number
    name: string | null
    serialNumber: string | null
    assetNumber: string | null
    purchaseDate: string | null
    deviceStatusId: number | null
}

export interface DeviceListResponse {
    devices: Device[]
    total: number
    page: number
    limit: number
}

export interface DevicePayload {
    name: string
    purchaseDate: string | null
    userId: string | null
}

export interface ListParams {
    orderBy: string
    direction: string
    page: number
    limit: number,
    filters?: Record<string, any>
}

export async function createDevice(payload: DevicePayload) {
    const { data } = await axios.post(generateUrl('/apps/sfxonitam/device/save'), payload)
    return data
}

export async function fetchDevice(id: number): Promise<Device> {
    const { data } = await axios.get(generateUrl(`/apps/sfxonitam/device/${id}`))
    return data
}

export async function fetchDevices(options: ListParams): Promise<DeviceListResponse> {
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

    const url = generateUrl('/apps/sfxonitam/device/list') + '?' + params.toString()
    const { data } = await axios.get(url)
    return data
}

export async function deleteDevice(id: number): Promise<void> {
    await axios.delete(generateUrl(`/apps/sfxonitam/device/${id}`))
}

export async function updateDevice(id: number, payload: DevicePayload) {
    const { data } = await axios.put(generateUrl(`/apps/sfxonitam/device/${id}`), payload)
    return data
}