import { generateUrl } from '@nextcloud/router'
import axios from '@nextcloud/axios'
import { translate as t } from '@nextcloud/l10n'

export interface ItamUser {
    id: number
    name: string | null
    comment: string
}

export interface ItamUserListResponse {
    itamUser: ItamUser[]
    total: number
    page: number
    limit: number
}

export interface ItamUserPayload {
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

export async function createItamUser(payload: ItamUserPayload) {
    const { data } = await axios.post(generateUrl('/apps/sfxonitam/itam-user/save'), payload)
    return data
}

export async function deleteItamUser(id: number): Promise<void> {
    await axios.delete(generateUrl(`/apps/sfxonitam/itam-user/${id}`))
}

export async function fetchAllItamUsers(params: ListParams): Promise<ItamUserListResponse> {
    const { data } = await axios.get(generateUrl('/apps/sfxonitam/itam-user/listall'), { params })
    return data
}

export async function fetchItamUser(id: number): Promise<ItamUser> {
    const { data } = await axios.get(generateUrl(`/apps/sfxonitam/itam-user/${id}`))
    return data
}

export async function fetchItamUsers(params: ListParams): Promise<ItamUserListResponse> {
    const { data } = await axios.get(generateUrl('/apps/sfxonitam/itam-user/list'), { params })
    return data
}

export function getItamUserDetailLink(itamUserId: string) {
    return generateUrl(`/apps/sfxonitam/itam-user/detail?itamUserId=${itamUserId}`)
}

export async function updateItamUser(id: number, payload: ItamUserPayload) {
    const { data } = await axios.put(generateUrl(`/apps/sfxonitam/itam-user/${id}`), payload)
    return data
}
