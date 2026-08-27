<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import NcSelect from '@nextcloud/vue/components/NcSelect'
import { useSfxonEntitySearch } from '@/composables/useSfxonEntitySearch'

const props = withDefaults(defineProps<{
    label?: string
    modelValue?: { value: string }[]
    entityData: { id: number; label: string }[]
    searchFn?: (query: string, signal: AbortSignal) => Promise<void>
}>(), {
    modelValue: () => [],
    entityData: () => [],
})

const emit = defineEmits(['update:modelValue'])

// Caches known options, so that selected values always show valid texts.
const knownOptionsById = new Map<string, { id: number; label: string }>()
watch(() => props.entityData, (list) => {
    for (const entity of list) {
        knownOptionsById.set(String(entity.id), entity)
    }
}, { immediate: true, deep: true })

const selectedOptions = computed({
    get() {
        return props.modelValue
            .map(v => knownOptionsById.get(v.value))
            .filter((e): e is { id: number; label: string } => !!e)
    },
    set(selected: { id: number; label: string }[]) {
        for (const entity of selected ?? []) {
            knownOptionsById.set(String(entity.id), entity)
        }
        emit('update:modelValue', (selected ?? []).map(e => ({ value: String(e.id) })))
    },
})

const hasValue = computed(() => props.modelValue.length > 0)

const { isSearching, onSearch, onMouseEnter, onMouseLeave, triggerPreload } =
    useSfxonEntitySearch(props.searchFn)
</script>

<template>
    <div :class="[$style.filterRow]">
        <NcSelect
            :class="[{ [$style.active]: hasValue }]"
            :input-label="label"
            :loading="isSearching"
            :min-input-length="searchFn ? 2 : 0"
            :options="entityData"
            v-model="selectedOptions"
            label="label"
            :multiple="true"
            @focusin="triggerPreload"
            @mouseenter="onMouseEnter"
            @mouseleave="onMouseLeave"
            v-on="searchFn ? { search: onSearch } : {}"
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
