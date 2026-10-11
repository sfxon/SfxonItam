import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'

export async function fetchEntityRecord(route: string, id: number): Promise<Record<string, any> | null> {
    const { data } = await axios.post(generateUrl(`/apps/sfxonitam/${route}/${id}`))

    return data?.mainData ?? null
}