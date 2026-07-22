<script setup lang="ts">

import { reactive, watch } from 'vue'
import SfxonEditorFormInput from '@/components/SfxonEditorFormInput'
import SfxonEditorFormTextareaLabeled from '@/components/SfxonEditorFormTextareaLabeled'
import SfxonEditorFormEntitySelect from '@/components/SfxonEditorFormEntitySelect'

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

interface EntityOption {
    id: string | number
    label: string
}

const props = defineProps<{
    customFields: CustomFieldDefinition[]
}>()

const emit = defineEmits<{
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

function parseForeignKeyOptions(field: CustomFieldDefinition): ForeignKeyOptions | null {
    if (!field.options) {
        return null
    }

    try {
        const parsed = JSON.parse(field.options)
        return parsed.foreignKey ?? null
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

props.customFields.forEach((field) => {
    if (field.type === 'foreign_key') {
        foreignKeyOptions[field.technicalName] = []
        foreignKeySelected[field.technicalName] = null
        values[field.technicalName] = null
        return
    }

    if (!(field.technicalName in values)) {
        values[field.technicalName] = ''
    }
})

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

watch(values, (v) => emit('update:values', { ...v }), { deep: true })
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
                v-if="customField.type === 'longtext'"
            />

            <SfxonEditorFormInput
                inputmode="numeric"
                :field="customField.technicalName"
                :id="`custom-field-${customField.technicalName}`"
                :label="customField.name + ':'"
                v-model="values[customField.technicalName]"
                type="text"
                v-else-if="customField.type === 'integer'"
            />

            <SfxonEditorFormInput
                inputmode="decimal"
                :field="customField.technicalName"
                :id="`custom-field-${customField.technicalName}`"
                :label="customField.name + ':'"
                v-model="values[customField.technicalName]"
                type="text"
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
                v-else-if="customField.type === 'foreign_key'"
            />

            <SfxonEditorFormInput
                :field="customField.technicalName"
                :id="`custom-field-${customField.technicalName}`"
                :label="customField.name + ':'"
                v-model="values[customField.technicalName]"
                type="text"
                v-else
            />
        </template>
    </div>
</template>

<style module>
.sfxonCustomFields {
    display: flex;
    flex-direction: column;
    gap: 8px;
}
</style>