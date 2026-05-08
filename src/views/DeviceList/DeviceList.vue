<script setup lang="ts">
import { reactive, ref, watch, onMounted } from 'vue'
import NcAppContent from '@nextcloud/vue/components/NcAppContent'
import NcAppNavigation from '@nextcloud/vue/components/NcAppNavigation'
import NcAppNavigationList from '@nextcloud/vue/components/NcAppNavigationList'
import NcAppNavigationNew from '@nextcloud/vue/components/NcAppNavigationNew'
import NcContent from '@nextcloud/vue/components/NcContent'
import { mdiPlus } from '@mdi/js'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import NcLoadingIcon from '@nextcloud/vue/components/NcLoadingIcon'
import { translate as t } from '@nextcloud/l10n'
import { generateUrl } from '@nextcloud/router'
import SfxonMainNavigation from '@/components/SfxonMainNavigation'
import SfxonPagination from '@/components/SfxonPagination'
import SfxonTable from '@/components/SfxonTable'
import { useListState } from '@/composables/useListState'
import { fetchDevices, deleteDevice} from '@/services/DeviceService'
import type { Device } from '@/services/DeviceService'
import NcDialog from '@nextcloud/vue/components/NcDialog'
import NcButton from '@nextcloud/vue/components/NcButton'
import { fetchAllDeviceStatis } from '@/services/DeviceStatusService'
import { fetchAllDeviceTypes } from '@/services/DeviceTypeService'
import { fetchAllItamUsers } from '@/services/ItamUserService'
import { fetchAllLocations } from '@/services/LocationService'
import { fetchAllPositions } from '@/services/PositionService'

const loading = ref(false)
const deviceStatisLoading = ref(false)
const deviceTypesLoading = ref(false)
const itamUsersLoading = ref(false)
const locationsLoading = ref(false)
const positionsLoading = ref(false)

const error = ref<string | null>(null)
const listState = useListState()

const devices = ref<Device[]>([])
const deviceToDelete = ref<Device | null>(null)
const locations = ref<{ id: string; label: string}[]>([])
const relatedEntityData = reactive<Record<string, { id: any; label: string }[]>>({
    'deviceStatus': [],
    'deviceType': [],
    'itamUser': [],
    'position': []
});

const columns = [
    { key: 'name', label: t('sfxonitam', 'Name'), sortable: true },
    { type: 'relatedEntity', relatedEntityName: 'deviceStatus', key: 'deviceStatusId', label: t('sfxonitam', 'DeviceStatus'), sortable: false },
    { type: 'relatedEntity', relatedEntityName: 'position', key: 'positionId', label: t('sfxonitam', 'Position'), sortable: false  },
    { type: 'relatedEntity', relatedEntityName: 'deviceType', key: 'deviceTypeId', label: t('sfxonitam', 'DeviceType'), sortable: false },
    { type: 'relatedEntity', relatedEntityName: 'itamUser', key: 'itamUserId', label: t('sfxonitam', 'User'), sortable: true },
    { key: 'serialNumber', label: t('sfxonitam', 'Seriennummer'), sortable: true },
    { key: 'serialNumber2', label: t('sfxonitam', 'Seriennummer 2'), sortable: true },
    { key: 'assetNumber', label: t('sfxonitam', 'Assetnumber'), sortable: true },
    { key: 'macAddress', label: t('sfxonitam', 'MAC-Adresse'), sortable: true },
    { key: 'merchantId', label: t('sfxonitam', 'Verkäufer'), sortable: true },
    { key: 'invoiceNumber', label: t('sfxonitam', 'Rechnungs-Nummer'), sortable: true },
    { type: 'date', key: 'purchaseDate', label: t('sfxonitam', 'Kaufdatum'), sortable: true },
    { type: 'actions', label: t('sfxonitam', 'Aktion'), sortable: false },
];

function addItem() {
    window.location.href = generateUrl('/apps/sfxonitam/device/detail')
}

function cancelDelete() {
    deviceToDelete.value = null
}

async function confirmDelete() {
    if (!deviceToDelete.value) return
    await deleteDevice(deviceToDelete.value.id)
    deviceToDelete.value = null
    await loadDevices()
}

async function loadDevices() {
    loading.value = true
    error.value = null

    try {
        const data = await fetchDevices({
            orderBy: listState.orderBy,
            direction: listState.orderDirection,
            page: listState.page,
            limit: listState.limit
        })
        devices.value = data.devices
        listState.total = data.total
    } catch (e) {
        error.value = t('sfxonitam', 'Fehler beim Laden der Geräte.')
    } finally {
        loading.value = false
    }
}

async function loadDeviceStatis() {
    deviceStatisLoading.value = true;

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
    deviceTypesLoading.value = true;

    try {
        const data = await fetchAllDeviceTypes({})

        relatedEntityData['deviceType'] = Object.values(data.deviceTypes).map((deviceType: any) => ({
            id: deviceType.id,
            label: deviceType.name
        }))

        console.log('relatedEntityData: ', relatedEntityData)
    } catch(e) {
        console.error('Fehler beim Laden der Device-Types', e)
    } finally {
        deviceTypesLoading.value = false
    }
}

async function loadItamUsers() {
    itamUsersLoading.value = true;

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
    locationsLoading.value = true;

    try {
        const data = await fetchAllLocations({})

        locations.value = Object.values(data.locations).map((location: any) => ({
            id: location.id,
            label: location.name
        }))
    } catch(e) {
        console.error('Fehler beim Laden der Locations', e)
    } finally {
        locationsLoading.value = false
    }
}

async function loadPositions() {
    await loadLocations()

    positionsLoading.value = true;

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

function onEditDevice(device: Device) {
    window.location.href = generateUrl(`/apps/sfxonitam/device/detail?deviceId=${device.id}`);
}

async function onDeleteDevice(device: Device) {
    deviceToDelete.value = device
}

watch(() => listState, loadDevices, { deep: true })

onMounted(async () => {
    await loadDeviceStatis()
    await loadDeviceTypes()
    await loadItamUsers()
    await loadPositions()

    // Load other entities before Devices.
    await loadDevices()
})

</script>

<template>
    <NcContent app-name="sfxonitam">
        <NcAppNavigation>
            <NcAppNavigationList>
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
        </NcAppNavigation>

        <!-- Inhaltsbereich -->
        <NcAppContent>
            <div :class="$style.sfxonItamHeader">
                Geräte-Verwaltung
            </div>
            <div :class="$style.sfxonItamContent">
                <!-- Fehler -->
                <div v-if="error" class="device-list__error">{{ error }}</div>

                <!-- Ladeindikator -->
                <div v-else-if="loading" class="device-list__loading">
                    <NcLoadingIcon :size="32" />
                </div>

                <!-- Leerer Zustand -->
                <div v-else-if="devices.length === 0" class="device-list__empty">
                    {{ t('sfxonitam', 'Keine Geräte gefunden.') }}
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
                />

                <SfxonPagination
                    v-model:page="listState.page"
                    :listState="listState"
                />
            </div>
        </NcAppContent>
    </NcContent>

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
</style>
