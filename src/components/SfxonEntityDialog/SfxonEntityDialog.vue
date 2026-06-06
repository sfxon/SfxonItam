<script setup lang="ts">

import { ref, watch } from 'vue'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcDialog from '@nextcloud/vue/components/NcDialog'
import NcNoteCard from '@nextcloud/vue/components/NcNoteCard'
import { NcLoadingIcon } from '@nextcloud/vue'
import SfxonEditorFormInput from '@/components/SfxonEditorFormInput'
import { translate as t } from '@nextcloud/l10n'

const props = defineProps<{
    relations: Record<string, any>
    entityName: string
}>()
const emit = defineEmits<{
    (e: 'close'): void
    (e: 'saved', payload: { entityName: string; newOption: { id: string; label: string } }): void
}>()
const dialogError = ref('')
const fieldErrors = ref<Record<string, Record<string, string>>>({})
const isSaving = ref(false)
const heading = ref('')

watch(
    () => props.entityName,
    (name) => {
        // Keep heading in sync with entityName prop.
        if (name && props.relations[name]) {
            heading.value = props.relations[name].addRecordModal?.heading ?? ''
        }
        // Reset errors whenever a new dialog opens.
        resetErrors()
    },
    { immediate: true }
)

function resetErrors() {
    dialogError.value = ''
    fieldErrors.value = {}
}

function close() {
    resetErrors()
    emit('close')
}

async function onSave() {
    const entityName = props.entityName
    const relation = props.relations[entityName]

    if (!relation) {
        return
    }

    const addRecordModal = relation.addRecordModal
    isSaving.value = true
    resetErrors()

    const payload: Record<string, string> = {}

    for (const field of addRecordModal.fields) {
        payload[field.fieldName] = field.value
    }

    try {
        const result = await addRecordModal.saveFunction(payload)

        // Build label from the configured labelFields.
        const labelDef = relation.labelFields ?? { fields: ['name'] }
        const labelParts = labelDef.fields.map(
            (fieldName: string) => addRecordModal.fields.find((f: any) => f.fieldName === fieldName)?.value ?? ''
        )
        const label = labelParts.filter(Boolean).join(labelDef.separator ?? ' ')

        const newOption = { id: String(result.id), label }

        // Clear form fields after successful save.
        for (const field of addRecordModal.fields) {
            field.value = ''
        }

        emit('saved', { entityName, newOption })
        close()
    } catch (error: any) {
        isSaving.value = false

        const responseData = error?.response?.data

        if (responseData?.status === 'error' && responseData?.errors) {
            fieldErrors.value[entityName] = responseData.errors
            dialogError.value = t('sfxon', 'Please check your input.')
        } else {
            dialogError.value = t('sfxon', 'An error occurred. Please try again. For further details, check the logs. For professional support, the Oishi Team is happy to help.')
            console.error(error?.stack)
            throw error
        }
    } finally {
        isSaving.value = false
    }
}
</script>

<template>
    <NcDialog
        :class="$style.sfxonEntityDialog"
        v-if="entityName"
        :name="heading"
        :open="!!entityName"
        @closing="close"
    >
        <!-- General error message -->
        <div>
            <NcNoteCard
                v-if="dialogError"
                :class="$style.errorMsg"
                type="error"
            >
                {{ dialogError }}
            </NcNoteCard>
        </div>

        <template v-for="(relation, relEntityName) in relations" :key="relEntityName">
            <template v-if="relEntityName === entityName">
                <template
                    v-if="relation.addRecordModal?.fields"
                    v-for="field in relation.addRecordModal.fields"
                    :key="relEntityName + '--' + field.fieldName"
                >
                    <template v-if="field.sfxonType === 'SfxonEditorFormInput'">
                        <SfxonEditorFormInput
                            :field="'addRecordModal' + field.fieldName"
                            :fieldError="fieldErrors[relEntityName]?.[field.fieldName] ?? ''"
                            :id="'addRecordModal' + field.fieldName"
                            v-model="field.value"
                            :label="field.label"
                            :type="field.type"
                        />
                    </template>
                </template>
            </template>
        </template>
        <template #actions>
            <NcButton
                variant="tertiary"
                @click="close"
            >
                {{ t('sfxonitam', 'Cancel') }}
            </NcButton>
            <NcButton
                variant="primary"
                :disabled="isSaving"
                @click="onSave"
            >
                <NcLoadingIcon v-if="isSaving" :size="20" />
                <span v-else>{{ t('sfxonitam', 'Save') }}</span>
            </NcButton>
        </template>
    </NcDialog>
</template>

<style module>
.sfxonEntityDialog :global(.modal-container__content) :global(.dialog__content) {
    /* Important, because otherwise the top border of the first element will be not visible.
     * The reason for this behaviour is, that Nextcloud css uses box-shadows instead of border to display the border.
     */
    padding-top: 2px!important;
}

.errorMsg {
    margin-top: 0 !important;
}

.errorMsg :global(span.icon-vue) {
    align-self: start;
    padding-top: 3px;
}
</style>
