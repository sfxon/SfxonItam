<script setup lang="ts">

import { useAttrs } from 'vue'
import NcCheckboxRadioSwitch from '@nextcloud/vue/components/NcCheckboxRadioSwitch'
import SfxonEditorStyles from '@/components/SfxonEditor/SfxonEditor.module.css'

defineOptions({
  inheritAttrs: false,
})

const props = defineProps<{
    field: string,
    fieldError: string,
    id: string,
    label: string,
    modelValue: boolean,
}>()

const emit = defineEmits<{
  (e: 'update:modelValue', value: boolean): void
  (e: 'input', field: string): void
}>()

const attrs = useAttrs()

function onInput(value: boolean) {
    emit('update:modelValue', value)
    emit('input', props.field)
}

</script>
<template>
    <div :class="SfxonEditorStyles.sfxonFormColumnRow">
        <div :class="SfxonEditorStyles.sfxonFormColumnLabel">
            &nbsp;
        </div>
        <div :class="SfxonEditorStyles.sfxonFormColumnInput">
            <div :class="[SfxonEditorStyles.field, $style.switchFieldContainer]">
                <!-- v-bind="attrs" must be at first position, to avoid overriding of other properties -->
                <NcCheckboxRadioSwitch
                    v-bind="attrs"
                    :id="id"
                    :model-value="modelValue"
                    type="switch"
                    :class="[$style.switchField, fieldError ? SfxonEditorStyles.fieldError : '']"
                    @update:modelValue="onInput"
                >
                    {{ label }}
                </NcCheckboxRadioSwitch>
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
.switchField {
    margin: 0 0 var(--default-grid-baseline);
}

.switchFieldContainer :global(.checkbox-radio-switch__content) {
    background-color: transparent!important;
    padding-left: 0!important;
}
</style>