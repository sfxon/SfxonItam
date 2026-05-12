<!-- SfxonFilterFieldNumericFromTo.vue -->
<script setup lang="ts">
import { computed } from 'vue'
import NcTextField from '@nextcloud/vue/components/NcTextField'

const props = defineProps<{
    labelFrom?: string
    labelTo?: string
    modelValue: { value: string }[]
}>()

const emit = defineEmits(['update:modelValue'])

function emitValues(from: string, to: string) {
    const result: { value: string }[] = []
    if (from !== '') {
        result[0] = { value: from }
    }
    if (to !== '') {
        result[1] = { value: to }
    }
    emit('update:modelValue', result)
}

const numericFrom = computed({
    get: () => props.modelValue?.[0]?.value ?? '',
    set: (val: string) => {
        const to = props.modelValue?.[1]?.value ?? ''
        emitValues(val, to)
    }
})

const numericTo = computed({
    get: () => props.modelValue?.[1]?.value ?? '',
    set: (val: string) => {
        const from = props.modelValue?.[0]?.value ?? ''
        emitValues(from, val)
    }
})

const hasValue = computed(
    () => !!props.modelValue?.[0]?.value || !!props.modelValue?.[1]?.value
)
</script>

<template>
    <div :class="[$style.filterRow]">
        <label for="textField">{{ labelFrom }}</label>
        <NcTextField
            id="numericFrom"
            :label-outside="true"
            type="number"
            v-model="numericFrom"
        />
        <label for="textField">{{ labelTo }}</label>
        <NcTextField
            id="numericTo"
            :label-outside="true"
            type="number"
            v-model="numericTo"
        />
    </div>
</template>

<style module>
    .filterRow {
        border-bottom: 1px solid #EEE;
        padding-bottom: .5rem;
        margin-bottom: .5rem;
        transition: background-color 0.2s;
    }
    .active {
        background-color: rgb(231, 250, 205) !important;
    }
    :global(body[data-theme-dark]) .active {
        background-color: rgb(45, 63, 20) !important;
    }
</style>