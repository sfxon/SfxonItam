<script setup lang="ts">
import { computed } from 'vue'
import { generateUrl } from '@nextcloud/router'
import { translate as t } from '@nextcloud/l10n'
import { deleteDeviceType, fetchDeviceTypes } from '@/services/DeviceTypeService'
import type { DeviceType } from '@/services/DeviceTypeService'
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
const VIEW_ID = 'device-type-list'
const deviceTypeUrl = (deviceType: Pick<DeviceType, 'id'>) => generateUrl(`/apps/sfxonitam/device-type/detail?deviceTypeId=${deviceType.id}`)
const breadcrumbs = computed<BreadcrumbItem[]>(() => [{
    label: t('sfxonitam', 'Device Type'),
    link: generateUrl('/apps/sfxonitam/device-type'),
    forceIconText: true,
    disableDrop: true,
}])
const { filterSidebarOpen } = useListViewUiState(VIEW_ID)
const {
    listState,
    items: deviceTypes,
    loading,
    error,
    filterValues,
    applyFilters,
    itemToDelete: deviceTypeToDelete,
    requestDelete,
    cancelDelete,
    confirmDelete,
    relatedEntityData,
    relatedEntitySearchFns,
    relatedEntityOf,
    detailUrl,
} = useEntityList<DeviceType>({
    fetch: fetchDeviceTypes,
    remove: deleteDeviceType,
    mapRow: (row) => withCustomFieldValues(row, props.customFields ?? []),
    errorMessage: t('sfxonitam', 'Error on loading device types.'),
})
const deleteMessage = computed(() => t('sfxonitam', 'Delete entry „{name}"?', {
    name: deviceTypeToDelete.value?.name ?? '',
}))
const noopCellMounted: CellMounted = () => () => {}
const col = createListColumns({
    relatedEntityOf,
    detailUrl,
    rowUrl: deviceTypeUrl,
    cellMounted: noopCellMounted,
})
const staticColumns = [
    col.text('name', t('sfxonitam', 'Name')),
    col.relation('manufacturerId', t('sfxonitam', 'Manufacturer')),
    col.text('comment', t('sfxonitam', 'Comment'), { sortable: false }),
    col.actions(t('sfxonitam', 'Action')),
]
const columns = computed(() => [
    ...staticColumns,
    ...customFieldColumns(props.customFields ?? [], noopCellMounted, deviceTypeUrl),
])
const defaultColumns = ['name', 'manufacturerId', '#actions']
const { orderedColumns, showModal: columnOrderModalOpen, onSaved } = useColumnOrder(VIEW_ID, columns.value, defaultColumns)
const filterFields = [
    col.textFilter('name', t('sfxonitam', 'Name')),
    col.relationFilter('manufacturerId', t('sfxonitam', 'Manufacturer')),
]

function addItem() {
    window.location.href = generateUrl('/apps/sfxonitam/device-type/detail')
}

function onEditDeviceType(deviceType: DeviceType) {
    window.location.href = deviceTypeUrl(deviceType)
}
</script>

<template>
    <SfxonListLayout
        v-model:filterSidebarOpen="filterSidebarOpen"
        :breadcrumbs="breadcrumbs"
        current-page="deviceTypes"
        :add-label="t('sfxonitam', 'Add Device Type')"
        :loading="loading"
        :error="error"
        @add="addItem"
        @edit-columns="columnOrderModalOpen = true"
    >
        <SfxonTable
            :columns="orderedColumns"
            :dataArray="deviceTypes"
            :dataArrayKey="'id'"
            :deleteCallback="requestDelete"
            :editCallback="onEditDeviceType"
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
                :open="!!deviceTypeToDelete"
                :title="t('sfxonitam', 'Delete device type')"
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