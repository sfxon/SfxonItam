<script setup lang="ts">

import { computed, onMounted, onUnmounted, reactive, ref } from 'vue'
import { deleteDeviceStatus} from '@/services/DeviceStatusService'
import type { DeviceStatus } from '@/services/DeviceStatusService'
import { fetchDeviceStatis} from '@/services/DeviceStatusService'
import { emit as emitEventBus, subscribe as subscribeEventBus, unsubscribe as unsubscribeEventBus } from '@nextcloud/event-bus'
import { generateUrl } from '@nextcloud/router'
import { loadState } from '@nextcloud/initial-state'
import { mdiPlus } from '@mdi/js'
import NcAppContent from '@nextcloud/vue/components/NcAppContent'
import NcAppNavigation from '@nextcloud/vue/components/NcAppNavigation'
import NcAppNavigationList from '@nextcloud/vue/components/NcAppNavigationList'
import NcAppNavigationNew from '@nextcloud/vue/components/NcAppNavigationNew'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcContent from '@nextcloud/vue/components/NcContent'
import NcDialog from '@nextcloud/vue/components/NcDialog'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import NcLoadingIcon from '@nextcloud/vue/components/NcLoadingIcon'
import { translate as t } from '@nextcloud/l10n'
import SfxonBarcode from '@/components/SfxonBarcode'
import SfxonColumnOrderModal from '@/components/SfxonColumnOrderModal'
import SfxonFilterBar from '@/components/SfxonFilterBar'
import SfxonItamHeaderBc from '@/components/SfxonItamHeaderBc'
import SfxonMainNavigation from '@/components/SfxonMainNavigation'
import SfxonPagination from '@/components/SfxonPagination'
import SfxonQrCodeView from '@/components/SfxonQrCodeView'
import SfxonTable from '@/components/SfxonTable'
import { useListState } from '@/composables/useListState'
import * as DeviceStatusService from '@/services/DeviceStatusService'
import { getDeviceStatusDetailLink } from '@/services/DeviceStatusService'
import * as DeviceTypeService from '@/services/DeviceTypeService'
import { getDeviceTypeDetailLink } from '@/services/DeviceTypeService'
import * as ItamUserService from '@/services/ItamUserService'
import { getItamUserDetailLink } from '@/services/ItamUserService'
import { fetchAllLocations } from '@/services/LocationService'
import { fetchAllManufacturers, getManufacturerDetailLink } from '@/services/ManufacturerService'
import * as MerchantService from '@/services/MerchantService'
import { getMerchantDetailLink } from '@/services/MerchantService'
import * as PositionService from '@/services/PositionService'
import { getPositionDetailLink } from '@/services/PositionService'
import * as QuantityUnitService from '@/services/QuantityUnitService'
import { getQuantityUnitDetailLink } from '@/services/QuantityUnitService'
import { useColumnOrder } from '@/composables/useColumnOrder'
import { saveUiState } from '@/services/ListViewSettings'
import { watch } from 'vue'

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    {
        label: t('sfxonitam', 'Device Status'),
        link: generateUrl('/apps/sfxonitam/device-status'),
        forceIconText: true,
        disableDrop: true,
    },
])

const VIEW_ID = 'device-status-list'

const initialUiState = loadState<{ filterSidebarOpen?: boolean; navigationOpen?: boolean }>(
    'sfxonitam',
    'listViewUiState-' + VIEW_ID,
    { filterSidebarOpen: true, navigationOpen: true }
)
const filterSidebarOpen = ref(initialUiState.filterSidebarOpen ?? true)
const navigationOpen = ref(initialUiState.navigationOpen ?? true)

watch([filterSidebarOpen, navigationOpen], () => {
    saveUiState(VIEW_ID, {
        filterSidebarOpen: filterSidebarOpen.value,
        navigationOpen: navigationOpen.value,
    }).catch((e) => console.warn('Could not save UI state:', e))
})


function onNavigationToggled({ open }: { open: boolean }) {
    navigationOpen.value = open
}
const loading = ref(false)
const error = ref<string | null>(null)
const listState = useListState()
const deviceStatis = ref<DeviceSatus[]>([])
const deviceStatusToDelete = ref<DeviceStatus | null>(null)
const filterValues = reactive<Record<string, { value: any }[]>>({})
const modalState = reactive<{
    dataRow: any | null,
    type: 'barcode' | 'image' | 'qrCode'
}>({
    dataRow: null,
    type: 'image',
})
const relatedEntityData = reactive<Record<string, { id: any; label: string }[]>>({
})
const previewState = reactive<{
    dataRow: any | null,
    type: 'barcode' | 'image' | 'qrCode'
}>({
    dataRow: null,
    type: 'image',
})
const props = defineProps({
    entityDefinitions: {
        type: Object,
        required: true,
    },
    customFields: {
        type: Array,
        default: () => [],
    },
})
const CUSTOM_FIELD_COLUMN_PREFIX = 'custom.'

function customFieldColumnKey(technicalName: string): string {
    return `${CUSTOM_FIELD_COLUMN_PREFIX}${technicalName}`
}

function addItem() {
    window.location.href = generateUrl('/apps/sfxonitam/device-status/detail')
}

function barcodeMounted(el: HTMLElement, dataRow: any) {
    const onEnter = previewBarcode.bind(null, dataRow)
    const onClick = openModal.bind(null, dataRow, 'barcode')

    el.addEventListener('mouseenter', onEnter)
    el.addEventListener('click', onClick)

    return () => {
        el.removeEventListener('mouseenter', onEnter)
        el.removeEventListener('click', onClick)
    }
}
function cancelDelete() {
    deviceStatusToDelete.value = null
}

function clearData() {
    deviceStatis.value = []
}

function closeModal() {
    modalState.dataRow = null
}

async function confirmDelete() {
    if (!deviceStatusToDelete.value) {
        return
    }
    
    await deleteDeviceStatus(deviceStatusToDelete.value.id)
    deviceStatusToDelete.value = null
    await loadDeviceStatis()
}

function defaultCellMounted(el: HTMLElement, dataRow: any) {
    const onEnter = previewImage.bind(null, dataRow)

    el.addEventListener('mouseenter', onEnter)

    return () => {
        el.removeEventListener('mouseenter', onEnter)
    }
}

function generateDeviceStatusUrl(deviceStatus: DeviceStatus) {
    return generateUrl(`/apps/sfxonitam/device-status/detail?deviceStatusId=${deviceStatus.id}`)
}

function onGetDeviceStatusUrl(dataRow: any) {
    return generateDeviceStatusUrl(dataRow)
}

function imageCellMounted(el: HTMLElement, dataRow: any) {
    const onEnter = previewImage.bind(null, dataRow)
    const onClick = openModal.bind(null, dataRow, 'image')

    el.addEventListener('mouseenter', onEnter)
    el.addEventListener('click', onClick)

    return () => {
        el.removeEventListener('mouseenter', onEnter)
        el.removeEventListener('click', onClick)
    }
}

const relationLabelBuilders: Record<string, (row: any) => string> = {
    deviceStatus: (row) => row.name,
    deviceType: (row) => row.name,
    itamUser: (row) => `${row.firstname} ${row.lastname}`,
    merchant: (row) => row.name,
    position: (row) => row.location ? `${row.location.name} - ${row.name}` : row.name,
    quantityUnit: (row) => row.name,
}

function applyRelations(relations?: Record<string, Record<string, any>>) {
    if (!relations) {
        return
    }

    for (const [relationName, buildLabel] of Object.entries(relationLabelBuilders)) {
        const rows = relations[relationName]

        if (!rows) {
            continue
        }

        relatedEntityData[relationName] = Object.values(rows).map((row: any) => ({
            id: row.id,
            label: buildLabel(row),
        }))
    }
}
async function loadDeviceStatis() {
    error.value = null

    try {
	const filters = Object.fromEntries(
            Object.entries(filterValues).map(([key, entries]) => [
                key,
                entries.map(e => e.value)
            ])
        )
        const data = await fetchDeviceStatis({
            orderBy: listState.orderBy,
            direction: listState.orderDirection,
            page: listState.page,
            limit: listState.limit,
            filters
        })
        deviceStatis.value =  data.deviceStatis.mainData.map((deviceStatus: any) => {
            const row = { ...deviceStatus }

            for (const cf of props.customFields as any[]) {
                row[customFieldColumnKey(cf.technicalName)] = device.customFields?.[cf.technicalName] ?? ''
            }

            return row
        })
        listState.total = data.total
        applyRelations(data.deviceStatis.relations)
    } catch (e) {
        error.value = t('sfxonitam', 'Error on loading device statis.')
        console.log(e)
    }
}

async function searchDeviceStatis(query: string, signal: AbortSignal): Promise<void> {
    const filters = { name: [query] }

    const data = await DeviceStatusService.findDeviceStatis({ filters }, signal)

    if (data === null || data.mainData === null) {
        return
    }

    relatedEntityData['deviceStatus'] = Object.values(data.mainData).map((deviceStatus: any) => ({
        id: deviceStatus.id,
        label: deviceStatus.name
    }))
}

async function searchDeviceTypes(query: string, signal: AbortSignal): Promise<void> {
    const filters = { name: [query] }

    const data = await DeviceTypeService.findDeviceTypes({ filters }, signal)

    if (data === null || data.mainData === null) {
        return
    }

    relatedEntityData['deviceType'] = Object.values(data.mainData).map((deviceType: any) => ({
        id: deviceType.id,
        label: deviceType.name
    }))
}

async function searchItamUsers(query: string, signal: AbortSignal): Promise<void> {
    const filters = {
        firstname: [query],
        lastname: [query],
        email: [query]
    }

    const data = await ItamUserService.findItamUsers({ filters }, signal)

    if (data === null || data.mainData === null) {
        return
    }

    relatedEntityData['itamUser'] = Object.values(data.mainData).map((itamUser: any) => ({
        id: itamUser.id,
        label: itamUser.firstname + ' ' + itamUser.lastname
    }))
}

async function searchMerchants(query: string, signal: AbortSignal): Promise<void> {
    const filters = { name: [query] }

    const data = await MerchantService.findMerchants({ filters }, signal)

    if (data === null || data.mainData === null) {
        return
    }

    relatedEntityData['merchant'] = Object.values(data.mainData).map((merchant: any) => ({
        id: merchant.id,
        label: merchant.name
    }))
}

async function searchPositions(query: string, signal: AbortSignal): Promise<void> {
    const filters = { name: [query] }
    const include = { location: {} }

    const data = await PositionService.findPositions({ filters, include }, signal)

    if (data === null || data.result === null || data.result.mainData === null) {
        return
    }

    relatedEntityData['position'] = Object.values(data.result.mainData).map((position: any) => {
        const location = data.result.relations?.location?.[position.locationId]
        const label = location ? `${location.name} - ${position.name}` : position.name
        
        return {
            id: position.id,
            label
        }
    })

    // Sort list alphabetically ASC.
    relatedEntityData['position'].sort((a, b) => a.label.localeCompare(b.label))
}

async function searchQuantityUnits(query: string, signal: AbortSignal): Promise<void> {
    const filters = { name: [query] }
    const data = await QuantityUnitService.findQuantityUnits({ filters }, signal)

    if (data === null || data.mainData === null) {
        return
    }

    relatedEntityData['quantityUnit'] = Object.values(data.mainData).map((quantityUnit: any) => ({
        id: quantityUnit.id,
        label: quantityUnit.name
    }))
}

async function searchManufacturers(_query: string, _signal: AbortSignal): Promise<void> {
    const data = await fetchAllManufacturers({})

    relatedEntityData['manufacturer'] = Object.values(data.manufacturers).map((manufacturer: any) => ({
        id: manufacturer.id,
        label: manufacturer.name
    }))
}

async function searchLocations(_query: string, _signal: AbortSignal): Promise<void> {
    const data = await fetchAllLocations({})

    relatedEntityData['location'] = Object.values(data.locations).map((location: any) => ({
        id: location.id,
        label: location.name
    }))
}

const relatedEntitySearchFns = {
    deviceStatus: searchDeviceStatis,
    deviceType: searchDeviceTypes,
    itamUser: searchItamUsers,
    location: searchLocations,
    manufacturer: searchManufacturers,
    merchant: searchMerchants,
    position: searchPositions,
    quantityUnit: searchQuantityUnits,
}

function onEditDeviceStatus(deviceStatus: DeviceStatus) {
    window.location.href = generateDeviceStatusUrl(deviceStatus);
}

async function onDeleteDeviceStatus(deviceStatus: DeviceStatus) {
    deviceStatusToDelete.value = deviceStatus
}

function onFilterBtn() {
    clearData()
    listState.page = 1;
    reloadDeviceStatis()
}

function openModal(dataRow: any, type: 'barcode' | 'image' | 'qrCode') {
    modalState.dataRow = dataRow
    modalState.type = type
}

function previewImage(dataRow: any) {
    previewState.dataRow = dataRow
    previewState.type = 'image'
}

function qrCodeCellMounted(el: HTMLElement, dataRow: any) {
    const onEnter = previewQrCode.bind(null, dataRow)
    const onClick = openModal.bind(null, dataRow, 'qrCode')

    el.addEventListener('mouseenter', onEnter)
    el.addEventListener('click', onClick)

    return () => {
        el.removeEventListener('mouseenter', onEnter)
        el.removeEventListener('click', onClick)
    }
}

function previewBarcode(dataRow: any) {
    previewState.dataRow = dataRow
    previewState.type = 'barcode'
    return true
}

function previewQrCode(dataRow: any) {
    previewState.dataRow = dataRow
    previewState.type = 'qrCode'
    return true
}

function previewClear(_dataRow: any) {
    previewState.dataRow = null
}

async function reloadDeviceStatis() {
    loading.value = true
    await loadDeviceStatis()

    loading.value = false
}

// Must be defined after the eventHandlers (previewQrCode, previewImage, rowLeave),
// because const with ref/reactive depend on the order of the declaration.
const staticColumns = [
    { key: 'name', label: t('sfxonitam', 'Name'), sortable: true, cellMounted: defaultCellMounted, colLinkCallback: onGetDeviceStatusUrl, },
    { key: 'comment', label: t('sfxonitam', 'Comment'), sortable: false, cellMounted: defaultCellMounted, colLinkCallback: onGetDeviceStatusUrl, },
    { type: 'actions', key: '#actions', label: t('sfxonitam', 'Action'), sortable: false, cellMounted: defaultCellMounted, },
]
const customFieldColumns = computed(() => (props.customFields as any[]).map(cf => ({
    key: customFieldColumnKey(cf.technicalName),
    label: cf.name,
    sortable: false,
    cellMounted: defaultCellMounted,
    colLinkCallback: onGetDeviceStatusUrl,
})))

const columns = computed(() => [...staticColumns, ...customFieldColumns.value])
const defaultColumns = ['name', '#actions']
const { orderedColumns, showModal, onSaved } = useColumnOrder('device-status-list', columns.value, defaultColumns)

const filterFields = [
    { key: 'name', label: t('sfxonitam', 'Name'), },
]

watch(
    () => [listState.orderBy, listState.orderDirection, listState.page, listState.limit],
    loadDeviceStatis
)

onMounted(async () => {
    subscribeEventBus('navigation-toggled', onNavigationToggled)
    emitEventBus('toggle-navigation', { open: navigationOpen.value })

    await reloadDeviceStatis()
})

onUnmounted(() => {
    unsubscribeEventBus('navigation-toggled', onNavigationToggled)
})
</script>

<template>
    <NcContent app-name="sfxonitam">
        <NcAppNavigation>
            <NcAppNavigationList :class="$style.sfxonNavList">
                <NcAppNavigationNew
                :text="t('sfxonitam', 'Add Device Status')"
                @click="addItem"
                >
                    <template #icon>
                        <NcIconSvgWrapper :path="mdiPlus" :size="20" />
                    </template>
                </NcAppNavigationNew>
            </NcAppNavigationList>
            <SfxonMainNavigation :currentPage="'deviceStatis'" />
        </NcAppNavigation>

        <NcAppContent>
            <SfxonItamHeaderBc
                :titleLabel="''"
                :breadcrumbs="breadcrumbs">
                <template #actionButtonsRight>
                    <NcButton @click="showModal = true">
                        {{ t('sfxonitam', 'Edit columns') }}
                    </NcButton>
                    <NcButton @click.prevent="filterSidebarOpen = !filterSidebarOpen">
                        {{ t('sfxonitam', 'Search/Filter') }}
                    </NcButton>
                </template>
            </SfxonItamHeaderBc>

            <div :class="$style.sfxonItamContent">
                <div v-if="error" class="devicestatus-list__error">{{ error }}</div>

                <div v-else-if="loading" class="devicestatus-list__loading">
                    <NcLoadingIcon :size="32" />
                </div>

                <SfxonTable
                    :columns="orderedColumns"
                    :dataArray="deviceStatis"
                    :dataArrayKey="'id'"
                    :deleteCallback="onDeleteDeviceStatus"
                    :editCallback="onEditDeviceStatus"
                    :listState="listState"
                    :orderByCallback="listState.sortBy"
		    :relatedEntityData="relatedEntityData"
                    :tableLeaveHandler="previewClear"
                />

                <SfxonPagination
                    v-model:page="listState.page"
                    :listState="listState"
                />
            </div>
        </NcAppContent>

        <!-- Sidebar for filter and search. -->
        <SfxonFilterBar
            v-model:filterSidebarOpen="filterSidebarOpen"
            :filterFields="filterFields"
            :filterValues="filterValues"
            :onFilterBtn="onFilterBtn"
            :relatedEntityData="relatedEntityData"
            :relatedEntitySearchFns="relatedEntitySearchFns"
        />
    </NcContent>

    <NcDialog
        v-if="deviceStatusToDelete"
        :name="t('sfxonitam', 'Gerätestatus löschen')"
        :open="!!deviceStatusToDelete"
        @closing="cancelDelete"
    >
        <p>
            {{ t('sfxonitam', `Delete entry „${deviceStatusToDelete.name}"?`) }}
        </p>

        <template #actions>
            <NcButton 
                variant="tertiary" 
                @click="cancelDelete">
                {{ t('sfxonitam', 'Abbrechen') }}
            </NcButton>
            <NcButton
                variant="error"
                @click="confirmDelete">
                {{ t('sfxonitam', 'Löschen') }}
            </NcButton>
        </template>
    </NcDialog>

    <SfxonColumnOrderModal
        :active-columns="orderedColumns"
        :all-columns="columns"
        @close="showModal = false"
        :default-columns="defaultColumns"
        list-id="device-status-list"
        @saved="onSaved"
        :show="showModal"
     />
</template>

<style module>
    .sfxonItamHeader {
        align-items: center;
        display: flex;
        flex: 0 0;
        font-weight: bold;
        gap: var(--default-grid-baseline);
        margin-block: var(--app-navigation-padding, 4px);
        margin-inline: calc(var(--default-clickable-area) + 2*var(--app-navigation-padding, 4px)) var(--app-navigation-padding, 4px);
        max-width: 100%;
        min-height: 32px;
    }

    .sfxonItamContent {
        padding-left: 12px;
        padding-right: 12px;
    }

    .sfxonItamHeaderSidebarToggleBtn {
        margin-left: auto;
        margin-right: 0;
    }

    .sfxonNavList {
        flex: 1 1 auto;
        overflow-y: auto;
        min-height: 0;
    }

    .sfxonNavPreview {
        flex: 0 0 auto;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 6px;
        padding: 8px;
        border-top: 1px solid var(--color-border);
        background: var(--color-main-background);
    }

    .sfxonNavPreviewImg {
        width: 100%;
        max-height: 220px;
        object-fit: contain;
        border-radius: 6px;
        display: block;
    }

    .sfxonNavPreviewLabel {
        font-size: 0.8rem;
        color: var(--color-text-lighter);
        text-align: center;
        word-break: break-word;
    }

    .sfxonNavPreviewNoImage {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 100%;
        height: 220px;
        color: var(--color-text-lighter);
        font-size: 0.85rem;
        border: 1px dashed var(--color-border);
        border-radius: 6px;
    }

    .sfxonModalContent {
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 16px 16px 48px;
        cursor: pointer;
        min-height: 200px;
    }

    .sfxonModalImg {
        max-width: 100%;
        max-height: 70vh;
        object-fit: contain;
        border-radius: 8px;
        display: block;
    }

    .sfxonModalNoImage {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 320px;
        height: 200px;
        color: var(--color-text-lighter);
        font-size: 0.9rem;
        border: 1px dashed var(--color-border);
        border-radius: 8px;
    }
</style>
