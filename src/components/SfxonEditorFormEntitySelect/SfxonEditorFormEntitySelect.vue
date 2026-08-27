<script setup lang="ts">

import { mdiPlus } from '@mdi/js'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import NcSelect from '@nextcloud/vue/components/NcSelect'
import SfxonEditorStyles from '@/components/SfxonEditor/SfxonEditor.module.css'
import { translate as t } from '@nextcloud/l10n'
import { useSfxonEntitySearch } from '@/composables/useSfxonEntitySearch'

const props = withDefaults(defineProps<{
    addRecordFn?: () => void,
    disabled?: boolean,
    field: string,
    fieldError: string,
    id: string,
    label: string,
    modelValue: Record<string, unknown> | null,
    options: [],
    placeholder?: string,
    readonly?: boolean,
    searchFn?: (query: string, signal: AbortSignal) => Promise<void>,
    trackBy: string
}>(), {
    disabled: false,
    readonly: false,
})

const emit = defineEmits<{
  (e: 'update:modelValue', value: Record<string, unknown> | null): void
  (e: 'input', field: string): void
}>()

function onInput(value: Record<string, unknown> | null) {
    if (props.disabled || props.readonly) {
        return
    }

    emit('update:modelValue', value)
    emit('input', props.field)
}

const {
    isSearching,
    onSearch: baseOnSearch,
    onMouseEnter: baseOnMouseEnter,
    onMouseLeave,
    triggerPreload: baseTriggerPreload,
} = useSfxonEntitySearch(props.searchFn)

async function onSearch(query: string) {
    if (props.disabled || props.readonly) {
        return
    }

    baseOnSearch(query)
}

function onMouseEnter() {
    if (props.disabled || props.readonly) {
        return
    }

    baseOnMouseEnter()
}

function triggerPreload() {
    if (props.disabled || props.readonly) {
        return
    }

    baseTriggerPreload()
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
                    :clearable="!disabled && !readonly"
                    :disabled="disabled"
                    :id="id"
                    @keydown.alt.n.prevent="!disabled && !readonly ? addRecordFn?.() : null"
                    :loading="isSearching"
                    :min-input-length="2"
                    :model-value="modelValue"
                    @mouseenter="onMouseEnter"
                    @mouseleave="onMouseLeave"
                    :open="readonly ? false : undefined"
                    :options="options"
                    :title="t('sfxonitam', 'Add new entry (Alt+N)')"
                    :track-by="trackBy"
                    v-on="searchFn && !disabled && !readonly ? { search: onSearch } : {}"
                    @update:modelValue="onInput" />
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

<style>
/* Change the border-radius of the select dropdown */
.vs__dropdown-menu--floating {
    border-bottom-left-radius: 3px !important;
    border-bottom-right-radius: 3px !important;
}


.vs__dropdown-toggle {
    min-width: 0;
    display: flex;
}

.vs__selected-options {
    min-width: 0;
    flex-wrap: nowrap;
    overflow: hidden;
}

.vs__selected {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    max-width: 100%;
    min-width: 0;
}

.vs__search {
    min-width: 0 !important;
}
</style>

<style module>
.addNewEntityButton {
    color: var(--color-primary-element);
    display: inline-block;
}

.addNewEntityButton :global(.icon-vue) {
    cursor: pointer!important;
}
</style>