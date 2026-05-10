<script setup lang="ts">
import { computed } from 'vue'
import { translate as t } from '@nextcloud/l10n'
import NcTextField from '@nextcloud/vue/components/NcTextField'

// When sending the data, the mapping function expectes data in this form: name: [{value: "my input value"}].
// To have this consistent over the different SfxonFilterField Input-Types, we had do make it a little bit more complex here.
// So the simple string is kept internally here - but the value is put out in the correct format in the format described above.
const props = defineProps<{
    label?: string
    modelValue: { value: string }[] 
}>()
const emit = defineEmits(['update:modelValue'])

const inputValue = computed({
    get: () => props.modelValue?.[0]?.value ?? '',
    set: (val: string) => {
        emit('update:modelValue', val ? [{ value: val }] : [])
    }
})

const hasValue = computed(() => !!inputValue.value)
</script>
<template>
    <div :class="[$style.filterRow]">
        <label for="textField">{{ label }}</label>
        <NcTextField
            :class="[{ [$style.active]: hasValue }]"
            id="filterValuesName"
            v-model="inputValue"
            :label-outside="true" />
    </div>
</template>
<style module>
    .filterRow {
        border-bottom: 1px solid #EEE;
        padding-bottom: .5rem;
        margin-bottom: .5rem;
        transition: background-color 0.2s;
    }
    .active input {
        background-color: rgb(231, 250, 205)!important;
    }
    :global(body[data-theme-dark]) .active input {
        background-color: rgb(45, 63, 20) !important;
    }
</style>
