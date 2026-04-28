<script setup lang="ts">
import { ref, watch, onMounted } from 'vue'
import NcAppContent from '@nextcloud/vue/components/NcAppContent'
import NcAppNavigation from '@nextcloud/vue/components/NcAppNavigation'
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

const loading   = ref(false)
const error     = ref<string | null>(null)
const devices   = ref<Device[]>([])
const listState = useListState()
const deviceToDelete = ref<Device | null>(null)

const columns = [
    { key: 'name', label: t('sfxonitam', 'Name'), sortable: true },
    { key: 'deviceStatusId', label: t('sfxonitam', 'DeviceStatus'), sortable: true },
    { key: 'positionId', label: t('sfxonitam', 'Position'), sortable: true  },
    { key: 'deviceTypeId', label: t('sfxonitam', 'DeviceType'), sortable: true },
    { key: 'userId', label: t('sfxonitam', 'User'), sortable: true },
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

function onEditDevice(device: Device) {
    window.location.href = generateUrl(`/apps/sfxonitam/device/detail?deviceId=${device.id}`);
}

async function onDeleteDevice(device: Device) {
    deviceToDelete.value = device
}

watch(() => listState, loadDevices, { deep: true })
onMounted(loadDevices)
</script>

<template>
    <NcContent app-name="sfxonitam">
        <NcAppNavigation>
            <template #list>
                <NcAppNavigationNew
                :text="t('sfxonitam', 'Neues Gerät')"
                @click="addItem"
                >
                    <template #icon>
                        <NcIconSvgWrapper :path="mdiPlus" :size="20" />
                    </template>
                </NcAppNavigationNew>
                <SfxonMainNavigation :currentPage="'devices'" />
            </template>
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
