import { generateUrl } from '@nextcloud/router'
import axios from '@nextcloud/axios'
import { translate as t } from '@nextcloud/l10n'

export interface Manufacturer {
    id: number
    name: string | null
    comment: string
}

export interface ManufacturerListResponse {
    manufacturer: Manufacturer[]
    total: number
    page: number
    limit: number
}

export interface ManufacturerPayload {
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

export async function createManufacturer(payload: ManufacturerPayload) {
    const { data } = await axios.post(generateUrl('/apps/sfxonitam/manufacturer/save'), payload)
    return data
}

export async function fetchAllManufacturers(params: ListParams): Promise<ManufacturerListResponse> {
    const { data } = await axios.get(generateUrl('/apps/sfxonitam/manufacturer/listall'), { params })
    return data
}

export async function fetchManufacturer(id: number): Promise<Manufacturer> {
    const { data } = await axios.get(generateUrl(`/apps/sfxonitam/manufacturer/${id}`))
    return data
}

export async function fetchManufacturers(params: ListParams): Promise<ManufacturerListResponse> {
    const { data } = await axios.get(generateUrl('/apps/sfxonitam/manufacturer/list'), { params })
    return data
}

export async function deleteManufacturer(id: number): Promise<void> {
    await axios.delete(generateUrl(`/apps/sfxonitam/manufacturer/${id}`))
}

export async function updateManufacturer(id: number, payload: ManufacturerPayload) {
    const { data } = await axios.put(generateUrl(`/apps/sfxonitam/manufacturer/${id}`), payload)
    return data
}
