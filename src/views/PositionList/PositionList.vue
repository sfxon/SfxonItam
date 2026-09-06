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
import { fetchPositions, deletePosition } from '@/services/PositionService'
import type { Position } from '@/services/PositionService'
import NcDialog from '@nextcloud/vue/components/NcDialog'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcNoteCard from '@nextcloud/vue/components/NcNoteCard'
import { fetchAllLocations } from '@/services/LocationService'

const loading = ref(false)
const locationsLoading = ref(false)
const error = ref<string | null>(null)
const positions = ref<Position[]>([])
const relatedEntityData = reactive({ location: [] as { id: number; label: string }[] });
const listState = useListState()
const positionToDelete = ref<Position | null>(null)
const generalError = ref<string>('')

const columns = [
    { key: 'name', label: t('sfxonitam', 'Name'), sortable: true },
    { type: 'relatedEntity', relatedEntityName: 'location', key: 'locationId', label: t('sfxonitam', 'Location'), sortable: false },
    { key: 'comment', label: t('sfxonitam', 'Beschreibung/Kommentare'), sortable: false },
    { type: 'actions', label: t('sfxonitam', 'Aktion'), sortable: false },
];

function addItem() {
    window.location.href = generateUrl('/apps/sfxonitam/position/detail')
}

function cancelDelete() {
    positionToDelete.value = null
}

async function confirmDelete() {
    if (!positionToDelete.value) {
        return
    }

    try {
        let result = await deletePosition(positionToDelete.value.id)
    } catch (e: any) {
        positionToDelete.value = null

        if(e.response && e.response.status == 422) {
            generalError.value = e.response.data.errors.join('<br>')
        } else {
            generalError.value = 'An error occured on delete.'
        }
        return;
    }

    positionToDelete.value = null
    await loadPositions()
}

async function loadPositions() {
    generalError.value = ''
    loading.value = true
    error.value = null

    try {
        const data = await fetchPositions({
            orderBy: listState.orderBy,
            direction: listState.orderDirection,
            page: listState.page,
            limit: listState.limit
        })
        positions.value = data.positions.mainData
        listState.total = data.total
    } catch (e) {
        error.value = t('sfxonitam', 'Error while loading positions.')
    } finally {
        loading.value = false
    }
}

async function loadLocations() {
    locationsLoading.value = true;

    try {
        const data = await fetchAllLocations({})

        relatedEntityData['location'] = Object.values(data.locations).map((deviceStatus: any) => ({
            id: deviceStatus.id,
            label: deviceStatus.name
        }))
    } catch(e) {
        console.error('Error while loading locations:', e)
    } finally {
        locationsLoading.value = false
    }
}

function onEditPosition(position: Position) {
    window.location.href = generateUrl(`/apps/sfxonitam/position/detail?positionId=${position.id}`);
}

async function onDeletePosition(position: Position) {
    generalError.value = ''
    positionToDelete.value = position
}

watch(() => listState, loadPositions, { deep: true })

onMounted(async () => {
    // await loadLocations()
    await loadPositions()
})
</script>

<template>
    <NcContent app-name="sfxonitam">
        <NcAppNavigation>
            <NcAppNavigationList>
                <NcAppNavigationNew
                :text="t('sfxonitam', 'Neue Position')"
                @click="addItem"
                >
                    <template #icon>
                        <NcIconSvgWrapper :path="mdiPlus" :size="20" />
                    </template>
                </NcAppNavigationNew>
            </NcAppNavigationList>
            <SfxonMainNavigation :currentPage="'positions'" />
        </NcAppNavigation>

        <!-- Inhaltsbereich -->
        <NcAppContent>
            <div :class="$style.sfxonItamHeader">
                Positionen-Verwaltung
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
                <div v-if="error" class="positions-list__error">{{ error }}</div>

                <!-- Ladeindikator -->
                <div v-else-if="loading" class="positions-list__loading">
                    <NcLoadingIcon :size="32" />
                </div>

                <!-- Leerer Zustand -->
                <div v-else-if="positions.length === 0" class="positions-list__empty">
                    {{ t('sfxonitam', 'Keine Positionen gefunden.') }}
                </div>

                <SfxonTable
                    :columns="columns"
                    :dataArray="positions"
                    :dataArrayKey="'id'"
                    :deleteCallback="onDeletePosition"
                    :editCallback="onEditPosition"
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
        v-if="positionToDelete"
        :name="t('sfxonitam', 'Position löschen')"
        :open="!!positionToDelete"
        @closing="cancelDelete"
    >
        <p>
            {{ t('sfxonitam', `Position „${positionToDelete.name}" wirklich löschen?`) }}
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
