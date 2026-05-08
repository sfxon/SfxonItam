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
    limit: number
}

export async function createDevice(payload: DevicePayload) {
    const { data } = await axios.post(generateUrl('/apps/sfxonitam/device/save'), payload)
    return data
}

export async function fetchDevice(id: number): Promise<Device> {
    const { data } = await axios.get(generateUrl(`/apps/sfxonitam/device/${id}`))
    return data
}

export async function fetchDevices(params: ListParams): Promise<DeviceListResponse> {
    const { data } = await axios.get(generateUrl('/apps/sfxonitam/device/list'), { params })
    return data
}

export async function deleteDevice(id: number): Promise<void> {
    await axios.delete(generateUrl(`/apps/sfxonitam/device/${id}`))
}

export async function updateDevice(id: number, payload: DevicePayload) {
    const { data } = await axios.put(generateUrl(`/apps/sfxonitam/device/${id}`), payload)
    return data
}