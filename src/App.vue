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
import { mdiPlus, mdiPencil, mdiTrashCan, mdiChevronUp, mdiChevronDown } from '@mdi/js'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import { translate as t } from '@nextcloud/l10n'
import { generateUrl } from '@nextcloud/router'
import axios from '@nextcloud/axios'


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
    { key: 'actions', label: t('sfxonitam', 'Aktion'), sortable: false },
];

function addItem() {
    window.location.href = generateUrl('/apps/sfxonitam/device/detail')
}

async function deleteDevice(device: Device) {
    alert('Löschen');
    /*
    if (!confirm(t('sfxonitam', `Gerät „${device.name}" wirklich löschen?`))) return
    await axios.delete(generateUrl(`/apps/sfxonitam/device/${device.id}`))
    await loadDevices()
    */
}

function editDevice(device: Device) {
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
        <NcAppContent :class="$style.content">
            <!-- Sortier-Header -->
            <div class="device-list__header" role="row">
                <button
                    v-for="col in columns"
                    :key="col.key"
                    class="device-list__col"
                    :class="{ active: orderBy === col.key }"
                    :disabled="!col.sortable"
                    :aria-sort="orderBy === col.key
                    ? (direction === 'ASC' ? 'ascending' : 'descending')
                    : undefined"
                    @click="col.sortable && sortBy(col.key)"
                >
                    {{ col.label }}
                    <NcIconSvgWrapper
                    v-if="col.sortable && orderBy === col.key"
                    :path="direction === 'ASC' ? mdiChevronUp : mdiChevronDown"
                    :size="16"
                    />
                </button>
            </div>

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

            <!-- Liste -->
            <ul v-else class="device-list__items">
                <NcListItem
                    v-for="device in devices"
                    :key="device.id"
                    :name="device.name ?? t('sfxonitam', '(kein Name)')"
                    :subname="[device.serialNumber, device.assetNumber].filter(Boolean).join(' · ')"
                    @click="editDevice(device)"
                >
                    <template #actions>
                    <NcActionButton @click.stop="editDevice(device)">
                        <template #icon><NcIconSvgWrapper :path="mdiPencil" :size="20" /></template>
                        {{ t('sfxonitam', 'Bearbeiten') }}
                    </NcActionButton>
                    <NcActionButton @click.stop="deleteDevice(device)">
                        <template #icon><NcIconSvgWrapper :path="mdiTrashCan" :size="20" /></template>
                        {{ t('sfxonitam', 'Löschen') }}
                    </NcActionButton>
                    </template>
                </NcListItem>
            </ul>

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
        </NcAppContent>
    </NcContent>
</template>

<style module>
.content {
    display: flex;
    justify-content: center;
    margin: 16px;
}

.detail {
    padding: 16px 24px;
    max-width: 800px;
}

.list {
    margin-top: 16px;
    list-style: none;
    padding: 0;
}

.empty {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 100%;
    opacity: 0.6;
}

.device-list__toolbar {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 8px 0 16px;
}
.device-list__header {
  display: grid;
  grid-template-columns: 2fr 1fr 1fr 1fr 80px;
  padding: 4px 8px;
  font-weight: 600;
  border-bottom: 1px solid var(--color-border);
}
.device-list__col {
  background: none;
  border: none;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 4px;
  padding: 4px;
  color: var(--color-text-maxcontrast);
}
.device-list__col.active { color: var(--color-main-text); }
.device-list__col:disabled { cursor: default; }
.device-list__loading,
.device-list__empty,
.device-list__error {
  display: flex;
  justify-content: center;
  padding: 32px;
  opacity: 0.6;
}
.device-list__error { color: var(--color-error); opacity: 1; }
.device-list__pagination {
  display: flex;
  align-items: center;
  gap: 4px;
  padding: 12px 0;
  justify-content: center;
}
.device-list__pagination button {
  min-width: 32px;
  padding: 4px 8px;
  border: 1px solid var(--color-border);
  border-radius: var(--border-radius);
  background: var(--color-main-background);
  cursor: pointer;
}
.device-list__pagination button.active {
  background: var(--color-primary-element);
  color: var(--color-primary-element-text);
  border-color: var(--color-primary-element);
}
.device-list__pagination button:disabled { opacity: 0.4; cursor: default; }
.device-list__info { margin-left: 8px; color: var(--color-text-maxcontrast); }
</style>
