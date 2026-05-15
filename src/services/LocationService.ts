import { generateUrl } from '@nextcloud/router'
import axios from '@nextcloud/axios'
import { translate as t } from '@nextcloud/l10n'

export interface Location {
    id: number
    name: string | null
    comment: string
}

export interface LocationListResponse {
    location: Location[]
    total: number
    page: number
    limit: number
}

export interface LocationPayload {
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

export async function createLocation(payload: LocationPayload) {
    const { data } = await axios.post(generateUrl('/apps/sfxonitam/location/save'), payload)
    return data
}

export async function deleteLocation(id: number): Promise<void> {
    await axios.delete(generateUrl(`/apps/sfxonitam/location/${id}`))
}

export async function fetchAllLocations(params: ListParams): Promise<LocationListResponse> {
    const { data } = await axios.get(generateUrl('/apps/sfxonitam/location/listall'), { params })
    return data
}

export async function fetchLocation(id: number): Promise<Location> {
    const { data } = await axios.get(generateUrl(`/apps/sfxonitam/location/${id}`))
    return data
}

export async function fetchLocations(params: ListParams): Promise<LocationListResponse> {
    const { data } = await axios.get(generateUrl('/apps/sfxonitam/location/list'), { params })
    return data
}

export async function updateLocation(id: number, payload: LocationPayload) {
    const { data } = await axios.put(generateUrl(`/apps/sfxonitam/location/${id}`), payload)
    return data
}
