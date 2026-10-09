<script setup lang="ts">

import { computed, onMounted, onUnmounted, reactive, ref, watch } from 'vue'
import { deleteDevice, fetchDevices } from '@/services/DeviceService'
import type { Device } from '@/services/DeviceService'
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
import SfxonItamHeaderBc, { type BreadcrumbItem } from '@/components/SfxonItamHeaderBc'
import SfxonMainNavigation from '@/components/SfxonMainNavigation'
import SfxonPagination from '@/components/SfxonPagination'
import SfxonQrCodeView from '@/components/SfxonQrCodeView'
import SfxonTable from '@/components/SfxonTable'
import { useListState } from '@/composables/useListState'
import { useColumnOrder } from '@/composables/useColumnOrder'
import { useRelatedEntities } from '@/composables/useRelatedEntities'
import type { RelationMeta } from '@/composables/useRelatedEntities'
import { saveUiState } from '@/services/ListViewSettings'

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

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    {
        label: t('sfxonitam', 'Device Management'),
        link: generateUrl('/apps/sfxonitam/'),
        forceIconText: true,
        disableDrop: true,
    },
])

const VIEW_ID = 'device-list'
const CUSTOM_FIELD_COLUMN_PREFIX = 'custom.'

const initialUiState = loadState<{ filterSidebarOpen?: boolean; navigationOpen?: boolean }>(
    'sfxonitam',
    'listViewUiState-' + VIEW_ID,
    { filterSidebarOpen: true, navigationOpen: true },
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
const devices = ref<Device[]>([])
const deviceToDelete = ref<Device | null>(null)
const filterValues = reactive<Record<string, { value: any }[]>>({})
const appliedFilters = ref<Record<string, any[]>>({})
const modalState = reactive<{
    dataRow: any | null,
    type: 'barcode' | 'image' | 'qrCode'
}>({
    dataRow: null,
    type: 'image',
})
const previewState = reactive<{
    dataRow: any | null,
    type: 'barcode' | 'image' | 'qrCode'
}>({
    dataRow: null,
    type: 'image',
})

const entityMeta = loadState<Record<string, RelationMeta>>('sfxonitam', 'entityMeta', {})

function relatedEntityOf(key: string): string | undefined {
    const entity = key.replace(/Id$/, '')

    return entity in entityMeta ? entity : undefined
}
const {
    data: relatedEntityData,
    applyRelations,
    searchFns: relatedEntitySearchFns,
    detailUrl,
} = useRelatedEntities(entityMeta)

function customFieldColumnKey(technicalName: string): string {
    return `${CUSTOM_FIELD_COLUMN_PREFIX}${technicalName}`
}

function generateDeviceUrl(device: Device) {
    return generateUrl(`/apps/sfxonitam/device/detail?deviceId=${device.id}`)
}

function onGetDeviceUrl(dataRow: any) {
    return generateDeviceUrl(dataRow)
}
function addItem() {
    window.location.href = generateUrl('/apps/sfxonitam/device/detail')
}

function onEditDevice(device: Device) {
    window.location.href = generateDeviceUrl(device)
}

function onDeleteDevice(device: Device) {
    deviceToDelete.value = device
}

function cancelDelete() {
    deviceToDelete.value = null
}

async function confirmDelete() {
    if (!deviceToDelete.value) {
        return
    }
    await deleteDevice(deviceToDelete.value.id)
    deviceToDelete.value = null
    await loadDevices()
}

function onFilterBtn() {
    appliedFilters.value = Object.fromEntries(
        Object.entries(filterValues).map(([key, entries]) => [
            key,
            entries.map((e) => e.value),
        ]),
    )
    devices.value = []

    if (listState.page === 1) {
        reloadDevices()
    } else {
        listState.page = 1
    }
}

function openModal(dataRow: any, type: 'barcode' | 'image' | 'qrCode') {
    modalState.dataRow = dataRow
    modalState.type = type
}

function closeModal() {
    modalState.dataRow = null
}

function previewImage(dataRow: any) {
    previewState.dataRow = dataRow
    previewState.type = 'image'
}

function previewBarcode(dataRow: any) {
    previewState.dataRow = dataRow
    previewState.type = 'barcode'
}

function previewQrCode(dataRow: any) {
    previewState.dataRow = dataRow
    previewState.type = 'qrCode'
}

function previewClear(_dataRow: any) {
    previewState.dataRow = null
}

function bindCell(
    el: HTMLElement,
    onEnter: () => void,
    onClick?: () => void,
) {
    el.addEventListener('mouseenter', onEnter)
    if (onClick) {
        el.addEventListener('click', onClick)
    }

    return () => {
        el.removeEventListener('mouseenter', onEnter)
        if (onClick) {
            el.removeEventListener('click', onClick)
        }
    }
}

const defaultCellMounted = (el: HTMLElement, dataRow: any) =>
    bindCell(el, () => previewImage(dataRow))

const imageCellMounted = (el: HTMLElement, dataRow: any) =>
    bindCell(el, () => previewImage(dataRow), () => openModal(dataRow, 'image'))

const qrCodeCellMounted = (el: HTMLElement, dataRow: any) =>
    bindCell(el, () => previewQrCode(dataRow), () => openModal(dataRow, 'qrCode'))

const barcodeMounted = (el: HTMLElement, dataRow: any) =>
    bindCell(el, () => previewBarcode(dataRow), () => openModal(dataRow, 'barcode'))


async function loadDevices() {
    error.value = null

    try {
        const data = await fetchDevices({
            orderBy: listState.orderBy,
            direction: listState.orderDirection,
            page: listState.page,
            limit: listState.limit,
            filters: appliedFilters.value,
        })

        devices.value = data.data.mainData.map((device: any) => {
            const row = { ...device }

            for (const cf of props.customFields as any[]) {
                row[customFieldColumnKey(cf.technicalName)] = device.customFields?.[cf.technicalName] ?? ''
            }

            return row
        })
        listState.total = data.total
        applyRelations(data.data.relations)
    } catch (e) {
        error.value = t('sfxonitam', 'Error on loading devices.')
        console.log(e)
    }
}

async function reloadDevices() {
    loading.value = true

    try {
        await loadDevices()
    } finally {
        loading.value = false
    }
}


function relationColumn(key: string, label: string) {
    const entity = relatedEntityOf(key)

    return {
        type: 'relatedEntity',
        relatedEntityName: entity,
        key,
        entityDetailUrlCallback: (idOrRow: any) => detailUrl(entity, idOrRow),
        label,
        sortable: true,
        cellMounted: defaultCellMounted,
        colLinkCallback: onGetDeviceUrl,
    }
}

function relationFilter(key: string, label: string) {
    return {
        type: 'relatedEntity',
        relatedEntityName: relatedEntityOf(key),
        key,
        label,
    }
}

// Must be defined after the event handlers (previewQrCode, previewImage, ...),
// because const with ref/reactive depend on the order of the declaration.
const staticColumns = [
    { type: 'image', label: t('sfxonitam', 'Image'), key: 'imageFileId', cellMounted: imageCellMounted, },
    { type: 'qrCode', label: t('sfxonitam', 'QR-Code'), key: '#qrcode', valueKey: 'id', cellMounted: qrCodeCellMounted, },
    { type: 'barcode', label: t('sfxonitam', 'Barcode'), key: '#barcode', valueKey: 'name', prefix: 'DEV', cellMounted: barcodeMounted, },
    { key: 'name', label: t('sfxonitam', 'Name'), sortable: true, cellMounted: defaultCellMounted, colLinkCallback: onGetDeviceUrl, },
    {
        type: 'quantityWithUnit',
        relatedEntityName: relatedEntityOf('quantityUnitId'),
        key: 'quantity',
        relatedEntityKey: 'quantityUnitId',
        entityDetailUrlCallback: (idOrRow: any) => detailUrl(relatedEntityOf('quantityUnitId'), idOrRow),
        label: t('sfxonitam', 'Quantity'),
        sortable: true,
        cellMounted: defaultCellMounted,
        colLinkCallback: onGetDeviceUrl,
    },
    relationColumn('deviceStatusId', t('sfxonitam', 'DeviceStatus')),
    relationColumn('positionId', t('sfxonitam', 'Position')),
    relationColumn('deviceTypeId', t('sfxonitam', 'DeviceType')),
    relationColumn('itamUserId', t('sfxonitam', 'User')),
    { key: 'serialNumber', label: t('sfxonitam', 'Serial Number'), sortable: true, cellMounted: defaultCellMounted, colLinkCallback: onGetDeviceUrl, },
    { key: 'serialNumber2', label: t('sfxonitam', 'Serial Number 2'), sortable: true, cellMounted: defaultCellMounted, colLinkCallback: onGetDeviceUrl, },
    { key: 'assetNumber', label: t('sfxonitam', 'Asset Number'), sortable: true, cellMounted: defaultCellMounted, colLinkCallback: onGetDeviceUrl, },
    relationColumn('merchantId', t('sfxonitam', 'Merchant')),
    { key: 'invoiceNumber', label: t('sfxonitam', 'Invoice-Number'), sortable: true, cellMounted: defaultCellMounted, colLinkCallback: onGetDeviceUrl, },
    { type: 'date', key: 'purchaseDate', label: t('sfxonitam', 'Purchase Date'), sortable: true, cellMounted: defaultCellMounted, colLinkCallback: onGetDeviceUrl, },
    { type: 'actions', key: '#actions', label: t('sfxonitam', 'Action'), sortable: false, cellMounted: defaultCellMounted, },
]

const customFieldColumns = computed(() => (props.customFields as any[]).map((cf) => ({
    key: customFieldColumnKey(cf.technicalName),
    label: cf.name,
    sortable: false,
    cellMounted: defaultCellMounted,
    colLinkCallback: onGetDeviceUrl,
})))

const columns = computed(() => [...staticColumns, ...customFieldColumns.value])
const defaultColumns = ['imageFileId', '#qrcode', '#barcode', 'name', '#actions']
const { orderedColumns, showModal: columnOrderModalOpen, onSaved } = useColumnOrder(VIEW_ID, columns.value, defaultColumns)

const filterFields = [
    { key: 'name', label: t('sfxonitam', 'Name'), },
    { type: 'numericFromTo', key: 'quantity', labelFrom: t('sfxonitam', 'Quantity from'), labelTo: t('sfxonitam', 'Quantity to'), },
    relationFilter('quantityUnitId', t('sfxonitam', 'QuantityUnit')),
    relationFilter('deviceStatusId', t('sfxonitam', 'DeviceStatus')),
    relationFilter('positionId', t('sfxonitam', 'Position')),
    relationFilter('locationId', t('sfxonitam', 'Location')),
    relationFilter('deviceTypeId', t('sfxonitam', 'DeviceType')),
    relationFilter('manufacturerId', t('sfxonitam', 'Manufacturer')),
    relationFilter('itamUserId', t('sfxonitam', 'User')),
    { key: 'serialNumber', label: t('sfxonitam', 'Serial Number'), },
    { key: 'serialNumber2', label: t('sfxonitam', 'Serial Number 2'), },
    { key: 'assetNumber', label: t('sfxonitam', 'Asset Number'), },
    relationFilter('merchantId', t('sfxonitam', 'Merchant')),
    { key: 'invoiceNumber', label: t('sfxonitam', 'Invoice Number'), },
    { type: 'date', key: 'purchaseDate', labelFrom: t('sfxonitam', 'Purchase Date from'), labelTo: t('sfxonitam', 'Purchase Date to') },
]

watch(
    () => [listState.orderBy, listState.orderDirection, listState.page, listState.limit],
    () => reloadDevices(),
)

onMounted(async () => {
    subscribeEventBus('navigation-toggled', onNavigationToggled)
    emitEventBus('toggle-navigation', { open: navigationOpen.value })

    await reloadDevices()
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
                    :text="t('sfxonitam', 'Add device')"
                    @click="addItem"
                >
                    <template #icon>
                        <NcIconSvgWrapper :path="mdiPlus" :size="20" />
                    </template>
                </NcAppNavigationNew>
            </NcAppNavigationList>

            <SfxonMainNavigation :currentPage="'devices'" />

            <!-- Preview Image on the sidebar. -->
            <template #footer>
                <Transition name="sfxon-preview">
                    <div
                        v-if="previewState.dataRow"
                        :class="$style.sfxonNavPreview"
                    >
                        <template v-if="previewState.type === 'qrCode'">
                            <SfxonQrCodeView
                                customStyle="width: 100%; height: 100%; max-height: 220px;"
                                :deviceId="previewState.dataRow.id"
                                :key="previewState.dataRow.id"
                            />
                        </template>
                        <template v-else-if="previewState.type === 'barcode'">
                            <SfxonBarcode
                                customStyle="width: 100%; height: 100%; max-height: 220px;"
                                :name="previewState.dataRow.name"
                                :key="previewState.dataRow.name"
                                :prefix="'DEV'"
                            />
                        </template>
                        <template v-else>
                            <img
                                v-if="previewState.dataRow.imageFileId"
                                :src="generateUrl(`/core/preview?fileId=${previewState.dataRow.imageFileId}&x=220&y=220&a=1`)"
                                :alt="previewState.dataRow.name ?? ''"
                                :class="$style.sfxonNavPreviewImg"
                            />
                            <span
                                v-else
                                :class="$style.sfxonNavPreviewNoImage"
                            >
                                {{ t('sfxonitam', 'No image') }}
                            </span>
                        </template>
                        <span :class="$style.sfxonNavPreviewLabel">
                            {{ previewState.dataRow.name }}
                        </span>
                    </div>
                </Transition>
            </template>
        </NcAppNavigation>

        <NcAppContent>
            <SfxonItamHeaderBc
                :titleLabel="''"
                :breadcrumbs="breadcrumbs">
                <template #actionButtonsRight>
                    <NcButton @click="columnOrderModalOpen = true">
                        {{ t('sfxonitam', 'Edit columns') }}
                    </NcButton>
                    <NcButton @click.prevent="filterSidebarOpen = !filterSidebarOpen">
                        {{ t('sfxonitam', 'Search/Filter') }}
                    </NcButton>
                </template>
            </SfxonItamHeaderBc>

            <div :class="$style.sfxonItamContent">
                <div v-if="error" class="device-list__error">{{ error }}</div>

                <div v-else-if="loading" class="device-list__loading">
                    <NcLoadingIcon :size="32" />
                </div>

                <SfxonTable
                    :columns="orderedColumns"
                    :dataArray="devices"
                    :dataArrayKey="'id'"
                    :deleteCallback="onDeleteDevice"
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
        v-if="deviceToDelete"
        :name="t('sfxonitam', 'Delete device')"
        :open="!!deviceToDelete"
        @closing="cancelDelete"
    >
        <p>
            {{ t('sfxonitam', 'Delete entry „{name}"?', { name: deviceToDelete.name }) }}
        </p>

        <template #actions>
            <NcButton
                variant="tertiary"
                @click="cancelDelete">
                {{ t('sfxonitam', 'Cancel') }}
            </NcButton>
            <NcButton
                variant="error"
                @click="confirmDelete">
                {{ t('sfxonitam', 'Delete') }}
            </NcButton>
        </template>
    </NcDialog>

    <!-- Image, barcode or QR-Code dialog/popup. -->
    <NcDialog
        v-if="modalState.dataRow"
        :name="modalState.dataRow?.name ?? ''"
        :open="!!modalState.dataRow"
        size="normal"
        @closing="closeModal"
        close-on-click-outside
    >
        <div
            :class="$style.sfxonModalContent"
            @click.left="closeModal"
        >
            <template v-if="modalState.type === 'qrCode'">
                <SfxonQrCodeView
                    :deviceId="modalState.dataRow.id"
                    customStyle="width: 100%; height: auto;"
                />
            </template>
            <template v-else-if="modalState.type === 'barcode'">
                <SfxonBarcode
                    :name="modalState.dataRow.name"
                    :prefix="'DEV'"
                    customStyle="width: 100%; height: auto;"
                />
            </template>
            <template v-else>
                <img
                    v-if="modalState.dataRow.imageFileId"
                    :src="generateUrl(`/core/preview?fileId=${modalState.dataRow.imageFileId}&x=800&y=800&a=1`)"
                    :alt="modalState.dataRow.name ?? ''"
                    :class="$style.sfxonModalImg"
                />
                <span v-else :class="$style.sfxonModalNoImage">
                    {{ t('sfxonitam', 'No image') }}
                </span>
            </template>
        </div>
    </NcDialog>

    <SfxonColumnOrderModal
        :active-columns="orderedColumns"
        :all-columns="columns"
        @close="columnOrderModalOpen = false"
        :default-columns="defaultColumns"
        :list-id="VIEW_ID"
        @saved="onSaved"
        :show="columnOrderModalOpen"
    />
</template>

<style module>
    .sfxonItamContent {
        padding-left: 12px;
        padding-right: 12px;
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