import { computed, ref } from 'vue'
import { generateUrl } from '@nextcloud/router'
import { translate as t } from '@nextcloud/l10n'
import { fetchFileMeta } from '@/services/FileMetaService'
import {
    buildDavDownloadUrl,
    guessMimeTypeFromExtension,
    isImageMimeType,
    matchesAccept,
    resolveFileIdForPath,
    uploadFileToFolder,
} from '@/composables/useSfxonFileUpload'

export interface SfxonFileUploadFieldOptions {
    /** Destination folder in the user's Nextcloud file system for newly uploaded files. */
    folder?: string

    /**
     * Allowed MIME type patterns, e.g., ['image/*', 'application/pdf'].
     * If not specified, both images and PDFs are permitted.
     */
    accept?: string[]
}

const DEFAULT_ACCEPT = ['image/*', 'application/pdf']

export function useSfxonFileUploadField(options: SfxonFileUploadFieldOptions = {}) {
    const folder = options.folder ?? 'ITAM-Files'
    const accept = options.accept ?? DEFAULT_ACCEPT

    const fileId = ref<number | null>(null)
    const fileName = ref<string>('')
    const mimeType = ref<string>('')
    const previewUrl = ref<string | null>(null)
    const downloadUrl = ref<string | null>(null)
    const isImage = ref<boolean>(false)
    const validationError = ref<string>('')
    const loadError = ref<string>('')

    const selectedLocalFile = ref<File | null>(null)
    const selectedRemoteFile = ref<{ id: number; name: string; path: string } | null>(null)

    const selectedLabel = computed(() =>
        selectedLocalFile.value?.name ?? selectedRemoteFile.value?.name ?? fileName.value ?? ''
    )
    const acceptAttr = computed(() => accept.join(','))

    function resetPreviewState() {
        if (previewUrl.value?.startsWith('blob:')) {
            URL.revokeObjectURL(previewUrl.value)
        }
        if (downloadUrl.value?.startsWith('blob:')) {
            URL.revokeObjectURL(downloadUrl.value)
        }
        previewUrl.value = null
        downloadUrl.value = null
    }

    function applyResolvedFile(
        id: number,
        mime: string,
        name: string,
        localBlobUrl: string | null = null,
        remotePath: string | null = null,
    ) {
        fileId.value = id
        fileName.value = name
        mimeType.value = mime
        isImage.value = isImageMimeType(mime)

        if (isImage.value) {
            previewUrl.value = localBlobUrl ?? generateUrl(`/core/preview?fileId=${id}&x=300&y=300&a=1`)
            downloadUrl.value = null
        } else {
            previewUrl.value = null
            downloadUrl.value = localBlobUrl ?? (remotePath ? buildDavDownloadUrl(remotePath) : null)
        }
    }

    function onPreviewError() {
        if (!isImage.value) return
        isImage.value = false
        previewUrl.value = null
        loadError.value = t('sfxonitam', 'Preview could not be loaded.')
    }

    async function setFromExisting(id: number | null): Promise<void> {
        resetPreviewState()
        selectedLocalFile.value = null
        selectedRemoteFile.value = null
        validationError.value = ''
        loadError.value = ''
        fileName.value = ''
        mimeType.value = ''

        if (!id) {
            fileId.value = null
            isImage.value = false
            return
        }

        fileId.value = id

        const meta = await fetchFileMeta(id)

        if (!meta) {
            loadError.value = t('sfxonitam', 'File was not found.')
            return
        }

        applyResolvedFile(id, meta.mimetype, meta.name, null, meta.path)
    }

    function onLocalFileSelected(files: File[]): void {
        const file = files?.[0] ?? null
        if (!file) return

        const mime = file.type || guessMimeTypeFromExtension(file.name)

        if (!matchesAccept(mime, accept)) {
            validationError.value = t('sfxonitam', 'This file type is not allowed.')
            return
        }
        validationError.value = ''
        loadError.value = ''

        resetPreviewState()
        selectedLocalFile.value = file
        selectedRemoteFile.value = null

        const blobUrl = URL.createObjectURL(file)
        applyResolvedFile(0, mime, file.name, blobUrl)
        fileId.value = null // Invalid until uploadIfNeeded() has been called.
    }

    async function openNextcloudFilePicker(title: string): Promise<void> {
        return new Promise((resolve) => {
            // @ts-ignore : OC
            OC.dialogs.filepicker(
                title,
                async (path: string) => {
                    const id = await resolveFileIdForPath(path)
                    if (!id) {
                        resolve()
                        return
                    }

                    const fallbackName = path.split('/').pop() ?? path
                    const meta = await fetchFileMeta(id)
                    const name = meta?.name ?? fallbackName
                    const mime = meta?.mimetype ?? guessMimeTypeFromExtension(fallbackName)

                    if (!matchesAccept(mime, accept)) {
                        validationError.value = t('sfxonitam', 'Dieser Dateityp ist nicht erlaubt.')
                        resolve()
                        return
                    }
                    validationError.value = ''
                    loadError.value = ''

                    resetPreviewState()
                    selectedRemoteFile.value = { id, name, path }
                    selectedLocalFile.value = null
                    applyResolvedFile(id, mime, name, null, meta?.path ?? path)
                    resolve()
                },
                false, // Multiselect
                accept, // Pass allowed MIME types directly to the picker.
                true, // Modal.
                1, // OC.dialogs.FILEPICKER_TYPE_CHOOSE
            )
        })
    }

    async function uploadIfNeeded(): Promise<number | null> {
        if (selectedLocalFile.value) {
            const id = await uploadFileToFolder(selectedLocalFile.value, folder)
            fileId.value = id
            selectedLocalFile.value = null
        }
        return fileId.value
    }

    return {
        fileId,
        fileName,
        mimeType,
        previewUrl,
        downloadUrl,
        isImage,
        selectedLabel,
        acceptAttr,
        validationError,
        loadError,
        setFromExisting,
        onLocalFileSelected,
        onPreviewError,
        openNextcloudFilePicker,
        uploadIfNeeded,
    }
}
