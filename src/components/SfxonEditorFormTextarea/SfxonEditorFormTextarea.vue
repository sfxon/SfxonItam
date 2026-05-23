<script setup lang="ts">

import NcTextArea from '@nextcloud/vue/components/NcTextArea'
import SfxonEditorStyles from '@/components/SfxonEditor/SfxonEditor.module.css'

const props = defineProps<{
    field: string,
    fieldError: string,
    id: string,
    label: string,
    modelValue: string,
    placeholder: string,
}>()

const emit = defineEmits<{
  (e: 'update:modelValue', value: string): void
  (e: 'input', field: string): void
}>()

function onInput(value: string | Event) {
    const v =
        typeof value === 'string'
            ? value
            : (value.target as HTMLInputElement).value

    emit('update:modelValue', v)
    emit('input', props.field)
}

</script>
<template>
    <div :class="[SfxonEditorStyles.sfxonFormColumnRow, SfxonEditorStyles.sfxonFormColumnRowDescription]">
        <div :class="[SfxonEditorStyles.sfxonFormColumnInput, SfxonEditorStyles.sfxonFormColumnInputDescription]">
            <div :class="SfxonEditorStyles.field">
                <NcTextArea
                    :id="id"
                    :model-value="modelValue"
                    :label="label"
                    :class="[fieldError ? SfxonEditorStyles.fieldError : '', SfxonEditorStyles.sfxonDescriptionInput]"
                    @update:modelValue="onInput"
                >
                </NcTextArea>
                <span v-if="fieldError" :class="SfxonEditorStyles.errorText">
                    {{ fieldError }}
                </span>
            </div>
        </div>
    </div>
</template>

<style module>
</style>