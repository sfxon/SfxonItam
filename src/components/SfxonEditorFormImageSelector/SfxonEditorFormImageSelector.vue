<script setup lang="ts">

import { mdiClose } from '@mdi/js'
import NcButton from '@nextcloud/vue/components/NcButton'
import SfxonEditorStyles from '@/components/SfxonEditor/SfxonEditor.module.css'
import { translate as t } from '@nextcloud/l10n'

const props = defineProps<{
    field: string,
    fieldError: string,
    id: string,
    label: string,
    imagePreviewUrl: string | null | undefined,
    selectedImageLabel: string | null | undefined,
}>()

const emit = defineEmits<{
    (e: 'localFileChange', event: Event): void
    (e: 'nextcloudFilePicker'): void
    (e: 'input', field: string): void
}>()

function onLocalFileChange(event: Event) {
    emit('localFileChange', event)
    emit('input', props.field)
}

function onNextcloudFilePicker() {
    emit('nextcloudFilePicker')
    emit('input', props.field)
}
</script>
<template>
    <div :class="SfxonEditorStyles.sfxonFormColumnRow">
        <div :class="SfxonEditorStyles.sfxonFormColumnInput">
            <div :class="SfxonEditorStyles.field">
                <div :class="$style.imageContainer">
                    <img
                        v-if="imagePreviewUrl"
                        :src="imagePreviewUrl"
                        :alt="t('sfxonitam', 'Device image preview')"
                        :class="$style.imagePreview"
                    />
                    <div v-else :class="$style.imagePlaceholder">
                        {{ t('sfxonitam', 'No image selected') }}
                    </div>
                </div>
                <div v-if="selectedImageLabel" :class="$style.selectedFileLabel">
                    {{ selectedImageLabel }}
                </div>
                <div :class="$style.fileChooserRow">
                    <input
                        :id="id"
                        accept="image/*"
                        @change="onLocalFileChange"
                        :class="$style.fileUploadInput"
                        type="file"
                    />
                    <NcButton
                        :class="$style.fileSelectInput"
                        @click="onNextcloudFilePicker"
                        variant="secondary"
                        type="button"
                    >
                        {{ t('sfxonitam', 'Select existing file') }}
                    </NcButton>
                </div>
            </div>
        </div>
    </div>
    <div :class="[SfxonEditorStyles.sfxonFormColumnRow, SfxonEditorStyles.sfxonFormColumnRowError]" v-if="fieldError">
        <div :class="SfxonEditorStyles.sfxonFormColumnLabel">&nbsp;</div>
        <div :class="SfxonEditorStyles.sfxonFormColumnInput">
            <span :class="SfxonEditorStyles.errorText">
                {{ fieldError }}
            </span>
        </div>
    </div>
</template>

<style module>
/* File Upload */
.fileUploadInput {
    margin-left: auto!important;
    margin-right: auto!important;
    max-width: 200px;
    padding-top: 3px!important;
}

.fileSelectInput {
    max-width: 200px;
    width: 100%!important;
}

@media (min-width: 768px) {
    .fileUploadInput,
    .fileSelectInput {
        max-width: 200px;
    }
}

.fileChooserRow {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-inline: auto;
    margin-top: 12px;
    width: 100%;
}

@media (min-width: 768px) {
    .fileChooserRow {
        max-width: 200px;
    }
}

.selectedFileLabel {
    color: var(--color-text-secondary);
    font-size: 0.875rem;
    margin-left: auto;
    margin-right: auto;
    margin-top: 4px;
    max-width: 200px;
    text-align: center;
    width: 100%;
}

.imageContainer {
    aspect-ratio: 1 / 1;
    border-radius: 6px;
    margin-inline: auto;
    max-width: 140px;
    overflow: hidden;
    position: relative;
    width: 100%;
}

@media (min-width: 768px) {
    .imageContainer {
        max-width: 200px;
    }
}

.imagePreview {
    background-color: var(--color-main-background);
    border: 1px solid var(--color-border);
    height: 100%;
    object-fit: contain;
    inset: 0;
    position: absolute;
    width: 100%;
}

.imagePlaceholder {
    align-items: center;
    border: 6px dashed var(--color-border);
    border-radius: 0;
    color: var(--color-text-lighter);
    display: flex;
    font-size: 0.9rem;
    inset: 0;
    justify-content: center;
    margin-left: auto;
    margin-right: auto;
    position: absolute;
}
</style>