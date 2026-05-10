<script setup lang="ts">
import { computed } from 'vue'
import { translate as t } from '@nextcloud/l10n'
import NcDateTimePickerNative from '@nextcloud/vue/components/NcDateTimePickerNative'

// When sending the data, the mapping function expectes data in this form: name: [{value: "my input value"}].
// To have this consistent over the different SfxonFilterField Input-Types, we had do make it a little bit more complex here.
// So the simple string is kept internally here - but the value is put out in the correct format in the format described above.
const props = defineProps<{
    labelFrom?: string
    labelTo?: string
    modelValue: { value: string }[]
}>()
const emit = defineEmits(['update:modelValue'])

const inputValue = computed({
    get: () => props.modelValue?.[0]?.value ?? '',
    set: (val: string) => {
        emit('update:modelValue', val ? [{ value: val }] : [])
    }
})

function emitDates(from: string, to: string) {
    const result: { value: string }[] = []

    if(from) {
        result[0] = { value: from }
    }

    if(to) {
        result[1] = { value: to }
    }

    emit('update:modelValue', result)
}

function fromSqlDate(val: string | undefined): Date | null {
    if(!val) {
        return null
    }

    const d = new Date(val + 'T00:00:00')
    return isNaN(d.getTime()) ? null: d
}

function toSqlDate(date: Date | null | undefined): string {
    if(!date) {
        return ''
    }

    const y = date.getFullYear()
    const m = String(date.getMonth() + 1).padStart(2, '0')
    const d = String(date.getDate()).padStart(2, '0')

    return `${y}-${m}-${d}`
}

const dateFrom = computed({
    get: () => fromSqlDate(props.modelValue?.[0]?.value),
    set: (date: Date | null) => {
        const from = toSqlDate(date)
        const to = props.modelValue?.[1]?.value ?? ''
        emitDates(from, to)
    }
})

const dateTo = computed({
    get: () => fromSqlDate(props.modelValue?.[1]?.value),
    set: (date: Date | null) => {
        const from = props.modelValue?.[0]?.value ?? ''
        const to = toSqlDate(date)
        emitDates(from, to)
    }
})

const hasValue = computed(
    () => !!props.modelValue?.[0]?.value || !!props.modelValue?.[1]?.value
)
</script>
<template>
    <div :class="[$style.filterRow]">
        <NcDateTimePickerNative
            :class="[{ [$style.active]: hasValue }]"
            id="fromDate"
            v-model="dateFrom"
            :label="labelFrom"
            type="date" />
        <NcDateTimePickerNative
            :class="[{ [$style.active]: hasValue }]"
            id="toDate"
            v-model="dateTo"
            :label="labelTo"
            type="date" />
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
