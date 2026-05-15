import { generateUrl } from '@nextcloud/router'
import axios from '@nextcloud/axios'
import { translate as t } from '@nextcloud/l10n'

export interface Merchant {
    id: number
    name: string | null
    comment: string
}

export interface MerchantListResponse {
    merchant: Merchant[]
    total: number
    page: number
    limit: number
}

export interface MerchantPayload {
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

export async function createMerchant(payload: MerchantPayload) {
    const { data } = await axios.post(generateUrl('/apps/sfxonitam/merchant/save'), payload)
    return data
}

export async function deleteMerchant(id: number): Promise<void> {
    await axios.delete(generateUrl(`/apps/sfxonitam/merchant/${id}`))
}

export async function fetchAllMerchants(params: ListParams): Promise<MerchantListResponse> {
    const { data } = await axios.get(generateUrl('/apps/sfxonitam/merchant/listall'), { params })
    return data
}

export async function fetchMerchant(id: number): Promise<Merchant> {
    const { data } = await axios.get(generateUrl(`/apps/sfxonitam/merchant/${id}`))
    return data
}

export async function fetchMerchants(params: ListParams): Promise<MerchantListResponse> {
    const { data } = await axios.get(generateUrl('/apps/sfxonitam/merchant/list'), { params })
    return data
}

export function getMerchantDetailLink(merchantId: string) {
    return generateUrl(`/apps/sfxonitam/merchant/detail?merchantId=${merchantId}`)
}

export async function updateMerchant(id: number, payload: MerchantPayload) {
    const { data } = await axios.put(generateUrl(`/apps/sfxonitam/merchant/${id}`), payload)
    return data
}
