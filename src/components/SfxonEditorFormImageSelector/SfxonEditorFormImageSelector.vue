<script setup lang="ts">

import { mdiFileDocumentOutline } from '@mdi/js'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import SfxonEditorStyles from '@/components/SfxonEditor/SfxonEditor.module.css'
import { translate as t } from '@nextcloud/l10n'

const props = withDefaults(defineProps<{
    accept?: string, // Comma-separated MIME types/extensions for the <input type="file"> accept attribute.
    downloadUrl?: string | null,
    field: string,
    fieldError: string,
    id: string,
    isImage?: boolean,
    label: string,
    imagePreviewUrl: string | null | undefined,
    selectedImageLabel: string | null | undefined,
    showLabel: boolean,
    style: string,
}>(), {
    accept: 'image/*,application/pdf',
    downloadUrl: null,
    isImage: true,
    showLabel: false,
    style: 'sfxonImageSelectorDefault' // Options: sfxonImageSelectorDefault, sfxonImageSelectorCompact
})

const emit = defineEmits<{
    (e: 'localFileChange', event: Event): void
    (e: 'nextcloudFilePicker'): void
    (e: 'input', field: string): void
    (e: 'previewError'): void
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
        <div 
            v-if="showLabel"
            :class="[SfxonEditorStyles.sfxonFormColumnLabel, $style.formFieldLabel]"
        >
            <label :for="id" :class="SfxonEditorStyles.label">{{ label }}</label>
        </div>
        <div :class="SfxonEditorStyles.sfxonFormColumnInput">
            <div :class="SfxonEditorStyles.field">
                <div :class="[
                    $style.imageContainer,
                    $style[style],
                    (!isImage && downloadUrl) ? $style.fileContainer : ''
                ]">
                    <img
                        v-if="imagePreviewUrl && isImage"
                        :src="imagePreviewUrl"
                        :alt="t('sfxonitam', 'file preview')"
                        :class="$style.imagePreview"
                        @error="emit('previewError')"
                    />
                    <a
                        v-else-if="downloadUrl"
                        :href="downloadUrl"
                        :download="selectedImageLabel || undefined"
                        target="_blank"
                        rel="noopener"
                        :class="$style.downloadLink"
                    >
                        <NcIconSvgWrapper :path="mdiFileDocumentOutline" :size="40" />
                        <span :class="$style.downloadLinkLabel">
                            {{ selectedImageLabel || t('sfxonitam', 'Download file') }}
                        </span>
                    </a>
                    <div v-else :class="$style.imagePlaceholder">
                        {{ t('sfxonitam', 'No file selected') }}
                    </div>
                </div>
                <div v-if="selectedImageLabel && isImage" :class="$style.selectedFileLabel">
                    {{ selectedImageLabel }}
                </div>
                <div :class="$style.fileChooserRow">
                    <input
                        :id="id"
                        :accept="accept"
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
    border-radius: 3px!important;
    margin-left: auto!important;
    margin-right: auto!important;
    max-width: 200px;
    padding-top: 3px!important;
}

.fileSelectInput {
    border-radius: 3px!important;
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

.imageContainer.sfxonImageSelectorCompact {
    max-width: 75px;
    max-height: 75px;
}

.imageContainer.fileContainer {
    aspect-ratio: auto;
    max-width: 100%;
    width: 100%;
    height: auto;
    overflow: visible;
}

.imageContainer.fileContainer .downloadLink {
    position: static;
    height: auto;
    width: 100%;
    flex-direction: row;
    align-items: center;
    gap: 12px;
    padding: 10px 16px;
    justify-content: flex-start;
    text-align: left;
    border-radius: 6px;
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
    text-align: center;
}

.downloadLink {
    align-items: center;
    background-color: var(--color-background-hover);
    border: 1px solid var(--color-border);
    color: var(--color-main-text);
    display: flex;
    flex-direction: column;
    gap: 8px;
    height: 100%;
    inset: 0;
    justify-content: center;
    padding: 12px;
    position: absolute;
    text-align: center;
    text-decoration: none;
    width: 100%;
}

.downloadLink:hover {
    background-color: var(--color-background-dark);
}

.downloadLinkLabel {
    font-size: 0.85rem;
    overflow-wrap: anywhere;
    word-break: break-word;
}

.formFieldLabel {
    justify-content: start!important;
}

.formFieldLabel label {
    padding-top: 11px;
}
</style>