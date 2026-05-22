<script setup lang="ts">

import NcTextField from '@nextcloud/vue/components/NcTextField'
import SfxonEditorStyles from '@/components/SfxonEditor/SfxonEditor.module.css'

const props = defineProps<{
    field: string,
    fieldError: string,
    id: string,
    label: string,
    modelValue: string,
    placeholder: string,
    type: string,
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
    <div :class="SfxonEditorStyles.sfxonFormColumnRow">
        <div :class="SfxonEditorStyles.sfxonFormColumnLabel">
            <label :for="id" :class="SfxonEditorStyles.label">{{ label }}</label>
        </div>
        <div :class="SfxonEditorStyles.sfxonFormColumnInput">
            <div :class="SfxonEditorStyles.field">
                <NcTextField
                    :id="id"
                    :model-value="modelValue"
                    :label-outside="true"
                    :placeholder="placeholder"
                    :class="fieldError ? SfxonEditorStyles.fieldError : ''"
                    @update:modelValue="onInput"
                />
                <span v-if="fieldError" :class="SfxonEditorStyles.errorText">
                    {{ fieldError }}
                </span>
            </div>
        </div>
    </div>
</template>

<style module>
</style>