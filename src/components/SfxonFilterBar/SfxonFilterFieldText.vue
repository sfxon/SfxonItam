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
        <NcTextField
            :class="[{ [$style.active]: hasValue }]"
            id="filterValuesName"
            v-model="inputValue"
            :label="label"
            :placeholder="t('sfxonitam', 'e.g. JP001')" />
    </div>
</template>
<style module>
    .filterRow {
        border-bottom: 1px solid #EEE;
        padding-bottom: .5rem;
        margin-bottom: .5rem;
        border-radius: 6px;
        transition: background-color 0.2s;
    }
    .active input {
        background-color: rgb(231, 250, 205)!important;
    }
</style>
