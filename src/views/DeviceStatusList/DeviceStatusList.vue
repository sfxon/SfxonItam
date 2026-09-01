<script setup lang="ts">
import { ref, watch, onMounted } from 'vue'
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
import { fetchDeviceStatis, deleteDeviceStatus} from '@/services/DeviceStatusService'
import type { DeviceStatus } from '@/services/DeviceStatusService'
import NcDialog from '@nextcloud/vue/components/NcDialog'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcNoteCard from '@nextcloud/vue/components/NcNoteCard'

const loading   = ref(false)
const error     = ref<string | null>(null)
const deviceStatis   = ref<DeviceSatus[]>([])
const listState = useListState()
const deviceStatusToDelete = ref<DeviceStatus | null>(null)
const generalError = ref<string>('')

const columns = [
    { key: 'name', label: t('sfxonitam', 'Name'), sortable: true },
    { key: 'comment', label: t('sfxonitam', 'Beschreibung/Kommentare'), sortable: false },
    { type: 'actions', label: t('sfxonitam', 'Aktion'), sortable: false },
];

function addItem() {
    window.location.href = generateUrl('/apps/sfxonitam/device-status/detail')
}

function cancelDelete() {
    deviceStatusToDelete.value = null
}

async function confirmDelete() {
    if (!deviceStatusToDelete.value) {
        return
    }

    try {
        let result = await deleteDeviceStatus(deviceStatusToDelete.value.id)
    } catch (e: any) {
        deviceStatusToDelete.value = null

        if(e.response && e.response.status == 422) {
            generalError.value = e.response.data.errors.join('<br>')
        } else {
            generalError.value = 'Es ist ein Fehler beim Löschen aufgetreten.'
        }
        return;
    }

    deviceStatusToDelete.value = null
    await loadDeviceStatis()
}

async function loadDeviceStatis() {
    generalError.value = ''
    loading.value = true
    error.value = null

    try {
        const data = await fetchDeviceStatis({
            orderBy: listState.orderBy,
            direction: listState.orderDirection,
            page: listState.page,
            limit: listState.limit
        })
        deviceStatis.value = data.deviceStatis.mainData
        listState.total = data.total
    } catch (e) {
        error.value = t('sfxonitam', 'Error while loading Device Status.')
    } finally {
        loading.value = false
    }
}

function onEditDeviceStatus(deviceStatus: DeviceStatus) {
    window.location.href = generateUrl(`/apps/sfxonitam/device-status/detail?deviceStatusId=${deviceStatus.id}`);
}

async function onDeleteDeviceStatus(deviceStatus: DeviceStatus) {
    generalError.value = ''
    deviceStatusToDelete.value = deviceStatus
}

watch(() => listState, loadDeviceStatis, { deep: true })
onMounted(loadDeviceStatis)
</script>

<template>
    <NcContent app-name="sfxonitam">
        <NcAppNavigation>
            <NcAppNavigationList>
                <NcAppNavigationNew
                :text="t('sfxonitam', 'Neuer Gerätestatus')"
                @click="addItem"
                >
                    <template #icon>
                        <NcIconSvgWrapper :path="mdiPlus" :size="20" />
                    </template>
                </NcAppNavigationNew>
            </NcAppNavigationList>
            <SfxonMainNavigation :currentPage="'deviceStatis'" />
        </NcAppNavigation>

        <!-- Inhaltsbereich -->
        <NcAppContent>
            <div :class="$style.sfxonItamHeader">
                Gerätestatus-Verwaltung
            </div>

            <!-- Allgemeine Fehlermeldung -->
            <div :class="$style.sfxonItamGeneralError">
                <NcNoteCard
                    v-if="generalError"
                    type="error"
                >
                    {{ generalError }}
                </NcNoteCard>
            </div>

            <div :class="$style.sfxonItamContent">
                <!-- Fehler -->
                <div v-if="error" class="devicestatus-list__error">{{ error }}</div>

                <!-- Ladeindikator -->
                <div v-else-if="loading" class="devicestatus-list__loading">
                    <NcLoadingIcon :size="32" />
                </div>

                <!-- Leerer Zustand -->
                <div v-else-if="deviceStatis.length === 0" class="devicestatus-list__empty">
                    {{ t('sfxonitam', 'Keine Gerätestati gefunden.') }}
                </div>

                <SfxonTable
                    :columns="columns"
                    :dataArray="deviceStatis"
                    :dataArrayKey="'id'"
                    :deleteCallback="onDeleteDeviceStatus"
                    :editCallback="onEditDeviceStatus"
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
        v-if="deviceStatusToDelete"
        :name="t('sfxonitam', 'Gerätestatus löschen')"
        :open="!!deviceStatusToDelete"
        @closing="cancelDelete"
    >
        <p>
            {{ t('sfxonitam', `Status „${deviceStatusToDelete.name}" wirklich löschen?`) }}
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

    .sfxonItamGeneralError {
        padding-left: 12px;
        padding-right: 12px;
    }
</style>
