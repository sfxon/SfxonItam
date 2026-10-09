<script setup lang="ts">
import SfxonMediaView from '@/components/SfxonMediaView'
import type { MediaType } from '@/composables/useMediaPreview'

defineProps<{
    dataRow: any | null
    type: MediaType
    barcodePrefix?: string
}>()
</script>

<template>
    <Transition name="sfxon-preview">
        <div
            v-if="dataRow"
            :class="$style.panel"
        >
            <SfxonMediaView
                :dataRow="dataRow"
                :type="type"
                :barcodePrefix="barcodePrefix"
                variant="sidebar"
            />
            <span :class="$style.label">
                {{ dataRow.name }}
            </span>
        </div>
    </Transition>
</template>

<style module>
    .panel {
        flex: 0 0 auto;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 6px;
        padding: 8px;
        border-top: 1px solid var(--color-border);
        background: var(--color-main-background);
    }

    .label {
        font-size: 0.8rem;
        color: var(--color-text-lighter);
        text-align: center;
        word-break: break-word;
    }
</style>