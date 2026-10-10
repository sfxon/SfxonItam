<script setup lang="ts">
import { computed } from 'vue'
import { generateUrl } from '@nextcloud/router'
import { translate as t } from '@nextcloud/l10n'
import { deleteQuantityUnit, fetchQuantityUnits } from '@/services/QuantityUnitService'
import type { QuantityUnit } from '@/services/QuantityUnitService'
import SfxonColumnOrderModal from '@/components/SfxonColumnOrderModal'
import SfxonConfirmDialog from '@/components/SfxonConfirmDialog'
import SfxonFilterBar from '@/components/SfxonFilterBar'
import SfxonListLayout from '@/components/SfxonListLayout'
import SfxonPagination from '@/components/SfxonPagination'
import SfxonTable from '@/components/SfxonTable'
import type { BreadcrumbItem } from '@/components/SfxonItamHeaderBc'
import { createListColumns } from '@/composables/createListColumns'
import { customFieldColumns, withCustomFieldValues } from '@/composables/customFieldColumns'
import type { CustomField } from '@/composables/customFieldColumns'
import { useColumnOrder } from '@/composables/useColumnOrder'
import { useEntityList } from '@/composables/useEntityList'
import { useListViewUiState } from '@/composables/useListViewUiState'
import type { CellMounted } from '@/composables/useMediaPreview'

const props = defineProps<{
    entityDefinitions?: Record<string, unknown>
    customFields?: CustomField[]
}>()
const VIEW_ID = 'quantity-unit-list'
const quantityUnitUrl = (quantityUnit: Pick<QuantityUnit, 'id'>) => generateUrl(`/apps/sfxonitam/quantity-unit/detail?quantityUnitId=${quantityUnit.id}`)
const breadcrumbs = computed<BreadcrumbItem[]>(() => [{
    label: t('sfxonitam', 'Quantity Unit'),
    link: generateUrl('/apps/sfxonitam/quantity-unit'),
    forceIconText: true,
    disableDrop: true,
}])
const { filterSidebarOpen } = useListViewUiState(VIEW_ID)
const {
    listState,
    items: quantityUnits,
    loading,
    error,
    filterValues,
    applyFilters,
    itemToDelete: quantityUnitToDelete,
    requestDelete,
    cancelDelete,
    confirmDelete,
    relatedEntityData,
    relatedEntitySearchFns,
    relatedEntityOf,
    detailUrl,
} = useEntityList<QuantityUnit>({
    fetch: fetchQuantityUnits,
    remove: deleteQuantityUnit,
    mapRow: (row) => withCustomFieldValues(row, props.customFields ?? []),
    errorMessage: t('sfxonitam', 'Error on loading quantity units.'),
})
const deleteMessage = computed(() => t('sfxonitam', 'Delete entry „{name}"?', {
    name: quantityUnitToDelete.value?.name ?? '',
}))
const noopCellMounted: CellMounted = () => () => {}
const col = createListColumns({
    relatedEntityOf,
    detailUrl,
    rowUrl: quantityUnitUrl,
    cellMounted: noopCellMounted,
})
const staticColumns = [
    col.text('name', t('sfxonitam', 'Name')),
    col.text('comment', t('sfxonitam', 'Comment'), { sortable: false }),
    col.actions(t('sfxonitam', 'Action')),
]
const columns = computed(() => [
    ...staticColumns,
    ...customFieldColumns(props.customFields ?? [], noopCellMounted, quantityUnitUrl),
])
const defaultColumns = ['name', 'comment', '#actions']
const { orderedColumns, showModal: columnOrderModalOpen, onSaved } = useColumnOrder(VIEW_ID, columns.value, defaultColumns)
const filterFields = [
    col.textFilter('name', t('sfxonitam', 'Name')),
]

function addItem() {
    window.location.href = generateUrl('/apps/sfxonitam/quantity-unit/detail')
}

function onEditQuantityUnit(quantityUnit: QuantityUnit) {
    window.location.href = quantityUnitUrl(quantityUnit)
}
</script>

<template>
    <SfxonListLayout
        v-model:filterSidebarOpen="filterSidebarOpen"
        :breadcrumbs="breadcrumbs"
        current-page="quantityUnits"
        :add-label="t('sfxonitam', 'Add Quantity Unit')"
        :loading="loading"
        :error="error"
        @add="addItem"
        @edit-columns="columnOrderModalOpen = true"
    >
        <SfxonTable
            :columns="orderedColumns"
            :dataArray="quantityUnits"
            :dataArrayKey="'id'"
            :deleteCallback="requestDelete"
            :editCallback="onEditQuantityUnit"
            :listState="listState"
            :orderByCallback="listState.sortBy"
            :relatedEntityData="relatedEntityData"
        />

        <SfxonPagination
            v-model:page="listState.page"
            :listState="listState"
        />

        <template #filter>
            <SfxonFilterBar
                v-model:filterSidebarOpen="filterSidebarOpen"
                :filterFields="filterFields"
                :filterValues="filterValues"
                :onFilterBtn="applyFilters"
                :relatedEntityData="relatedEntityData"
                :relatedEntitySearchFns="relatedEntitySearchFns"
            />
        </template>

        <template #dialogs>
            <SfxonConfirmDialog
                :open="!!quantityUnitToDelete"
                :title="t('sfxonitam', 'Delete quantity unit')"
                :message="deleteMessage"
                :confirm-label="t('sfxonitam', 'Delete')"
                @confirm="confirmDelete"
                @cancel="cancelDelete"
            />

            <SfxonColumnOrderModal
                :active-columns="orderedColumns"
                :all-columns="columns"
                :default-columns="defaultColumns"
                :list-id="VIEW_ID"
                :show="columnOrderModalOpen"
                @close="columnOrderModalOpen = false"
                @saved="onSaved"
            />
        </template>
    </SfxonListLayout>
</template>