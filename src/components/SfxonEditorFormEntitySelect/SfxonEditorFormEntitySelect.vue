<script setup lang="ts">

import { mdiPlus } from '@mdi/js'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import NcSelect from '@nextcloud/vue/components/NcSelect'
import { ref } from 'vue'
import SfxonEditorStyles from '@/components/SfxonEditor/SfxonEditor.module.css'

const isSearching = ref(false)
const preloaded = ref<boolean>(false)
const hoverTimeout = ref<ReturnType<typeof setTimeout> | null>(null)
const props = defineProps<{
    addRecordFn?: () => void,
    field: string,
    fieldError: string,
    id: string,
    label: string,
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

function onMouseEnter() {
    hoverTimeout.value = setTimeout(triggerPreload, 150)
}

function onMouseLeave() {
    if (hoverTimeout.value) clearTimeout(hoverTimeout.value)
}

// -- Async Search Handler --
// The AbortController helps to avoid problems with fast paced loads,
// for example, when the server is slow, and the user types fast,
// and would trigger multiple loads, this could lead to false results.
// To avoid this completely, we dismiss a prior load, and only accept the one,
// that reallly was the last one.
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
        isSearching.value = true
        await props.searchFn!(query, abortController.value.signal)
        isSearching.value = false
    }, 300)
}

// Prefills the select with up to 20 values.
// Improves smootheness of the components.
function triggerPreload() {
    if (preloaded.value || !props.searchFn) {
        return
    }

    preloaded.value = true
    const ctrl = new AbortController()
    props.searchFn!('', ctrl.signal)
}

function onClickAddIcon() {
    alert('test')
}

</script>
<template>
    <div :class="SfxonEditorStyles.sfxonFormColumnRow">
        <div :class="SfxonEditorStyles.sfxonFormColumnLabel">
            <label :for="id" :class="SfxonEditorStyles.label">
                {{ label }}

                <span
                    v-if="addRecordFn !== null && typeof(addRecordFn) === 'function'"
                    :class="$style.addNewEntityButton"
                    @click="addRecordFn?.()"
                >
                    <NcIconSvgWrapper :path="mdiPlus" :size="16" />
                </span>
            </label>
        </div>
        <div :class="SfxonEditorStyles.sfxonFormColumnInput">
            <div :class="SfxonEditorStyles.field">
                <NcSelect
                    :class="fieldError ? SfxonEditorStyles.fieldError : ''"
                    @focusin="triggerPreload"
                    :id="id"
                    :loading="isSearching"
                    :min-input-length="2"
                    :model-value="modelValue"
                    @mouseenter="onMouseEnter"
                    @mouseleave="onMouseLeave"
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
.addNewEntityButton {
    color: var(--color-primary-element);
    display: inline-block;
}

.addNewEntityButton :global(.icon-vue) {
    cursor: pointer!important;
}
</style>