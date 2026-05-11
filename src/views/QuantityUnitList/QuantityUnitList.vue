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
import { fetchQuantityUnits, deleteQuantityUnit } from '@/services/QuantityUnitService'
import type { QuantityUnit } from '@/services/QuantityUnitService'
import NcDialog from '@nextcloud/vue/components/NcDialog'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcNoteCard from '@nextcloud/vue/components/NcNoteCard'

const loading   = ref(false)
const error     = ref<string | null>(null)
const quantityUnits   = ref<QuantityUnit[]>([])
const listState = useListState()
const quantityUnitToDelete = ref<QuantityUnit | null>(null)
const generalError = ref<string>('')

const columns = [
    { key: 'name', label: t('sfxonitam', 'Name'), sortable: true },
    { key: 'comment', label: t('sfxonitam', 'Beschreibung/Kommentare'), sortable: false },
    { type: 'actions', label: t('sfxonitam', 'Aktion'), sortable: false },
];

function addItem() {
    window.location.href = generateUrl('/apps/sfxonitam/quantity-unit/detail')
}

function cancelDelete() {
    quantityUnitToDelete.value = null
}

async function confirmDelete() {
    if (!quantityUnitToDelete.value) {
        return
    }

    try {
        let result = await deleteQuantityUnit(quantityUnitToDelete.value.id)
    } catch (e: any) {
        quantityUnitToDelete.value = null

        if(e.response && e.response.status == 422) {
            generalError.value = e.response.data.errors.join('<br>')
        } else {
            generalError.value = 'Es ist ein Fehler beim Löschen aufgetreten.'
        }
        return;
    }

    quantityUnitToDelete.value = null
    await loadQuantityUnits()
}

async function loadQuantityUnits() {
    generalError.value = ''
    loading.value = true
    error.value = null

    try {
        const data = await fetchQuantityUnits({
            orderBy: listState.orderBy,
            direction: listState.orderDirection,
            page: listState.page,
            limit: listState.limit
        })
        quantityUnits.value = data.quantityUnits
        listState.total = data.total
    } catch (e) {
        error.value = t('sfxonitam', 'Fehler beim Laden der Gerätestati.')
    } finally {
        loading.value = false
    }
}

function onEditQuantityUnit(quantityUnit: QuantityUnit) {
    window.location.href = generateUrl(`/apps/sfxonitam/quantity-unit/detail?quantityUnitId=${quantityUnit.id}`);
}

async function onDeleteQuantityUnit(quantityUnit: QuantityUnit) {
    generalError.value = ''
    quantityUnitToDelete.value = quantityUnit
}

watch(() => listState, loadQuantityUnits, { deep: true })
onMounted(loadQuantityUnits)
</script>

<template>
    <NcContent app-name="sfxonitam">
        <NcAppNavigation>
            <NcAppNavigationList>
                <NcAppNavigationNew
                :text="t('sfxonitam', 'New Quantity Unit')"
                @click="addItem"
                >
                    <template #icon>
                        <NcIconSvgWrapper :path="mdiPlus" :size="20" />
                    </template>
                </NcAppNavigationNew>
            </NcAppNavigationList>
            <SfxonMainNavigation :currentPage="'quantityUnits'" />
        </NcAppNavigation>

        <!-- Inhaltsbereich -->
        <NcAppContent>
            <div :class="$style.sfxonItamHeader">
                Quantity Unit Management
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
                <div v-if="error" class="quantityUnits-list__error">{{ error }}</div>

                <!-- Ladeindikator -->
                <div v-else-if="loading" class="quantityUnits-list__loading">
                    <NcLoadingIcon :size="32" />
                </div>

                <!-- Leerer Zustand -->
                <div v-else-if="quantityUnits.length === 0" class="quantityUnits-list__empty">
                    {{ t('sfxonitam', 'Quantity Unit not found.') }}
                </div>

                <SfxonTable
                    :columns="columns"
                    :dataArray="quantityUnits"
                    :dataArrayKey="'id'"
                    :deleteCallback="onDeleteQuantityUnit"
                    :editCallback="onEditQuantityUnit"
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
        v-if="quantityUnitToDelete"
        :name="t('sfxonitam', 'Delete Quantity Unit')"
        :open="!!quantityUnitToDelete"
        @closing="cancelDelete"
    >
        <p>
            {{ t('sfxonitam', `Really delete quantity unit with name „${quantityUnitToDelete.name}"?`) }}
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
