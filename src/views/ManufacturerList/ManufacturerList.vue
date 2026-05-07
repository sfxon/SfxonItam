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
import { fetchManufacturers, deleteManufacturer } from '@/services/ManufacturerService'
import type { Manufacturer } from '@/services/ManufacturerService'
import NcDialog from '@nextcloud/vue/components/NcDialog'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcNoteCard from '@nextcloud/vue/components/NcNoteCard'

const loading   = ref(false)
const error     = ref<string | null>(null)
const manufacturers   = ref<Manufacturer[]>([])
const listState = useListState()
const manufacturerToDelete = ref<Manufacturer | null>(null)
const generalError = ref<string>('')

const columns = [
    { key: 'name', label: t('sfxonitam', 'Name'), sortable: true },
    { key: 'comment', label: t('sfxonitam', 'Beschreibung/Kommentare'), sortable: false },
    { type: 'actions', label: t('sfxonitam', 'Aktion'), sortable: false },
];

function addItem() {
    window.location.href = generateUrl('/apps/sfxonitam/manufacturer/detail')
}

function cancelDelete() {
    manufacturerToDelete.value = null
}

async function confirmDelete() {
    if (!manufacturerToDelete.value) {
        return
    }

    try {
        let result = await deleteManufacturer(manufacturerToDelete.value.id)
    } catch (e: any) {
        manufacturerToDelete.value = null

        if(e.response && e.response.status == 422) {
            generalError.value = e.response.data.errors.join('<br>')
        } else {
            generalError.value = 'Es ist ein Fehler beim Löschen aufgetreten.'
        }
        return;
    }

    manufacturerToDelete.value = null
    await loadManufacturers()
}

async function loadManufacturers() {
    generalError.value = ''
    loading.value = true
    error.value = null

    try {
        const data = await fetchManufacturers({
            orderBy: listState.orderBy,
            direction: listState.orderDirection,
            page: listState.page,
            limit: listState.limit
        })
        manufacturers.value = data.manufacturers
        listState.total = data.total
    } catch (e) {
        error.value = t('sfxonitam', 'Fehler beim Laden der Gerätestati.')
    } finally {
        loading.value = false
    }
}

function onEditManufacturer(manufacturer: Manufacturer) {
    window.location.href = generateUrl(`/apps/sfxonitam/manufacturer/detail?manufacturerId=${manufacturer.id}`);
}

async function onDeleteManufacturer(manufacturer: Manufacturer) {
    generalError.value = ''
    manufacturerToDelete.value = manufacturer
}

watch(() => listState, loadManufacturers, { deep: true })
onMounted(loadManufacturers)
</script>

<template>
    <NcContent app-name="sfxonitam">
        <NcAppNavigation>
            <NcAppNavigationList>
                <NcAppNavigationNew
                :text="t('sfxonitam', 'Neuer Hersteller')"
                @click="addItem"
                >
                    <template #icon>
                        <NcIconSvgWrapper :path="mdiPlus" :size="20" />
                    </template>
                </NcAppNavigationNew>
            </NcAppNavigationList>
            <SfxonMainNavigation :currentPage="'manufacturers'" />
        </NcAppNavigation>

        <!-- Inhaltsbereich -->
        <NcAppContent>
            <div :class="$style.sfxonItamHeader">
                Hersteller-Verwaltung
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
                <div v-if="error" class="manufacturers-list__error">{{ error }}</div>

                <!-- Ladeindikator -->
                <div v-else-if="loading" class="manufacturers-list__loading">
                    <NcLoadingIcon :size="32" />
                </div>

                <!-- Leerer Zustand -->
                <div v-else-if="manufacturers.length === 0" class="manufacturers-list__empty">
                    {{ t('sfxonitam', 'Keine Hersteller gefunden.') }}
                </div>

                <SfxonTable
                    :columns="columns"
                    :dataArray="manufacturers"
                    :dataArrayKey="'id'"
                    :deleteCallback="onDeleteManufacturer"
                    :editCallback="onEditManufacturer"
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
        v-if="manufacturerToDelete"
        :name="t('sfxonitam', 'Hersteller löschen')"
        :open="!!manufacturerToDelete"
        @closing="cancelDelete"
    >
        <p>
            {{ t('sfxonitam', `Hersteller „${manufacturerToDelete.name}" wirklich löschen?`) }}
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
