<script setup lang="ts">
import { computed } from 'vue'
import { generateUrl } from '@nextcloud/router'
import { translate as t } from '@nextcloud/l10n'
import { deleteDeviceStatus, fetchDeviceStatis } from '@/services/DeviceStatusService'
import type { DeviceStatus } from '@/services/DeviceStatusService'
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
const VIEW_ID = 'device-status-list'
const deviceStatusUrl = (deviceStatus: Pick<DeviceStatus, 'id'>) => generateUrl(`/apps/sfxonitam/device-status/detail?deviceStatusId=${deviceStatus.id}`)
const breadcrumbs = computed<BreadcrumbItem[]>(() => [{
    label: t('sfxonitam', 'Device Status'),
    link: generateUrl('/apps/sfxonitam/device-status'),
    forceIconText: true,
    disableDrop: true,
}])
const { filterSidebarOpen } = useListViewUiState(VIEW_ID)
const {
    listState,
    items: deviceStatis,
    loading,
    error,
    filterValues,
    applyFilters,
    itemToDelete: deviceStatusToDelete,
    requestDelete,
    cancelDelete,
    confirmDelete,
    relatedEntityData,
    relatedEntitySearchFns,
    relatedEntityOf,
    detailUrl,
} = useEntityList<DeviceStatus>({
    fetch: fetchDeviceStatis,
    remove: deleteDeviceStatus,
    mapRow: (row) => withCustomFieldValues(row, props.customFields ?? []),
    errorMessage: t('sfxonitam', 'Error on loading device statis.'),
})
const deleteMessage = computed(() => t('sfxonitam', 'Delete entry „{name}"?', {
    name: deviceStatusToDelete.value?.name ?? '',
}))
const noopCellMounted: CellMounted = () => () => {}
const col = createListColumns({
    relatedEntityOf,
    detailUrl,
    rowUrl: deviceStatusUrl,
    cellMounted: noopCellMounted,
})
const staticColumns = [
    col.text('name', t('sfxonitam', 'Name')),
    col.text('comment', t('sfxonitam', 'Comment'), { sortable: false }),
    col.actions(t('sfxonitam', 'Action')),
]
const columns = computed(() => [
    ...staticColumns,
    ...customFieldColumns(props.customFields ?? [], noopCellMounted, deviceStatusUrl),
])
const defaultColumns = ['name', '#actions']
const { orderedColumns, showModal: columnOrderModalOpen, onSaved } = useColumnOrder(VIEW_ID, columns.value, defaultColumns)
const filterFields = [
    col.textFilter('name', t('sfxonitam', 'Name')),
]

function addItem() {
    window.location.href = generateUrl('/apps/sfxonitam/device-status/detail')
}

function onEditDeviceStatus(deviceStatus: DeviceStatus) {
    window.location.href = deviceStatusUrl(deviceStatus)
}
</script>

<template>
    <SfxonListLayout
        v-model:filterSidebarOpen="filterSidebarOpen"
        :breadcrumbs="breadcrumbs"
        current-page="deviceStatis"
        :add-label="t('sfxonitam', 'Add Device Status')"
        :loading="loading"
        :error="error"
        @add="addItem"
        @edit-columns="columnOrderModalOpen = true"
    >
        <SfxonTable
            :columns="orderedColumns"
            :dataArray="deviceStatis"
            :dataArrayKey="'id'"
            :deleteCallback="requestDelete"
            :editCallback="onEditDeviceStatus"
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
                :open="!!deviceStatusToDelete"
                :title="t('sfxonitam', 'Delete device status')"
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