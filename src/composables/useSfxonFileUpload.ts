import { getCurrentUser } from '@nextcloud/auth'
import { generateRemoteUrl } from '@nextcloud/router'

async function propfindOcProp(davUrl: string, propName: string): Promise<string | null> {
    const res = await fetch(davUrl, {
        method: 'PROPFIND',
        credentials: 'include',
        headers: {
            'Depth': '0',
            'Content-Type': 'application/xml',
            'requesttoken': (window as any).oc_requesttoken,
        },
        body: `<?xml version="1.0"?>
            <d:propfind xmlns:d="DAV:" xmlns:oc="http://owncloud.org/ns">
                <d:prop><oc:${propName}/></d:prop>
            </d:propfind>`,
    })

    if (!res.ok) return null

    const xml = new DOMParser().parseFromString(await res.text(), 'application/xml')
    const el = xml.getElementsByTagNameNS('http://owncloud.org/ns', propName)[0]
    return el?.textContent ?? null
}

export async function resolveFileIdForPath(path: string): Promise<number | null> {
    const user = getCurrentUser()
    const davUrl = generateRemoteUrl(`dav/files/${user?.uid}${path}`)
    const value = await propfindOcProp(davUrl, 'fileid')
    return value ? Number(value) || null : null
}

export async function uploadFileToFolder(file: File, folder = 'ITAM-Files'): Promise<number> {
    const user = getCurrentUser()
    const folderUrl = generateRemoteUrl(`dav/files/${user?.uid}/${folder}`)
    const davUrl = `${folderUrl}/${encodeURIComponent(file.name)}`

    await fetch(folderUrl, {
        method: 'MKCOL',
        credentials: 'include',
        headers: { requesttoken: (window as any).oc_requesttoken },
    })

    const putRes = await fetch(davUrl, {
        method: 'PUT',
        credentials: 'include',
        headers: {
            requesttoken: (window as any).oc_requesttoken,
            'Content-Type': file.type,
        },
        body: file,
    })
    if (!putRes.ok) {
        throw new Error(`Upload failed: ${putRes.status}`)
    }

    const fileId = await propfindOcProp(davUrl, 'fileid')
    const id = fileId ? Number(fileId) || null : null
    if (!id) {
        throw new Error('Could not resolve fileId after upload')
    }

    return id
}

export function buildDavDownloadUrl(path: string): string {
    const user = getCurrentUser()
    return generateRemoteUrl(`dav/files/${user?.uid}${path}`)
}

export function isImageMimeType(mimetype: string | null | undefined): boolean {
    return !!mimetype && mimetype.startsWith('image/')
}

const EXTENSION_MIME_MAP: Record<string, string> = {
    png: 'image/png',
    jpg: 'image/jpeg',
    jpeg: 'image/jpeg',
    gif: 'image/gif',
    webp: 'image/webp',
    svg: 'image/svg+xml',
    pdf: 'application/pdf',
}

export function guessMimeTypeFromExtension(filenameOrPath: string): string {
    const ext = filenameOrPath.split('.').pop()?.toLowerCase() ?? ''
    return EXTENSION_MIME_MAP[ext] ?? ''
}

export function matchesAccept(mimetype: string, accept: string[]): boolean {
    if (!mimetype) return true

    return accept.some((pattern) => {
        if (pattern.endsWith('/*')) {
            return mimetype.startsWith(pattern.slice(0, -1))
        }
        return mimetype === pattern
    })
}
