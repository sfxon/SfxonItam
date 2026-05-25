<script setup lang="ts">

import NcSelect from '@nextcloud/vue/components/NcSelect'
import { ref } from 'vue'
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
    searchFn?: (query: string, signal: AbortSignal) => Promise<void>,
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

// -- Async Search Handler --
// Note: No AbortController used intentionally.
// Debounce alone covers most cases. If race conditions
// become an issue (slow servers, high load), add AbortController
// pattern to searchFn + signal prop.
const searchTimeout = ref<ReturnType<typeof setTimeout> | null>(null)
const abortController = ref<AbortController | null>(null) // Aborts a previous call.

async function onSearch(query: string) {
    if (searchTimeout.value) {
        clearTimeout(searchTimeout.value)
    }

    // Abort previous call.
    if (abortController.value) {
        abortController.value.abort()
    }

    if(query === null || query.trim() === "") {
        return;
    }

    searchTimeout.value = setTimeout(async () => {
        abortController.value = new AbortController()
        await props.searchFn!(query, abortController.value.signal)
    }, 300)
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
                    :class="fieldError ? SfxonEditorStyles.fieldError : ''"
                    :id="id"
                    :loading="loading"
                    :min-input-length="2"
                    :model-value="modelValue"
                    :options="options"
                    v-on="searchFn ? { search: onSearch } : {}"
                    :track-by="trackBy"
                    @update:modelValue="onInput" />
                <span v-if="fieldError" :class="SfxonEditorStyles.errorText">
                    {{ fieldError }}
                </span>
            </div>
        </div>
    </div>
</template>

<style module>
</style>