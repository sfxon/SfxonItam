<script setup lang="ts">
import NcDialog from '@nextcloud/vue/components/NcDialog'
import SfxonMediaView from '@/components/SfxonMediaView'
import type { MediaType } from '@/composables/useMediaPreview'

defineProps<{
    dataRow: any | null
    type: MediaType
    barcodePrefix?: string
}>()

defineEmits<{
    (e: 'close'): void
}>()
</script>

<template>
    <NcDialog
        v-if="dataRow"
        :name="dataRow?.name ?? ''"
        :open="!!dataRow"
        size="normal"
        close-on-click-outside
        @closing="$emit('close')"
    >
        <div
            :class="$style.content"
            @click.left="$emit('close')"
        >
            <SfxonMediaView
                :dataRow="dataRow"
                :type="type"
                :barcodePrefix="barcodePrefix"
                variant="modal"
            />
        </div>
    </NcDialog>
</template>

<style module>
    .content {
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 16px 16px 48px;
        cursor: pointer;
        min-height: 200px;
    }
</style>