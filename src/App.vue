<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue'
import NcAppContent from '@nextcloud/vue/components/NcAppContent'
import NcAppNavigation from '@nextcloud/vue/components/NcAppNavigation'
import NcAppNavigationNew from '@nextcloud/vue/components/NcAppNavigationNew'
import NcAppNavigationItem from '@nextcloud/vue/components/NcAppNavigationItem'
import NcContent from '@nextcloud/vue/components/NcContent'
import NcListItem from '@nextcloud/vue/components/NcListItem'
import NcActions from '@nextcloud/vue/components/NcActions'
import NcActionButton from '@nextcloud/vue/components/NcActionButton'
import { mdiPlus, mdiPencil, mdiTrashCan, mdiChevronUp, mdiChevronDown, mdiDelete } from '@mdi/js'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import { translate as t } from '@nextcloud/l10n'
import { generateUrl } from '@nextcloud/router'
import axios from '@nextcloud/axios'
import SfxonTable from '@/components/SfxonTable'

interface Device {
    id: number
    name: string | null
    serialNumber: string | null
    assetNumber: string | null
    macAddress: string | null
    purchaseDate: string | null
    deviceStatusId: number | null
}

const devices   = ref<Device[]>([]);
const total     = ref(0);
const page      = ref(1);
const limit     = ref(20);
const orderBy   = ref('name');
const direction = ref<'ASC' | 'DESC'>('ASC');
const loading   = ref(false);
const error     = ref<string | null>(null);
const totalPages = computed(() => Math.max(1, Math.ceil(total.value / limit.value)));
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
    { key: 'purchase_date', label: t('sfxonitam', 'Kaufdatum'), sortable: true },
    { type: 'actions', label: t('sfxonitam', 'Aktion'), sortable: false },
];

function addItem() {
    window.location.href = generateUrl('/apps/sfxonitam/device/detail')
}

async function onDeleteDevice(device: Device) {
    alert('Löschen');
    /*
    if (!confirm(t('sfxonitam', `Gerät „${device.name}" wirklich löschen?`))) return
    await axios.delete(generateUrl(`/apps/sfxonitam/device/${device.id}`))
    await loadDevices()
    */
}

function onEditDevice(device: Device) {
    window.location.href = generateUrl(`/apps/sfxonitam/device/detail?id=${device.id}`);
}

async function loadDevices() {
    loading.value = true
    error.value   = null
    try {
        const { data } = await axios.get(generateUrl('/apps/sfxonitam/device/list'), {
        params: { orderBy: orderBy.value, direction: direction.value,
                    page: page.value, limit: limit.value },
        })
        devices.value = data.devices
        total.value   = data.total
    } catch (e) {
        error.value = t('sfxonitam', 'Fehler beim Laden der Geräte.')
    } finally {
        loading.value = false
    }
}

function sortBy(col: string) {
    if (orderBy.value === col) {
        direction.value = direction.value === 'ASC' ? 'DESC' : 'ASC'
    } else {
        orderBy.value   = col
        direction.value = 'ASC'
    }
    page.value = 1
}

watch([orderBy, direction, page], loadDevices)
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
                    :orderBy="orderBy"
                    :orderByCallback="sortBy"
                    :orderDirection="direction"
                    />

                <!-- Paginierung -->
                <div v-if="totalPages > 1" class="device-list__pagination">
                    <button :disabled="page === 1" @click="page--">‹</button>

                    <template v-for="p in totalPages" :key="p">
                        <!-- Fenster: erste, letzte, und ±2 um aktuelle Seite -->
                        <template v-if="p === 1 || p === totalPages || (p >= page - 2 && p <= page + 2)">
                            <button :class="{ active: p === page }" @click="page = p">{{ p }}</button>
                        </template>
                        <span v-else-if="p === page - 3 || p === page + 3">…</span>
                    </template>

                    <button :disabled="page === totalPages" @click="page++">›</button>

                    <span class="device-list__info">
                        {{ (page - 1) * limit + 1 }}–{{ Math.min(page * limit, total) }}
                        {{ t('sfxonitam', 'von') }} {{ total }}
                    </span>
                </div>
            </div>
        </NcAppContent>
    </NcContent>
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
