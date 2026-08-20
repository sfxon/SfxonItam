<script setup lang="ts">

import { reactive, ref, watch } from 'vue'
import SfxonEditorFormCheckbox from '@/components/SfxonEditorFormCheckbox';
import SfxonEditorFormDatePicker from '@/components/SfxonEditorFormDatePicker'
import SfxonEditorFormInput from '@/components/SfxonEditorFormInput'
import SfxonEditorFormTextareaLabeled from '@/components/SfxonEditorFormTextareaLabeled'
import SfxonEditorFormEntitySelect from '@/components/SfxonEditorFormEntitySelect'
import SfxonEditorFormImageSelector from '@/components/SfxonEditorFormImageSelector'
import { useSfxonFileUploadField } from '@/composables/useSfxonFileUploadField'
import {
    parseLocalDateString,
    parseLocalDateTimeString,
    toLocalDateString,
    toLocalDateTimeString,
} from '@/services/DateService'

interface CustomFieldDefinition {
    id: number
    customFieldGroupId: string | null
    technicalName: string
    name: string
    type: string
    position: number
    options: string | null
    editable: boolean
    validation: string | null
    comment: string | null
}

interface LabelCompositionField {
    type: 'field'
    id: string
}
interface LabelCompositionText {
    type: 'text'
    value: string
}
type LabelCompositionPart = LabelCompositionField | LabelCompositionText

interface ForeignKeyOptions {
    targetEntity: string
    labelComposition: LabelCompositionPart[]
}

interface FileFieldOptions {
    /** Allowed MIME type patterns, e.g., ['image/*', 'application/pdf']. If not specified: images + PDF. */
    accept?: string[]
    /** Destination folder in the Nextcloud file system for newly uploaded files. */
    folder?: string
}

interface EntityOption {
    id: string | number
    label: string
}

const props = withDefaults(defineProps<{
    customFields: CustomFieldDefinition[]
    fieldErrors?: Record<string, string>
    /**
     * Currently stored values, keyed by technicalName (e.g., when editing
     * an existing record). For "file" fields, the stored fileId (BIGINT)
     * is expected here. The parent component may set or update this
     * property after an asynchronous load operation; the component
     * reactively watches this prop and automatically adopts new values.
     *
     * Note: If the prop is subsequently changed (e.g., due to a
     * second load operation), any values ​​already edited by the user
     * will be overwritten. This is not an issue for the standard
     * "load once, then edit" scenario.
     */
    initialValues?: Record<string, unknown>
}>(), {
    fieldErrors: () => ({}),
    initialValues: () => ({}),
})

const emit = defineEmits<{
    'input': [technicalName: string]
    'update:values': [values: Record<string, unknown>]
}>()

type FindFn = (params: { filters: Record<string, string[]> }, signal: AbortSignal) => Promise<any>

const entityRegistry: Record<string, { load: () => Promise<FindFn> }> = {
    user: {
        load: async () => {
            const mod = await import('@/services/ItamUserService')
            return mod.findItamUsers
        },
    },
    merchant: {
        load: async () => {
            const mod = await import('@/services/MerchantService')
            return mod.findMerchants
        },
    },
    position: {
        load: async () => {
            const mod = await import('@/services/PositionService')
            return mod.findPositions
        },
    },
}

const loadedFindFns: Record<string, FindFn> = {}

const values = reactive<Record<string, unknown>>({})
const foreignKeyOptions = reactive<Record<string, EntityOption[]>>({})
const foreignKeySelected = reactive<Record<string, EntityOption | null>>({})
const foreignKeyLoading = reactive<Record<string, boolean>>({})
const fileFields = reactive<Record<string, ReturnType<typeof useSfxonFileUploadField>>>({})

function parseForeignKeyOptions(field: CustomFieldDefinition): ForeignKeyOptions | null {
    if (!field.options) {
        return null
    }

    try {
        const parsed = JSON.parse(field.options)
        return parsed.foreignKey ?? null
    } catch (e) {
        console.error(`Invalid options JSON for custom field  "${field.technicalName}":`, e)
        return null
    }
}

function parseFileOptions(field: CustomFieldDefinition): FileFieldOptions | null {
    if (!field.options) {
        return null
    }

    try {
        const parsed = JSON.parse(field.options)
        return parsed.file ?? null
    } catch (e) {
        console.error(`Ungültiges options-JSON für Custom Field "${field.technicalName}":`, e)
        return null
    }
}

function buildOptionLabel(record: Record<string, any>, composition: LabelCompositionPart[]): string {
    return composition
        .map((part) => (part.type === 'field' ? (record[part.id] ?? '') : part.value))
        .join('')
}

function errorFor(technicalName: string): string {
    return props.fieldErrors[`customFields.${technicalName}`] ?? ''
}

function onFieldInput(technicalName: string) {
    emit('input', technicalName)
}

/** Parses a 'date' or 'datetime' custom field's stored string value back into a Date. */
function parseCustomDateFieldValue(field: CustomFieldDefinition, incoming: unknown): Date | null {
    if (!incoming) {
        return null
    }

    return field.type === 'datetime'
        ? parseLocalDateTimeString(String(incoming))
        : parseLocalDateString(String(incoming))
}

/** Converts a 'date'/'datetime' custom field's current Date value back into the string format sent to the backend. */
function serializeCustomFieldValue(field: CustomFieldDefinition, value: unknown): unknown {
    if (field.type !== 'date' && field.type !== 'datetime') {
        return value
    }

    if (!(value instanceof Date)) {
        return null
    }

    return field.type === 'datetime' ? toLocalDateTimeString(value) : toLocalDateString(value)
}

props.customFields.forEach((field) => {
    if (field.type === 'foreign_key') {
        foreignKeyOptions[field.technicalName] = []
        foreignKeySelected[field.technicalName] = null
        values[field.technicalName] = null
        return
    }

    if (field.type === 'file') {
        const fileOptions = parseFileOptions(field)
        fileFields[field.technicalName] = reactive(useSfxonFileUploadField({
            folder: fileOptions?.folder ?? 'ITAM-CustomFiles',
            accept: fileOptions?.accept,
        }))
        values[field.technicalName] = null
        return
    }

    if (field.type === 'date' || field.type === 'datetime') {
        values[field.technicalName] = null
        return
    }

    if (!(field.technicalName in values)) {
        values[field.technicalName] = ''
    }
})

Object.keys(fileFields).forEach((technicalName) => {
    watch(
        () => fileFields[technicalName].fileId,
        (newId) => { values[technicalName] = newId },
    )
})

watch(
    () => props.initialValues,
    (newValues) => {
        if (!newValues) {
            return
        }

        const fileLoads: Promise<void>[] = []

        for (const field of props.customFields) {
            if (!(field.technicalName in newValues)) {
                continue
            }

            const incoming = newValues[field.technicalName]

            if (field.type === 'file') {
                fileLoads.push(
                    fileFields[field.technicalName].setFromExisting((incoming as number) ?? null)
                )
                continue
            }

            if (field.type === 'foreign_key') {
                values[field.technicalName] = incoming ?? null
                continue
            }

            if (field.type === 'date' || field.type === 'datetime') {
                values[field.technicalName] = parseCustomDateFieldValue(field, incoming)
                continue
            }

            values[field.technicalName] = incoming ?? ''
        }

        // Loads the file values in the background. Prevents "blocks" in the rendering process.
        Promise.all(fileLoads).catch((e) => {
            console.error('Error while loading custom file fields:', e)
        })
    },
    { immediate: true }
)

async function getFindFn(targetEntity: string): Promise<FindFn | null> {
    if (loadedFindFns[targetEntity]) {
        return loadedFindFns[targetEntity]
    }

    const registryEntry = entityRegistry[targetEntity]
    
    if (!registryEntry) {
        console.error(
            `Keine Entität "${targetEntity}" in entityRegistry registriert. `
            + `Bitte in SfxonCustomFields.vue ergänzen.`
        )
        return null
    }

    const findFn = await registryEntry.load()
    loadedFindFns[targetEntity] = findFn
    return findFn
}

async function searchForeignKeyOptions(
    field: CustomFieldDefinition,
    query: string,
    signal: AbortSignal
): Promise<void> {
    const fk = parseForeignKeyOptions(field)

    if (!fk) {
        return
    }

    const findFn = await getFindFn(fk.targetEntity)

    if (!findFn) {
        return
    }

    const filterFields = fk.labelComposition
        .filter((part): part is LabelCompositionField => part.type === 'field')
        .map((part) => part.id)

    const filters: Record<string, string[]> = {}
    filterFields.forEach((f) => { filters[f] = [query] })

    foreignKeyLoading[field.technicalName] = true

    try {
        const data = await findFn({ filters }, signal)

        if (data === null || data.mainData === null) {
            return
        }

        foreignKeyOptions[field.technicalName] = Object.values(data.mainData).map((record: any) => ({
            id: record.id,
            label: buildOptionLabel(record, fk.labelComposition),
        }))
    } finally {
        foreignKeyLoading[field.technicalName] = false
    }
}

function onForeignKeyInput(field: CustomFieldDefinition, value: EntityOption | null) {
    foreignKeySelected[field.technicalName] = value
    values[field.technicalName] = value?.id ?? null
}

function onFileLocalChange(field: CustomFieldDefinition, event: Event) {
    const files = Array.from((event.target as HTMLInputElement).files ?? [])
    fileFields[field.technicalName].onLocalFileSelected(files)
}

async function onFileNextcloudPicker(field: CustomFieldDefinition): Promise<void> {
    await fileFields[field.technicalName].openNextcloudFilePicker(field.name)
}

async function uploadPendingFiles(): Promise<void> {
    for (const field of Object.values(fileFields)) {
        await field.uploadIfNeeded()
    }
}

defineExpose({
    uploadPendingFiles,
})

watch(values, (v) => {
    const serialized: Record<string, unknown> = { ...v }

    for (const field of props.customFields) {
        if (field.type === 'date' || field.type === 'datetime') {
            serialized[field.technicalName] = serializeCustomFieldValue(field, v[field.technicalName])
        }
    }

    emit('update:values', serialized)
}, { deep: true })
</script>

<template>
    <div v-if="customFields.length" :class="$style.sfxonCustomFields">
        <template
            v-for="customField in customFields"
            :key="customField.id"
        >
            <SfxonEditorFormTextareaLabeled
                :field="customField.technicalName"
                :id="`custom-field-${customField.technicalName}`"
                :label="customField.name + ':'"
                v-model="values[customField.technicalName]"
                :field-error="errorFor(customField.technicalName)"
                @input="onFieldInput(customField.technicalName)"
                v-if="customField.type === 'longtext'"
            />

            <SfxonEditorFormInput
                inputmode="numeric"
                :field="customField.technicalName"
                :id="`custom-field-${customField.technicalName}`"
                :label="customField.name + ':'"
                v-model="values[customField.technicalName]"
                type="text"
                :field-error="errorFor(customField.technicalName)"
                @input="onFieldInput(customField.technicalName)"
                v-else-if="customField.type === 'integer'"
            />

            <SfxonEditorFormInput
                inputmode="decimal"
                :field="customField.technicalName"
                :id="`custom-field-${customField.technicalName}`"
                :label="customField.name + ':'"
                v-model="values[customField.technicalName]"
                type="text"
                :field-error="errorFor(customField.technicalName)"
                @input="onFieldInput(customField.technicalName)"
                v-else-if="customField.type === 'decimal'"
            />

            <SfxonEditorFormEntitySelect
                :field="customField.technicalName"
                :id="`custom-field-${customField.technicalName}`"
                :label="customField.name + ':'"
                :model-value="foreignKeySelected[customField.technicalName]"
                :options="foreignKeyOptions[customField.technicalName]"
                track-by="id"
                :search-fn="(query, signal) => searchForeignKeyOptions(customField, query, signal)"
                @update:model-value="(val) => onForeignKeyInput(customField, val)"
                :field-error="errorFor(customField.technicalName)"
                @input="onFieldInput(customField.technicalName)"
                v-else-if="customField.type === 'foreign_key'"
            />

            <SfxonEditorFormCheckbox
                :field="customField.technicalName"
                :id="`custom-field-${customField.technicalName}`"
                :label="customField.name"
                v-model="values[customField.technicalName]"
                :field-error="errorFor(customField.technicalName)"
                @input="onFieldInput(customField.technicalName)"
                v-else-if="customField.type === 'boolean'"
            />

            <SfxonEditorFormDatePicker
                :field="customField.technicalName"
                :id="`custom-field-${customField.technicalName}`"
                :label="customField.name + ':'"
                :type="customField.type === 'datetime' ? 'datetime' : 'date'"
                v-model="values[customField.technicalName]"
                :field-error="errorFor(customField.technicalName)"
                @input="onFieldInput(customField.technicalName)"
                v-else-if="customField.type === 'date' || customField.type === 'datetime'"
            />

            <SfxonEditorFormImageSelector
                :field="customField.technicalName"
                :field-error="fileFields[customField.technicalName] ? (errorFor(customField.technicalName) || fileFields[customField.technicalName].validationError) : ''"
                :id="`custom-field-${customField.technicalName}`"
                :label="customField.name + ':'"
                :image-preview-url="fileFields[customField.technicalName].previewUrl"
                :is-image="fileFields[customField.technicalName].isImage"
                :download-url="fileFields[customField.technicalName].downloadUrl"
                :accept="fileFields[customField.technicalName].acceptAttr"
                :selected-image-label="fileFields[customField.technicalName].selectedLabel"
                @local-file-change="(event) => onFileLocalChange(customField, event)"
                @nextcloud-file-picker="() => onFileNextcloudPicker(customField)"
                @preview-error="fileFields[customField.technicalName].onPreviewError"
                :show-label="true"
                :style="'sfxonImageSelectorCompact'"
                @input="onFieldInput(customField.technicalName)"
                v-else-if="customField.type === 'file'"
            />

            <SfxonEditorFormInput
                :field="customField.technicalName"
                :id="`custom-field-${customField.technicalName}`"
                :label="customField.name + ':'"
                v-model="values[customField.technicalName]"
                type="text"
                :field-error="errorFor(customField.technicalName)"
                @input="onFieldInput(customField.technicalName)"
                v-else
            />
        </template>
    </div>
</template>

<style module>
.sfxonCustomFields {
    /*display: flex;
    flex-direction: column;
    gap: 8px;*/
}
</style>