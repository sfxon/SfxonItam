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
import { fetchDeviceTypes, deleteDeviceType } from '@/services/DeviceTypeService'
import type { DeviceType } from '@/services/DeviceTypeService'
import NcDialog from '@nextcloud/vue/components/NcDialog'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcNoteCard from '@nextcloud/vue/components/NcNoteCard'
import { fetchAllManufacturers } from '@/services/ManufacturerService'

const loading   = ref(false)
const manufacturersLoading = ref(false)
const error     = ref<string | null>(null)
const deviceTypes   = ref<DeviceType[]>([])
const relatedEntityData = reactive({ 'manufacturer': {}, });
const listState = useListState()
const deviceTypeToDelete = ref<DeviceType | null>(null)
const generalError = ref<string>('')

const columns = [
    { key: 'name', label: t('sfxonitam', 'Name'), sortable: true },
    { type: 'relatedEntity', relatedEntityName: 'manufacturer', key: 'manufacturerId', label: t('sfxonitam', 'Manufacturer'), sortable: false },
    { key: 'comment', label: t('sfxonitam', 'Beschreibung/Kommentare'), sortable: false },
    { type: 'actions', label: t('sfxonitam', 'Aktion'), sortable: false },
];

function addItem() {
    window.location.href = generateUrl('/apps/sfxonitam/device-type/detail')
}

function cancelDelete() {
    deviceTypeToDelete.value = null
}

async function confirmDelete() {
    if (!deviceTypeToDelete.value) {
        return
    }

    try {
        let result = await deleteDeviceType(deviceTypeToDelete.value.id)
    } catch (e: any) {
        deviceTypeToDelete.value = null

        if(e.response && e.response.status == 422) {
            generalError.value = e.response.data.errors.join('<br>')
        } else {
            generalError.value = 'Es ist ein Fehler beim Löschen aufgetreten.'
        }
        return;
    }

    deviceTypeToDelete.value = null
    await loadDeviceTypes()
}

async function loadDeviceTypes() {
    generalError.value = ''
    loading.value = true
    error.value = null

    try {
        const data = await fetchDeviceTypes({
            orderBy: listState.orderBy,
            direction: listState.orderDirection,
            page: listState.page,
            limit: listState.limit
        })
        deviceTypes.value = data.deviceTypes
        listState.total = data.total
    } catch (e) {
        error.value = t('sfxonitam', 'Fehler beim Laden der Geräte-Typen.')
    } finally {
        loading.value = false
    }
}

async function loadManufacturers() {
    manufacturersLoading.value = true;

    try {
        const data = await fetchAllManufacturers({})

        relatedEntityData['manufacturer'] = Object.values(data.manufacturers).map((deviceStatus: any) => ({
            id: deviceStatus.id,
            label: deviceStatus.name
        }))
    } catch(e) {
        console.error('Fehler beim Laden der Device-Stati', e)
    } finally {
        manufacturersLoading.value = false
    }
}

function onEditDeviceType(deviceType: DeviceType) {
    window.location.href = generateUrl(`/apps/sfxonitam/device-type/detail?deviceTypeId=${deviceType.id}`);
}

async function onDeleteDeviceType(deviceType: DeviceType) {
    generalError.value = ''
    deviceTypeToDelete.value = deviceType
}

watch(() => listState, loadDeviceTypes, { deep: true })

onMounted(async () => {
    await loadManufacturers()
    await loadDeviceTypes()
})
</script>

<template>
    <NcContent app-name="sfxonitam">
        <NcAppNavigation>
            <NcAppNavigationList>
                <NcAppNavigationNew
                :text="t('sfxonitam', 'Neuer Geräte-Typ')"
                @click="addItem"
                >
                    <template #icon>
                        <NcIconSvgWrapper :path="mdiPlus" :size="20" />
                    </template>
                </NcAppNavigationNew>
            </NcAppNavigationList>
            <SfxonMainNavigation :currentPage="'deviceTypes'" />
        </NcAppNavigation>

        <!-- Inhaltsbereich -->
        <NcAppContent>
            <div :class="$style.sfxonItamHeader">
                Geräte-Typ-Verwaltung
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
                <div v-if="error" class="deviceTypes-list__error">{{ error }}</div>

                <!-- Ladeindikator -->
                <div v-else-if="loading" class="deviceTypes-list__loading">
                    <NcLoadingIcon :size="32" />
                </div>

                <!-- Leerer Zustand -->
                <div v-else-if="deviceTypes.length === 0" class="deviceTypes-list__empty">
                    {{ t('sfxonitam', 'Keine Geräte-Typen gefunden.') }}
                </div>

                <SfxonTable
                    :columns="columns"
                    :dataArray="deviceTypes"
                    :dataArrayKey="'id'"
                    :deleteCallback="onDeleteDeviceType"
                    :editCallback="onEditDeviceType"
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
        v-if="deviceTypeToDelete"
        :name="t('sfxonitam', 'Geräte-Typ löschen')"
        :open="!!deviceTypeToDelete"
        @closing="cancelDelete"
    >
        <p>
            {{ t('sfxonitam', `Geräte-Typ „${deviceTypeToDelete.name}" wirklich löschen?`) }}
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
