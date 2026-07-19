<script setup lang="ts">

import { reactive } from 'vue'
import SfxonEditorFormInput from '@/components/SfxonEditorFormInput'
import SfxonEditorFormTextareaLabeled from '@/components/SfxonEditorFormTextareaLabeled'

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

const props = defineProps<{
    customFields: CustomFieldDefinition[]
}>()
const values = reactive<Record<string, string>>({})

props.customFields.forEach((field) => {
    if (!(field.technicalName in values)) {
        values[field.technicalName] = ''
    }
})
</script>

<template>
    <div v-if="customFields.length" :class="$style.sfxonCustomFields">
        <template
            v-for="customField in customFields"
            :key="customField.id"
        >
            <SfxonEditorFormTextareaLabeled
                :field="customField.technicalName"
                id="`custom-field-${customField.technicalName}`"
                :label="customField.name + ':'"
                v-model="values[customField.technicalName]"
                v-if="customField.type === 'longtext'"
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