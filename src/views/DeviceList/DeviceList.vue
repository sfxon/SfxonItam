<script setup lang="ts">

import { deleteDevice } from '@/services/DeviceService'
import type { Device } from '@/services/DeviceService'
import { fetchDevices} from '@/services/DeviceService'
import { generateUrl } from '@nextcloud/router'
import { reactive } from 'vue'
import { ref } from 'vue'
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
import { onMounted } from 'vue'
import { translate as t } from '@nextcloud/l10n'
import SfxonBarcode from '@/components/SfxonBarcode'
import SfxonFilterBar from '@/components/SfxonFilterBar'
import SfxonMainNavigation from '@/components/SfxonMainNavigation'
import SfxonPagination from '@/components/SfxonPagination'
import SfxonQrCodeView from '@/components/SfxonQrCodeView'
import SfxonTable from '@/components/SfxonTable'
import { useListState } from '@/composables/useListState'
import { fetchAllDeviceStatis, getDeviceStatusDetailLink } from '@/services/DeviceStatusService'
import { fetchAllDeviceTypes, getDeviceTypeDetailLink } from '@/services/DeviceTypeService'
import { fetchAllItamUsers, getItamUserDetailLink } from '@/services/ItamUserService'
import { fetchAllLocations } from '@/services/LocationService'
import { fetchAllManufacturers, getManufacturerDetailLink } from '@/services/ManufacturerService'
import { fetchAllMerchants, getMerchantDetailLink } from '@/services/MerchantService'
import { fetchAllPositions, getPositionDetailLink } from '@/services/PositionService'
import { fetchAllQuantityUnits, getQuantityUnitDetailLink } from '@/services/QuantityUnitService'
import { watch } from 'vue'

const deviceStatisLoading = ref(false)
const deviceTypesLoading = ref(false)
const filterSidebarOpen = ref(true)
const itamUsersLoading = ref(false)
const loading = ref(false)
const locationsLoading = ref(false)
const manufacturersLoading = ref(false)
const merchantsLoading = ref(false)
const positionsLoading = ref(false)
const quantityUnitsLoading = ref(false)
const error = ref<string | null>(null)
const listState = useListState()
const devices = ref<Device[]>([])
const deviceToDelete = ref<Device | null>(null)
const filterValues = reactive<Record<string, { value: any }[]>>({})
const locations = ref<{ id: string; label: string}[]>([])
const manufacturers = ref<{ id: string; label: string}[]>([])
const modalState = reactive<{
    dataRow: any | null,
    type: 'barcode' | 'image' | 'qrCode'
}>({
    dataRow: null,
    type: 'image',
})
const relatedEntityData = reactive<Record<string, { id: any; label: string }[]>>({
    'deviceStatus': [],
    'deviceType': [],
    'itamUser': [],
    'location': [],
    'manufacturer': [],
    'merchant': [],
    'position': [],
    'quantityUnit': [],
})
const previewState = reactive<{
    dataRow: any | null,
    type: 'barcode' | 'image' | 'qrCode'
}>({
    dataRow: null,
    type: 'image',
})

function addItem() {
    window.location.href = generateUrl('/apps/sfxonitam/device/detail')
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
    deviceToDelete.value = null
}

function clearData() {
    devices.value = []
    locations.value = []
    manufacturers.value = []
    relatedEntityData.deviceStatus = []
    relatedEntityData.deviceType = []
    relatedEntityData.itamUser = []
    relatedEntityData.merchant = []
    relatedEntityData.position = []
}

function closeModal() {
    modalState.dataRow = null
}

async function confirmDelete() {
    if (!deviceToDelete.value) return
    await deleteDevice(deviceToDelete.value.id)
    deviceToDelete.value = null
    await loadDevices()
}

function defaultCellMounted(el: HTMLElement, dataRow: any) {
    const onEnter = previewImage.bind(null, dataRow)

    el.addEventListener('mouseenter', onEnter)

    return () => {
        el.removeEventListener('mouseenter', onEnter)
    }
}

function generateDeviceUrl(device: Device) {
    return generateUrl(`/apps/sfxonitam/device/detail?deviceId=${device.id}`)
}

function onGetDeviceUrl(dataRow: any) {
    return generateDeviceUrl(dataRow)
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

async function loadDevices() {
    error.value = null

    try {
        const filters = Object.fromEntries(
            Object.entries(filterValues).map(([key, entries]) => [
                key,
                entries.map(e => e.value)
            ])
        )

        const data = await fetchDevices({
            orderBy: listState.orderBy,
            direction: listState.orderDirection,
            page: listState.page,
            limit: listState.limit,
            filters
        })

        devices.value = data.devices.mainData
        listState.total = data.total
        applyRelations(data.devices.relations)
    } catch (e) {
        error.value = t('sfxonitam', 'Fehler beim Laden der Geräte.')
        console.log(e)
    } finally {
    }
}

async function loadDeviceStatis() {
    try {
        const data = await fetchAllDeviceStatis({})

        relatedEntityData['deviceStatus'] = Object.values(data.deviceStatis).map((deviceStatus: any) => ({
            id: deviceStatus.id,
            label: deviceStatus.name
        }))
    } catch(e) {
        console.error('Fehler beim Laden der Device-Stati', e)
    } finally {
        deviceStatisLoading.value = false
    }
}

async function loadDeviceTypes() {
    deviceTypesLoading.value = true

    try {
        const data = await fetchAllDeviceTypes({})

        relatedEntityData['deviceType'] = Object.values(data.deviceTypes).map((deviceType: any) => ({
            id: deviceType.id,
            label: deviceType.name
        }))
    } catch(e) {
        console.error('Fehler beim Laden der Device-Types', e)
    } finally {
        deviceTypesLoading.value = false
    }
}

async function loadItamUsers() {
    itamUsersLoading.value = true

    try {
        const data = await fetchAllItamUsers({})

        relatedEntityData['itamUser'] = Object.values(data.itamUsers).map((itamUser: any) => ({
            id: itamUser.id,
            label: itamUser.firstname + ' ' + itamUser.lastname
        }))
    } catch(e) {
        console.error('Fehler beim Laden der itamUsers', e)
    } finally {
        itamUsersLoading.value = false
    }
}

async function loadLocations() {
    locationsLoading.value = true

    try {
        const data = await fetchAllLocations({})

        locations.value = Object.values(data.locations).map((location: any) => ({
            id: location.id,
            label: location.name
        }))
        relatedEntityData['location'] = locations
    } catch(e) {
        console.error('Fehler beim Laden der Locations', e)
    } finally {
        locationsLoading.value = false
    }
}

async function loadMerchants() {
    merchantsLoading.value = true

    try {
        const data = await fetchAllMerchants({})

        relatedEntityData['merchant'] = Object.values(data.merchants).map((merchant: any) => ({
            id: merchant.id,
            label: merchant.name
        }))
    } catch(e) {
        console.error('Fehler beim Laden der Merchants', e)
    } finally {
        merchantsLoading.value = false
    }
}

async function loadManufacturers() {
    manufacturersLoading.value = true

    try {
        const data = await fetchAllManufacturers({})

        relatedEntityData['manufacturer'] = Object.values(data.manufacturers).map((manufacturer: any) => ({
            id: manufacturer.id,
            label: manufacturer.name
        }))
    } catch(e) {
        console.error('Error while loading Manufacturers', e)
    } finally {
        manufacturersLoading.value = false
    }
}

async function loadPositions() {
    await loadLocations()

    positionsLoading.value = true

    try {
        const data = await fetchAllPositions({})

        relatedEntityData['position'] = Object.values(data.positions).map((position: any) => {
            const location = locations.value.find(l => l.id == position.locationId)
            return {
                id: position.id,
                label: location
                    ? location.label + ' - ' + position.name
                    : position.name
            }
        })

        // Sort list alphabetically ASC.
        relatedEntityData['position'].sort((a, b) => a.label.localeCompare(b.label))
    } catch(e) {
        console.error('Fehler beim Laden der Positionen', e)
    } finally {
        positionsLoading.value = false
    }
}

async function loadQuantityUnits() {
    quantityUnitsLoading.value = true

    try {
        const data = await fetchAllQuantityUnits({})

        relatedEntityData['quantityUnit'] = Object.values(data.quantityUnits).map((quantityUnit: any) => ({
            id: quantityUnit.id,
            label: quantityUnit.name
        }))
    } catch(e) {
        console.error('Error while loading Quantity Units', e)
    } finally {
        quantityUnitsLoading.value = false
    }
}

function onEditDevice(device: Device) {
    window.location.href = generateDeviceUrl(device)
}

async function onDeleteDevice(device: Device) {
    deviceToDelete.value = device
}

function onFilterBtn() {
    clearData()
    listState.page = 1;
    reloadDevices()
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

async function reloadDevices() {
    loading.value = true

    /*
    await loadDeviceStatis()
    await loadDeviceTypes()
    await loadItamUsers()
    await loadManufacturers()
    await loadMerchants()
    await loadPositions()
    await loadQuantityUnits()
    */

    // Load other entities before Devices.
    await loadDevices()

    loading.value = false
}

// Must be defined after the eventHandlers (previewQrCode, previewImage, rowLeave),
// because const with ref/reactive depend on the order of the declaration.
const columns = [
    { type: 'image', label: t('sfxonitam', 'Image'), key: 'imageFileId', cellMounted: imageCellMounted, },
    { type: 'qrCode', label: t('sfxonitam', 'QR-Code'), key: 'id', cellMounted: qrCodeCellMounted, },
    { type: 'barcode', label: t('sfxonitam', 'Barcode'), key: 'name', prefix: 'DEV', cellMounted: barcodeMounted, },
    { key: 'name', label: t('sfxonitam', 'Name'), sortable: true, cellMounted: defaultCellMounted, colLinkCallback: onGetDeviceUrl, },
    { type: 'quantityWithUnit', relatedEntityName: 'quantityUnit', key: 'quantity', relatedEntityKey: 'quantityUnitId', entityDetailUrlCallback: getQuantityUnitDetailLink, label: t('sfxonitam', 'Quantity'), sortable: true, cellMounted: defaultCellMounted, colLinkCallback: onGetDeviceUrl, },
    { type: 'relatedEntity', relatedEntityName: 'deviceStatus', key: 'deviceStatusId', entityDetailUrlCallback: getDeviceStatusDetailLink, label: t('sfxonitam', 'DeviceStatus'), sortable: false, cellMounted: defaultCellMounted, colLinkCallback: onGetDeviceUrl, },
    { type: 'relatedEntity', relatedEntityName: 'position', key: 'positionId', entityDetailUrlCallback: getPositionDetailLink, label: t('sfxonitam', 'Position'), sortable: false, cellMounted: defaultCellMounted, colLinkCallback: onGetDeviceUrl, },
    { type: 'relatedEntity', relatedEntityName: 'deviceType', key: 'deviceTypeId', entityDetailUrlCallback: getDeviceTypeDetailLink, label: t('sfxonitam', 'DeviceType'), sortable: false, cellMounted: defaultCellMounted, colLinkCallback: onGetDeviceUrl, },
    { type: 'relatedEntity', relatedEntityName: 'itamUser', key: 'itamUserId', entityDetailUrlCallback: getItamUserDetailLink, label: t('sfxonitam', 'User'), sortable: false, cellMounted: defaultCellMounted, colLinkCallback: onGetDeviceUrl, },
    { key: 'serialNumber', label: t('sfxonitam', 'Serial Number'), sortable: true, cellMounted: defaultCellMounted, colLinkCallback: onGetDeviceUrl, },
    { key: 'serialNumber2', label: t('sfxonitam', 'Serial Number 2'), sortable: true, cellMounted: defaultCellMounted, colLinkCallback: onGetDeviceUrl, },
    { key: 'assetNumber', label: t('sfxonitam', 'Asset Number'), sortable: true, cellMounted: defaultCellMounted, colLinkCallback: onGetDeviceUrl, },
    { type: 'relatedEntity', relatedEntityName: 'merchant', key: 'merchantId', entityDetailUrlCallback: getMerchantDetailLink, label: t('sfxonitam', 'Händler'), sortable: false, cellMounted: defaultCellMounted, colLinkCallback: onGetDeviceUrl, },
    { key: 'invoiceNumber', label: t('sfxonitam', 'Rechnungs-Nummer'), sortable: true, cellMounted: defaultCellMounted, colLinkCallback: onGetDeviceUrl, },
    { type: 'date', key: 'purchaseDate', label: t('sfxonitam', 'Purchase Date'), sortable: true, cellMounted: defaultCellMounted, colLinkCallback: onGetDeviceUrl, },
    { type: 'actions', label: t('sfxonitam', 'Aktion'), sortable: false, cellMounted: defaultCellMounted, },
]

const filterFields = [
    { key: 'name', label: t('sfxonitam', 'Name'), },
    { type: 'numericFromTo', key: 'quantity', labelFrom: t('sfxonitam', 'Quantity from'), labelTo: t('sfxonitam', 'Quantity to'), },
    { type: 'relatedEntity', relatedEntityName: 'quantityUnit', key: 'quantityUnitId', label: t('sfxonitam', 'QuantityUnit'), },
    { type: 'relatedEntity', relatedEntityName: 'deviceStatus', key: 'deviceStatusId', label: t('sfxonitam', 'DeviceStatus'), },
    { type: 'relatedEntity', relatedEntityName: 'position', key: 'positionId', label: t('sfxonitam', 'Position'), },
    { type: 'relatedEntity', relatedEntityName: 'location', key: 'locationId', label: t('sfxonitam', 'Location'), },
    { type: 'relatedEntity', relatedEntityName: 'deviceType', key: 'deviceTypeId', label: t('sfxonitam', 'DeviceType'), },
    { type: 'relatedEntity', relatedEntityName: 'manufacturer', key: 'manufacturerId', label: t('sfxonitam', 'Manufacturer'), },
    { type: 'relatedEntity', relatedEntityName: 'itamUser', key: 'itamUserId', label: t('sfxonitam', 'User'), },
    { key: 'serialNumber', label: t('sfxonitam', 'Serial Number'), },
    { key: 'serialNumber2', label: t('sfxonitam', 'Serial Number 2'), },
    { key: 'assetNumber', label: t('sfxonitam', 'Asset Number'), },
    { type: 'relatedEntity', relatedEntityName: 'merchant', key: 'merchantId', label: t('sfxonitam', 'Merchant'), },
    { key: 'invoiceNumber', label: t('sfxonitam', 'Invoice Number'), },
    { type: 'date', key: 'purchaseDate', labelFrom: t('sfxonitam', 'Purchase Date from'), labelTo: t('sfxonitam', 'Purchase Date to') },
]

watch(
    () => [listState.orderBy, listState.orderDirection, listState.page, listState.limit],
    loadDevices
)

onMounted(async () => {
    await reloadDevices()
})
</script>

<template>
    <NcContent app-name="sfxonitam">
        <NcAppNavigation>
            <NcAppNavigationList :class="$style.sfxonNavList">
                <NcAppNavigationNew
                :text="t('sfxonitam', 'Neues Gerät')"
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

        <!-- Main App Content -->
        <NcAppContent>
            <div :class="$style.sfxonItamHeader">
                <div class="content-title">Geräte-Verwaltung</div>
                <div :class="$style.sfxonItamHeaderSidebarToggleBtn">
                    <NcButton @click.prevent="filterSidebarOpen = !filterSidebarOpen">
                        {{ t('sfxonitam', 'Search/Filter') }}
                    </NcButton>
                </div>
            </div>
            <div :class="$style.sfxonItamContent">
                <div v-if="error" class="device-list__error">{{ error }}</div>

                <!-- Loading spinner -->
                <div v-else-if="loading" class="device-list__loading">
                    <NcLoadingIcon :size="32" />
                </div>

                <SfxonTable
                    :columns="columns"
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
        />
    </NcContent>

    <!-- Confirm Delete Dialog. -->
    <NcDialog
        v-if="deviceToDelete"
        :name="t('sfxonitam', 'Gerät löschen')"
        :open="!!deviceToDelete"
        @closing="cancelDelete"
    >
        <p>
            {{ t('sfxonitam', `Gerät „${deviceToDelete.name}" wirklich löschen?`) }}
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

    <!-- Image or QR-Code Dialog/Popup. -->
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
