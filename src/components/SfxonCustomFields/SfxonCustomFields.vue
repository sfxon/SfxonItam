<script setup lang="ts">

import { computed, reactive, ref, watch } from 'vue'
import { loadState } from '@nextcloud/initial-state'
import { translate as t } from '@nextcloud/l10n'
import SfxonEditorFormCheckbox from '@/components/SfxonEditorFormCheckbox';
import SfxonEditorFormDatePicker from '@/components/SfxonEditorFormDatePicker'
import SfxonEditorFormInput from '@/components/SfxonEditorFormInput'
import SfxonEditorFormTextareaLabeled from '@/components/SfxonEditorFormTextareaLabeled'
import SfxonEditorFormEntitySelect from '@/components/SfxonEditorFormEntitySelect'
import SfxonEditorFormImageSelector from '@/components/SfxonEditorFormImageSelector'
import SfxonEntityDialog from '@/components/SfxonEntityDialog'
import { useRelatedEntities } from '@/composables/useRelatedEntities'
import { useSfxonFileUploadField } from '@/composables/useSfxonFileUploadField'
import type { RelationMeta } from '@/composables/useRelatedEntities'
import { fetchEntityList } from '@/services/EntityListService'
import { fetchEntityRecord } from '@/services/EntityRecordService'
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
    /**
     * Field definitions of the entities (same as in the editors), keyed by entity name.
     * Needed to build the "add new record" dialog for foreign key fields.
     */
    entityDefinitions?: Record<string, any[]>
    /**
     * Service modules (e.g. { DeviceStatusService }) that provide create<Entity>() for the "add new record" dialog.
     * Foreign key fields whose target entity has no service or definition get no plus button.
     */
    services?: Record<string, any>
}>(), {
    fieldErrors: () => ({}),
    initialValues: () => ({}),
    entityDefinitions: () => ({}),
    services: () => ({}),
})

const emit = defineEmits<{
    'input': [technicalName: string]
    'update:values': [values: Record<string, unknown>]
    'record-added': []
}>()

const FOREIGN_KEY_SEARCH_LIMIT = 50

const entityMeta = loadState<Record<string, RelationMeta>>('sfxonitam', 'entityMeta', {})

function resolveEntityMeta(entity: string): RelationMeta | null {
    const meta = entityMeta[entity]

    if (!meta) {
        console.error(
            `Entität "${entity}" ist im Backend nicht registriert. Verfügbar: ${Object.keys(entityMeta).join(', ')}`
        )
        return null
    }

    return meta
}

async function searchEntityRecords(
    route: string,
    filters: Record<string, string[]>,
    signal: AbortSignal
): Promise<Record<string, any> | null> {
    try {
        const response = await fetchEntityList(route, { page: 1, limit: FOREIGN_KEY_SEARCH_LIMIT, filters }, signal)

        return response?.data?.mainData ?? null
    } catch (e) {
        if (signal.aborted) {
            return null
        }

        throw e
    }
}

async function fetchRecordById(entity: string, id: number): Promise<Record<string, any> | null> {
    const meta = resolveEntityMeta(entity)

    if (!meta) {
        return null
    }

    return fetchEntityRecord(meta.route, id)
}

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
        return parsed.imageFile ?? null
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

const { data: relatedEntityData, searchFns: relatedEntitySearchFns } = useRelatedEntities(entityMeta)
const addRelations = reactive<Record<string, any>>({})
const addDialogForeignEntityConfig = reactive<Record<string, any>>({})
const addDialogEntityName = ref('')
const addDialogField = ref<CustomFieldDefinition | null>(null)

function buildAddRelation(entityName: string): void {
    const entityDefinition = props.entityDefinitions[entityName]
    const capitalizedEntityName = entityName.charAt(0).toUpperCase() + entityName.slice(1)
    const saveFunction = props.services[`${capitalizedEntityName}Service`]?.[`create${capitalizedEntityName}`]

    if (!entityDefinition || !saveFunction) {
        return
    }

    const fields = []

    for (const fieldDefinition of entityDefinition) {
        if (!fieldDefinition.requiredOnCreate && !fieldDefinition.foreignEntity) {
            continue
        }

        fields.push({
            fieldName: fieldDefinition.propertyName,
            label: t('sfxonitam', fieldDefinition.label) + ':',
            sfxonType: fieldDefinition.type,
            type: fieldDefinition.foreignEntity ? null : fieldDefinition.type,
            value: fieldDefinition.foreignEntity ? null : '',
            foreignEntity: fieldDefinition.foreignEntity || null,
        })

        // Selects inside of the dialog need their options and search function, too.
        const foreignEntity = fieldDefinition.foreignEntity

        if (foreignEntity && !addDialogForeignEntityConfig[foreignEntity]) {
            addDialogForeignEntityConfig[foreignEntity] = {
                searchFn: relatedEntitySearchFns[foreignEntity],
                optionsTarget: computed(() => relatedEntityData[foreignEntity]),
                labelFields: { fields: ['name'] },
            }
        }
    }

    addRelations[entityName] = {
        addRecordModal: {
            heading: t('sfxonitam', 'Add ' + entityName),
            fields,
            saveFunction,
        },
    }
}

props.customFields.forEach((field) => {
    const entityName = field.type === 'foreignKey' ? parseForeignKeyOptions(field)?.targetEntity : null

    if (entityName && !addRelations[entityName]) {
        buildAddRelation(entityName)
    }
})

function canAddRecord(field: CustomFieldDefinition): boolean {
    const entityName = parseForeignKeyOptions(field)?.targetEntity

    return !!entityName && !!addRelations[entityName]
}

function openAddDialog(field: CustomFieldDefinition): void {
    addDialogField.value = field
    addDialogEntityName.value = parseForeignKeyOptions(field)?.targetEntity ?? ''
}

function closeAddDialog(): void {
    addDialogEntityName.value = ''
    addDialogField.value = null
}

// The dialog closes itself right after "saved", so the field has to be read before the first await.
// The label is built from the label composition of the custom field, not from the dialog's fields.
async function onRecordAdded(payload: { entityName: string; newOption: { id: string; label: string } }): Promise<void> {
    const field = addDialogField.value

    if (!field) {
        return
    }

    await loadForeignKeySelected(field, payload.newOption.id)
    values[field.technicalName] = Number(payload.newOption.id)
    onFieldInput(field.technicalName)
    emit('record-added')
}

props.customFields.forEach((field) => {
    if (field.type === 'foreignKey') {
        foreignKeyOptions[field.technicalName] = []
        foreignKeySelected[field.technicalName] = null
        values[field.technicalName] = null
        return
    }

    if (field.type === 'imageFile') {
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

    if (field.type === 'bool') {
        values[field.technicalName] = false
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

            if (field.type === 'imageFile') {
                fileLoads.push(
                    fileFields[field.technicalName].setFromExisting((incoming as number) ?? null)
                )
                continue
            }

            if (field.type === 'foreignKey') {
                values[field.technicalName] = incoming ?? null
                fileLoads.push(loadForeignKeySelected(field, incoming))
                continue
            }

            if (field.type === 'date' || field.type === 'datetime') {
                values[field.technicalName] = parseCustomDateFieldValue(field, incoming)
                continue
            }

            if (field.type === 'bool') {
                values[field.technicalName] = normalizeBooleanValue(incoming)
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

// Very kindly allow null, false, true, 0 or 1 as values,
// but parse them all to true or false.
// This is a reaction to the database designs -
// since Nc can at least use 3 different kind of databases (sqlite, mysql, postgre sql).
// Note: I might move this into the SfxonEditorFormCheckbox component later.
function normalizeBooleanValue(incoming: unknown): boolean | null {
    if (incoming === null || incoming === undefined) {
        return null
    }

    if (typeof incoming === 'boolean') {
        return incoming
    }

    if (incoming === 1 || incoming === '1') {
        return true
    }

    if (incoming === 0 || incoming === '0') {
        return false
    }

    console.warn('Unerwarteter Boolean-Wert für Custom Field:', incoming)
    return null
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

    const meta = resolveEntityMeta(fk.targetEntity)

    if (!meta) {
        return
    }

    const filterFields = fk.labelComposition
        .filter((part): part is LabelCompositionField => part.type === 'field')
        .map((part) => part.id)

    const filters: Record<string, string[]> = { _search: [query], _searchFields: filterFields }

    foreignKeyLoading[field.technicalName] = true

    try {
        const mainData = await searchEntityRecords(meta.route, filters, signal)

        if (mainData === null) {
            return
        }

        foreignKeyOptions[field.technicalName] = Object.values(mainData).map((record: any) => ({
            id: record.id,
            label: buildOptionLabel(record, fk.labelComposition),
        }))
    } finally {
        foreignKeyLoading[field.technicalName] = false
    }
}

async function loadForeignKeySelected(field: CustomFieldDefinition, id: unknown): Promise<void> {
    if (id === null || id === undefined || id === '') {
        foreignKeySelected[field.technicalName] = null
        return
    }

    const fk = parseForeignKeyOptions(field)
    if (!fk) return

    try {
        const record = await fetchRecordById(fk.targetEntity, Number(id))

        if (!record) return

        const option: EntityOption = {
            id: record.id,
            label: buildOptionLabel(record, fk.labelComposition),
        }

        foreignKeySelected[field.technicalName] = option

        if (!foreignKeyOptions[field.technicalName].some((o) => o.id === option.id)) {
            foreignKeyOptions[field.technicalName] = [...foreignKeyOptions[field.technicalName], option]
        }
    } catch (e) {
        console.error(`Error while loading foreign key label for field "${field.technicalName}":`, e)
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
                v-if="customField.type === 'text'"
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
                v-else-if="customField.type === 'int'"
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
                :addRecordFn="canAddRecord(customField) ? () => openAddDialog(customField) : undefined"
                @update:model-value="(val) => onForeignKeyInput(customField, val)"
                :field-error="errorFor(customField.technicalName)"
                @input="onFieldInput(customField.technicalName)"
                v-else-if="customField.type === 'foreignKey'"
            />

            <SfxonEditorFormCheckbox
                :field="customField.technicalName"
                :id="`custom-field-${customField.technicalName}`"
                :label="customField.name"
                v-model="values[customField.technicalName]"
                :field-error="errorFor(customField.technicalName)"
                @input="onFieldInput(customField.technicalName)"
                v-else-if="customField.type === 'bool'"
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
                v-else-if="customField.type === 'imageFile'"
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

        <SfxonEntityDialog
            :relations="addRelations"
            :entity-name="addDialogEntityName"
            :foreign-entity-config="addDialogForeignEntityConfig"
            @close="closeAddDialog"
            @saved="onRecordAdded"
        />
    </div>
</template>

<style module>
.sfxonCustomFields {
    /*display: flex;
    flex-direction: column;
    gap: 8px;*/
}
</style>