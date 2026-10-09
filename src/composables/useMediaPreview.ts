import { reactive } from 'vue'

export type MediaType = 'barcode' | 'image' | 'qrCode'
export type CellMounted = (el: HTMLElement, dataRow: any) => () => void

interface MediaState {
    dataRow: any | null
    type: MediaType
}

function bindCell(el: HTMLElement, onEnter: () => void, onClick?: () => void) {
    el.addEventListener('mouseenter', onEnter)
    if (onClick) {
        el.addEventListener('click', onClick)
    }

    return () => {
        el.removeEventListener('mouseenter', onEnter)
        if (onClick) {
            el.removeEventListener('click', onClick)
        }
    }
}

export function useMediaPreview() {
    const modalState = reactive<MediaState>({ dataRow: null, type: 'image' })
    const previewState = reactive<MediaState>({ dataRow: null, type: 'image' })

    function openModal(dataRow: any, type: MediaType) {
        modalState.dataRow = dataRow
        modalState.type = type
    }

    function closeModal() {
        modalState.dataRow = null
    }

    function preview(dataRow: any, type: MediaType) {
        previewState.dataRow = dataRow
        previewState.type = type
    }

    function previewClear() {
        previewState.dataRow = null
    }

    function cellMountedFor(type?: MediaType): CellMounted {
        if (!type) {
            return (el, dataRow) => bindCell(el, () => preview(dataRow, 'image'))
        }

        return (el, dataRow) => bindCell(
            el,
            () => preview(dataRow, type),
            () => openModal(dataRow, type),
        )
    }

    return {
        modalState,
        previewState,
        closeModal,
        previewClear,
        defaultCellMounted: cellMountedFor(),
        imageCellMounted: cellMountedFor('image'),
        qrCodeCellMounted: cellMountedFor('qrCode'),
        barcodeCellMounted: cellMountedFor('barcode'),
    }
}
