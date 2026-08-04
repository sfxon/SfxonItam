<script setup lang="ts">

import { computed } from 'vue'
import { mdiClose } from '@mdi/js'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcDateTimePickerNative from '@nextcloud/vue/components/NcDateTimePickerNative'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import SfxonEditorStyles from '@/components/SfxonEditor/SfxonEditor.module.css'
import { translate as t } from '@nextcloud/l10n'

const props = withDefaults(defineProps<{
    field: string,
    fieldError: string,
    id: string,
    label: string,
    modelValue: Date | null | undefined,
    placeholder: string | undefined,
    type?: 'date' | 'datetime'
}>(), {
    type: 'date',
})

const emit = defineEmits<{
    (e: 'update:modelValue', value: Date | null): void
    (e: 'input', field: string): void
}>()

const nativeType = computed(() => (props.type === 'datetime' ? 'datetime-local' : 'date'))

function onInput(value: Date | null) {
    emit('update:modelValue', value)
    emit('input', props.field)
}

function onClear() {
    emit('update:modelValue', null)
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
                <div :class="SfxonEditorStyles.dateRow">
                    <NcDateTimePickerNative
                        :class="[SfxonEditorStyles.sfxonEditorFormDatePicker, fieldError ? SfxonEditorStyles.fieldError : '']"
                        :id="id"
                        :label="''"
                        :model-value="modelValue"
                        :placeholder="placeholder"
                        :type="nativeType"
                        @update:modelValue="onInput"
                    />
                    <NcButton
                        :class="SfxonEditorStyles.sfxonEditorFormDatePickerBtn"
                        :disabled="modelValue === null || modelValue === undefined"
                        type="button"
                        :aria-label="t('sfxonitam', 'Clear date')"
                        @click="onClear"
                        >
                        <NcIconSvgWrapper
                            :path="mdiClose"
                            :size="16"
                        />
                    </NcButton>
                </div>
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