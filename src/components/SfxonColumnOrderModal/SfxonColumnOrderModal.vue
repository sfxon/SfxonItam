<script setup lang="ts">

import { ref, watch } from 'vue'
import { mdiArrowDown, mdiArrowRight, mdiArrowUp, mdiClose } from '@mdi/js'
import NcModal from '@nextcloud/vue/components/NcModal'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import { translate as t } from '@nextcloud/l10n'
import { saveColumnOrder } from '@/services/ListViewSettings'

type Column = { key: string; label: string }

const props = defineProps<{
    activeColumns: Column[]
    allColumns: Column[]
    defaultColumns: string[]
    listId: string
    show: boolean
}>()

const emit = defineEmits<{
    close: []
    saved: [columns: Column[]]
}>()

const localActiveColumns = ref<Column[]>([])
const localInactiveColumns = ref<Column[]>([])
const isSaving = ref(false)

function resetLocalState() {
    const activeKeys = new Set(props.activeColumns.map(c => c.key))

    localActiveColumns.value = props.activeColumns.map(c => ({ ...c }))
    localInactiveColumns.value = props.allColumns
        .filter(c => !activeKeys.has(c.key))
        .map(c => ({ ...c }))
        .sort((a, b) => a.label.localeCompare(b.label))
}

watch(() => [props.allColumns, props.activeColumns], resetLocalState, { deep: true })

watch(() => props.show, (isOpen) => {
    if (isOpen) {
        resetLocalState()
    }
})

function activateColumn(index: number) {
    const column = localInactiveColumns.value.splice(index, 1)[0]

    if (column) {
        localActiveColumns.value.push(column)
    }
}

function deactivateColumn(index: number) {
    const column = localActiveColumns.value.splice(index, 1)[0]

    if (!column) {
        return
    }

    const insertIndex = localInactiveColumns.value
        .findIndex(c => c.label.localeCompare(column.label) > 0)

    if (insertIndex === -1) {
        localInactiveColumns.value.push(column)
    } else {
        localInactiveColumns.value.splice(insertIndex, 0, column)
    }
}

function moveItemUp(index: number) {
    if (index <= 0) {
        return
    }

    const items = localActiveColumns.value
    const temp = items[index]
    items[index] = items[index - 1]
    items[index - 1] = temp
}

function moveItemDown(index: number) {
    const items = localActiveColumns.value

    if (index >= items.length - 1) {
        return
    }

    const temp = items[index]
    items[index] = items[index + 1]
    items[index + 1] = temp
}

function resetToDefaultLayout() {
    console.log('defaultColumns', props.defaultColumns)
    console.log('allColumns: ', props.allColumns)

    const columnsByKey = new Map(props.allColumns.map(c => [c.key, c]))
    const defaultActive = props.defaultColumns
        .map(key => columnsByKey.get(key))
        .filter((c): c is Column => c !== undefined)
        .map(c => ({ ...c }))
    const defaultKeys = new Set(defaultActive.map(c => c.key))

    localActiveColumns.value = defaultActive
    localInactiveColumns.value = props.allColumns
        .filter(c => !defaultKeys.has(c.key))
        .map(c => ({ ...c }))
        .sort((a, b) => a.label.localeCompare(b.label))
}

async function save() {
    isSaving.value = true

    try {
        await saveColumnOrder(props.listId, localActiveColumns.value.map(c => c.key))
        emit('saved', localActiveColumns.value)
        emit('close')
    } finally {
        isSaving.value = false
    }
}
</script>

<template>
    <NcModal v-if="show" size="large" @close="$emit('close')">
        <div :class="$style.columnOrderModal">
            <h2>{{ t('sfxonitam', 'Set column order') }}</h2>

            <div :class="$style.panels">
                <!-- Links: deaktivierte, aber verf�gbare Spalten -->
                <div :class="$style.panel">
                    <h3 :class="$style.panelTitle">
                        {{ t('sfxonitam', 'Available columns') }}
                    </h3>
                    <div :class="$style.panelList">
                        <div v-if="localInactiveColumns.length === 0" :class="$style.emptyState">
                            {{ t('sfxonitam', 'Alle Spalten sind aktiv') }}
                        </div>
                        <div
                            v-for="(column, index) in localInactiveColumns"
                            :key="column.key"
                            :class="$style.compositionItem"
                        >
                            <span :class="$style.itemContent">
                                {{ column.label }}
                            </span>
                            <div :class="$style.itemControls">
                                <NcButton
                                    variant="tertiary"
                                    :aria-label="t('sfxonitam', 'Add column')"
                                    :class="$style.sortBtn"
                                    @click="activateColumn(index)"
                                >
                                    <template #icon>
                                        <NcIconSvgWrapper :path="mdiArrowRight" :size="16" />
                                    </template>
                                </NcButton>
                            </div>
                        </div>
                    </div>
                </div>

                <div :class="$style.panel">
                    <h3 :class="$style.panelTitle">
                        {{ t('sfxonitam', 'Active columns') }}
                    </h3>
                    <div :class="$style.panelList">
                        <div v-if="localActiveColumns.length === 0" :class="$style.emptyState">
                            {{ t('sfxonitam', 'Keine Spalten aktiv') }}
                        </div>
                        <div
                            v-for="(column, index) in localActiveColumns"
                            :key="column.key"
                            :class="$style.compositionItem"
                        >
                            <span :class="$style.itemContent">
                                {{ column.label }}
                            </span>
                            <div :class="$style.sortControls">
                                <NcButton
                                    variant="tertiary"
                                    :disabled="index === 0"
                                    :aria-label="t('sfxonitam', 'Move up')"
                                    :class="$style.sortBtn"
                                    @click="moveItemUp(index)"
                                >
                                    <template #icon>
                                        <NcIconSvgWrapper :path="mdiArrowUp" :size="16" />
                                    </template>
                                </NcButton>
                                <NcButton
                                    variant="tertiary"
                                    :disabled="index === localActiveColumns.length - 1"
                                    :aria-label="t('sfxonitam', 'Move down')"
                                    :class="$style.sortBtn"
                                    @click="moveItemDown(index)"
                                >
                                    <template #icon>
                                        <NcIconSvgWrapper :path="mdiArrowDown" :size="16" />
                                    </template>
                                </NcButton>
                                <NcButton
                                    variant="tertiary"
                                    :aria-label="t('sfxonitam', 'Remove column')"
                                    :class="$style.sortBtn"
                                    @click="deactivateColumn(index)"
                                >
                                    <template #icon>
                                        <NcIconSvgWrapper :path="mdiClose" :size="16" />
                                    </template>
                                </NcButton>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div :class="$style.actions">
                <NcButton variant="tertiary" :class="$style.resetToDefaultLayoutButton" @click="resetToDefaultLayout">
                    {{  t('sfxonitam', 'Reset to default') }}
                </NcButton>

                <NcButton variant="primary" :disabled="isSaving" @click="save">
                    {{ t('sfxonitam', 'Save') }}
                </NcButton>
                <NcButton variant="tertiary" @click="$emit('close')">
                    {{ t('sfxonitam', 'Cancel') }}
                </NcButton>
            </div>
        </div>
    </NcModal>
</template>

<style module>
.columnOrderModal {
    display: flex;
    flex-direction: column;
    gap: 16px;
    padding: 20px;
    max-height: 80vh;
}

.panels {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
    min-height: 0;
}

.panel {
    display: flex;
    flex-direction: column;
    gap: 8px;
    min-height: 0;
    min-width: 0;
}

.panelTitle {
    margin: 0;
    font-size: 0.9rem;
    font-weight: bold;
    color: var(--color-text-maxcontrast);
}

.panelList {
    display: flex;
    flex-direction: column;
    gap: 6px;
    max-height: 45vh;
    overflow-y: auto;
    padding-right: 4px;
}

.compositionItem {
    align-items: center;
    background: var(--color-background-dark, #f0f0f0);
    border: 1px solid var(--color-border);
    border-radius: var(--border-radius, 6px);
    display: flex;
    flex-shrink: 0;
    gap: 8px;
    padding: 6px 10px;
    transition: background 0.15s ease;
}

.compositionItem:hover {
    background: var(--color-background-hover);
}

.itemContent {
    flex: 1;
    min-width: 0;
}

.itemControls {
    display: flex;
    flex-direction: row;
    gap: 1px;
}

.sortControls {
    display: flex;
    flex-direction: row;
    gap: 1px;
    opacity: 0;
    transition: opacity 0.15s ease;
}

.compositionItem:hover .sortControls {
    opacity: 1;
}

.sortBtn {
    height: 22px !important;
    min-height: 22px !important;
    padding: 2px !important;
}

.sortBtn:disabled {
    opacity: 0.2;
}

.emptyState {
    align-items: center;
    border: 1px dashed var(--color-border);
    border-radius: var(--border-radius, 6px);
    color: var(--color-text-lighter);
    display: flex;
    flex-shrink: 0;
    justify-content: center;
    min-height: 44px;
    padding: 8px;
}

.actions {
    display: flex;
    justify-content: flex-end;
    gap: 8px;
    margin-top: 8px;
    flex-shrink: 0;
}

@media (max-width: 700px) {
    .panels {
        grid-template-columns: 1fr;
    }

    .columnOrderModal {
        max-height: calc(100vh - 48px);
    }

    .panelList {
        max-height: 30vh;
    }
}

@media (hover: none) {
    .sortControls {
        opacity: 1;
    }
}

.resetToDefaultLayoutButton {
    margin-left: 0;
    margin-right: auto;
}
</style>