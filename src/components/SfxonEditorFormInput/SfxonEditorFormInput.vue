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
    type: "number" | "text" | "password" | "email" | "tel" | "url" | "search" | undefined,
}>()

const emit = defineEmits<{
  (e: 'update:modelValue', value: string): void
  (e: 'input', field: string): void
}>()

function onInput(value: string | number) {
    emit('update:modelValue', String(value))
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
                    :type="type"
                />
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
</style>