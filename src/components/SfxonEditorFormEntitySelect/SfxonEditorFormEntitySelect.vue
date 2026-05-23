<script setup lang="ts">

import NcSelect from '@nextcloud/vue/components/NcSelect'
import SfxonEditorStyles from '@/components/SfxonEditor/SfxonEditor.module.css'

const props = defineProps<{
    field: string,
    fieldError: string,
    id: string,
    label: string,
    loading: any,
    modelValue: Record<string, unknown> | null,
    options: [],
    placeholder?: string,
    trackBy: string
}>()

const emit = defineEmits<{
  (e: 'update:modelValue', value: Record<string, unknown> | null): void
  (e: 'input', field: string): void
}>()

function onInput(value: Record<string, unknown> | null) {
    emit('update:modelValue', value)
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
                <NcSelect
                    :id="id"
                    :model-value="modelValue"
                    :options="options"
                    :loading="loading"
                    :track-by="trackBy"
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