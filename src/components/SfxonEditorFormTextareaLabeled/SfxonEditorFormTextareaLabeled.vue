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
    <div>
        <div :class="SfxonEditorStyles.sfxonFormColumnRow">
            <div :class="[SfxonEditorStyles.sfxonFormColumnLabel, $style.formFieldLabel]">
                <label :for="id" :class="SfxonEditorStyles.label">{{ label }}</label>
            </div>
            <div :class="SfxonEditorStyles.sfxonFormColumnInput">
                <div :class="SfxonEditorStyles.field">
                    <NcTextArea
                        :id="id"
                        :model-value="modelValue"
                        :label-outside="true"
                        :class="[fieldError ? SfxonEditorStyles.fieldError : '', SfxonEditorStyles.sfxonDescriptionInput, $style.myDescriptionInput]"
                        @update:modelValue="onInput"
                    >
                    </NcTextArea>
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
    </div>
</template>

<style module>
.formFieldLabel {
    justify-content: start!important;
}

.formFieldLabel label {
    padding-top: 11px;
}

.myDescriptionInput {
    height: auto!important;
    padding-top: 2px!important;
    padding-bottom: 2px!important;
    
}
</style>