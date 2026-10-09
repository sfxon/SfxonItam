<script setup lang="ts">
import { computed } from 'vue'
import { generateUrl } from '@nextcloud/router'
import { translate as t } from '@nextcloud/l10n'
import { deleteDevice, fetchDevices } from '@/services/DeviceService'
import type { Device } from '@/services/DeviceService'
import SfxonColumnOrderModal from '@/components/SfxonColumnOrderModal'
import SfxonConfirmDialog from '@/components/SfxonConfirmDialog'
import SfxonFilterBar from '@/components/SfxonFilterBar'
import SfxonListLayout from '@/components/SfxonListLayout'
import SfxonMediaModal from '@/components/SfxonMediaModal'
import SfxonMediaPreviewPanel from '@/components/SfxonMediaPreviewPanel'
import SfxonPagination from '@/components/SfxonPagination'
import SfxonTable from '@/components/SfxonTable'
import type { BreadcrumbItem } from '@/components/SfxonItamHeaderBc'
import { createListColumns } from '@/composables/createListColumns'
import { customFieldColumns, withCustomFieldValues } from '@/composables/customFieldColumns'
import type { CustomField } from '@/composables/customFieldColumns'
import { useColumnOrder } from '@/composables/useColumnOrder'
import { useEntityList } from '@/composables/useEntityList'
import { useListViewUiState } from '@/composables/useListViewUiState'
import { useMediaPreview } from '@/composables/useMediaPreview'

const props = defineProps<{
    customFields?: CustomField[]
}>()
const VIEW_ID = 'device-list'
const BARCODE_PREFIX = 'DEV'
const deviceUrl = (device: Pick<Device, 'id'>) => generateUrl(`/apps/sfxonitam/device/detail?deviceId=${device.id}`)
const breadcrumbs = computed<BreadcrumbItem[]>(() => [{
    label: t('sfxonitam', 'Device Management'),
    link: generateUrl('/apps/sfxonitam/'),
    forceIconText: true,
    disableDrop: true,
}])
const { filterSidebarOpen } = useListViewUiState(VIEW_ID)
const {
    listState,
    items: devices,
    loading,
    error,
    filterValues,
    applyFilters,
    itemToDelete: deviceToDelete,
    requestDelete,
    cancelDelete,
    confirmDelete,
    relatedEntityData,
    relatedEntitySearchFns,
    relatedEntityOf,
    detailUrl,
} = useEntityList<Device>({
    fetch: fetchDevices,
    remove: deleteDevice,
    mapRow: (row) => withCustomFieldValues(row, props.customFields ?? []),
    errorMessage: t('sfxonitam', 'Error on loading devices.'),
})
const deleteMessage = computed(() => t('sfxonitam', 'Delete entry „{name}"?', {
    name: deviceToDelete.value?.name ?? '',
}))
const {
    modalState,
    previewState,
    closeModal,
    previewClear,
    defaultCellMounted,
    imageCellMounted,
    qrCodeCellMounted,
    barcodeCellMounted,
} = useMediaPreview()
const col = createListColumns({
    relatedEntityOf,
    detailUrl,
    rowUrl: deviceUrl,
    cellMounted: defaultCellMounted,
})
const staticColumns = [
    col.media('image', 'imageFileId', t('sfxonitam', 'Image'), imageCellMounted),
    col.media('qrCode', '#qrcode', t('sfxonitam', 'QR-Code'), qrCodeCellMounted, { valueKey: 'id' }),
    col.media('barcode', '#barcode', t('sfxonitam', 'Barcode'), barcodeCellMounted, { valueKey: 'name', prefix: BARCODE_PREFIX }),
    col.text('name', t('sfxonitam', 'Name')),
    col.quantityWithUnit('quantity', 'quantityUnitId', t('sfxonitam', 'Quantity')),
    col.relation('deviceStatusId', t('sfxonitam', 'DeviceStatus')),
    col.relation('positionId', t('sfxonitam', 'Position')),
    col.relation('deviceTypeId', t('sfxonitam', 'DeviceType')),
    col.relation('itamUserId', t('sfxonitam', 'User')),
    col.text('serialNumber', t('sfxonitam', 'Serial Number')),
    col.text('serialNumber2', t('sfxonitam', 'Serial Number 2')),
    col.text('assetNumber', t('sfxonitam', 'Asset Number')),
    col.relation('merchantId', t('sfxonitam', 'Merchant')),
    col.text('invoiceNumber', t('sfxonitam', 'Invoice-Number')),
    col.date('purchaseDate', t('sfxonitam', 'Purchase Date')),
    col.actions(t('sfxonitam', 'Action')),
]
const columns = computed(() => [
    ...staticColumns,
    ...customFieldColumns(props.customFields ?? [], defaultCellMounted, deviceUrl),
])
const defaultColumns = ['imageFileId', '#qrcode', '#barcode', 'name', '#actions']
const { orderedColumns, showModal: columnOrderModalOpen, onSaved } = useColumnOrder(VIEW_ID, columns.value, defaultColumns)
const filterFields = [
    col.textFilter('name', t('sfxonitam', 'Name')),
    col.numericRangeFilter('quantity', t('sfxonitam', 'Quantity from'), t('sfxonitam', 'Quantity to')),
    col.relationFilter('quantityUnitId', t('sfxonitam', 'QuantityUnit')),
    col.relationFilter('deviceStatusId', t('sfxonitam', 'DeviceStatus')),
    col.relationFilter('positionId', t('sfxonitam', 'Position')),
    col.relationFilter('locationId', t('sfxonitam', 'Location')),
    col.relationFilter('deviceTypeId', t('sfxonitam', 'DeviceType')),
    col.relationFilter('manufacturerId', t('sfxonitam', 'Manufacturer')),
    col.relationFilter('itamUserId', t('sfxonitam', 'User')),
    col.textFilter('serialNumber', t('sfxonitam', 'Serial Number')),
    col.textFilter('serialNumber2', t('sfxonitam', 'Serial Number 2')),
    col.textFilter('assetNumber', t('sfxonitam', 'Asset Number')),
    col.relationFilter('merchantId', t('sfxonitam', 'Merchant')),
    col.textFilter('invoiceNumber', t('sfxonitam', 'Invoice Number')),
    col.dateRangeFilter('purchaseDate', t('sfxonitam', 'Purchase Date from'), t('sfxonitam', 'Purchase Date to')),
]

function addItem() {
    window.location.href = generateUrl('/apps/sfxonitam/device/detail')
}

function onEditDevice(device: Device) {
    window.location.href = deviceUrl(device)
}
</script>

<template>
    <SfxonListLayout
        v-model:filterSidebarOpen="filterSidebarOpen"
        :breadcrumbs="breadcrumbs"
        current-page="devices"
        :add-label="t('sfxonitam', 'Add device')"
        :loading="loading"
        :error="error"
        @add="addItem"
        @edit-columns="columnOrderModalOpen = true"
    >
        <template #navigation-footer>
            <SfxonMediaPreviewPanel
                :dataRow="previewState.dataRow"
                :type="previewState.type"
                :barcodePrefix="BARCODE_PREFIX"
            />
        </template>

        <SfxonTable
            :columns="orderedColumns"
            :dataArray="devices"
            :dataArrayKey="'id'"
            :deleteCallback="requestDelete"
            :editCallback="onEditDevice"
            :listState="listState"
            :orderByCallback="listState.sortBy"
            :relatedEntityData="relatedEntityData"
            :tableLeaveHandler="previewClear"
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
                :open="!!deviceToDelete"
                :title="t('sfxonitam', 'Delete device')"
                :message="deleteMessage"
                :confirm-label="t('sfxonitam', 'Delete')"
                @confirm="confirmDelete"
                @cancel="cancelDelete"
            />

            <SfxonMediaModal
                :dataRow="modalState.dataRow"
                :type="modalState.type"
                :barcodePrefix="BARCODE_PREFIX"
                @close="closeModal"
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