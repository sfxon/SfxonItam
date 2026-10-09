<script setup lang="ts">
import { computed } from 'vue'
import { generateUrl } from '@nextcloud/router'
import { translate as t } from '@nextcloud/l10n'
import SfxonBarcode from '@/components/SfxonBarcode'
import SfxonQrCodeView from '@/components/SfxonQrCodeView'
import type { MediaType } from '@/composables/useMediaPreview'

const props = withDefaults(defineProps<{
    dataRow: any
    type: MediaType
    variant: 'sidebar' | 'modal'
    barcodePrefix?: string
}>(), {
    barcodePrefix: 'DEV',
})

const VARIANTS = {
    sidebar: { imageSize: 220, svgStyle: 'width: 100%; height: 100%; max-height: 220px;' },
    modal: { imageSize: 800, svgStyle: 'width: 100%; height: auto;' },
} as const

const config = computed(() => VARIANTS[props.variant])

const imageUrl = computed(() => {
    const size = config.value.imageSize

    return generateUrl(`/core/preview?fileId=${props.dataRow.imageFileId}&x=${size}&y=${size}&a=1`)
})
</script>

<template>
    <SfxonQrCodeView
        v-if="type === 'qrCode'"
        :key="dataRow.id"
        :deviceId="dataRow.id"
        :customStyle="config.svgStyle"
    />
    <SfxonBarcode
        v-else-if="type === 'barcode'"
        :key="dataRow.name"
        :name="dataRow.name"
        :prefix="barcodePrefix"
        :customStyle="config.svgStyle"
    />
    <img
        v-else-if="dataRow.imageFileId"
        :src="imageUrl"
        :alt="dataRow.name ?? ''"
        :class="[$style.img, $style[variant]]"
    />
    <span
        v-else
        :class="[$style.noImage, $style[variant]]"
    >
        {{ t('sfxonitam', 'No image') }}
    </span>
</template>

<style module>
    .img {
        max-width: 100%;
        object-fit: contain;
        display: block;
    }

    .noImage {
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--color-text-lighter);
        border: 1px dashed var(--color-border);
    }

    .sidebar.img {
        width: 100%;
        max-height: 220px;
        border-radius: 6px;
    }

    .modal.img {
        max-height: 70vh;
        border-radius: 8px;
    }

    .sidebar.noImage {
        width: 100%;
        height: 220px;
        font-size: 0.85rem;
        border-radius: 6px;
    }

    .modal.noImage {
        width: 320px;
        height: 200px;
        font-size: 0.9rem;
        border-radius: 8px;
    }
</style>