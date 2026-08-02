import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'

export interface FileMeta {
    id: number
    name: string
    mimetype: string
    size: number
    path: string // Path relative to the user path, e.g. "/ITAM-Images/foo.png".
}

export async function fetchFileMeta(fileId: number): Promise<FileMeta | null> {
    try {
        const { data } = await axios.get(generateUrl(`/apps/sfxonitam/file/${fileId}/meta`))

        if (data?.status !== 'success') {
            return null
        }

        return data.file as FileMeta
    } catch (e) {
        console.error('Could not fetch file meta for id', fileId, e)
        return null
    }
}