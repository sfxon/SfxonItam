<script setup lang="ts">
import { computed } from 'vue'
import NcSelect from '@nextcloud/vue/components/NcSelect'

const props = withDefaults(defineProps<{
    label?: string
    modelValue?: { value: string }[]
    entityData: { id: number; label: string }[]
}>(), {
    modelValue: () => [],
    entityData: () => [],
})

const emit = defineEmits(['update:modelValue'])

const selectedOptions = computed({
    get() {
        return props.entityData.filter(e =>
            props.modelValue.some(v => v.value === String(e.id))
        )
    },
    set(selected: { id: number; label: string }[]) {
        emit('update:modelValue', (selected ?? []).map(e => ({ value: String(e.id) })))
    },
})

const hasValue = computed(() => props.modelValue.length > 0)
</script>

<template>
    <div :class="[$style.filterRow]">
        <NcSelect
            :class="[{ [$style.active]: hasValue }]"
            :input-label="label"
            :options="entityData"
            v-model="selectedOptions"
            label="label"
            :multiple="true"
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

    .filterRow :global(.vs--searchable.select) {
        width: 100%!important;
    }

    .active :global(.vs__dropdown-toggle) {
        background-color: rgb(231, 250, 205)!important;
    }

    :global(body[data-theme-dark]) .active :global(.vs__dropdown-toggle) {
        background-color: rgb(45, 63, 20) !important;
    }
</style>
