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
import { fetchMerchants, deleteMerchant } from '@/services/MerchantService'
import type { Merchant } from '@/services/MerchantService'
import NcDialog from '@nextcloud/vue/components/NcDialog'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcNoteCard from '@nextcloud/vue/components/NcNoteCard'

const loading   = ref(false)
const error     = ref<string | null>(null)
const merchants   = ref<Merchant[]>([])
const listState = useListState()
const merchantToDelete = ref<Merchant | null>(null)
const generalError = ref<string>('')

const columns = [
    { key: 'name', label: t('sfxonitam', 'Name'), sortable: true },
    { key: 'comment', label: t('sfxonitam', 'Beschreibung/Kommentare'), sortable: false },
    { type: 'actions', label: t('sfxonitam', 'Aktion'), sortable: false },
];

function addItem() {
    window.location.href = generateUrl('/apps/sfxonitam/merchant/detail')
}

function cancelDelete() {
    merchantToDelete.value = null
}

async function confirmDelete() {
    if (!merchantToDelete.value) {
        return
    }

    try {
        let result = await deleteMerchant(merchantToDelete.value.id)
    } catch (e: any) {
        merchantToDelete.value = null

        if(e.response && e.response.status == 422) {
            generalError.value = e.response.data.errors.join('<br>')
        } else {
            generalError.value = 'Es ist ein Fehler beim Löschen aufgetreten.'
        }
        return;
    }

    merchantToDelete.value = null
    await loadMerchants()
}

async function loadMerchants() {
    generalError.value = ''
    loading.value = true
    error.value = null

    try {
        const data = await fetchMerchants({
            orderBy: listState.orderBy,
            direction: listState.orderDirection,
            page: listState.page,
            limit: listState.limit
        })
        merchants.value = data.merchants
        listState.total = data.total
    } catch (e) {
        error.value = t('sfxonitam', 'Fehler beim Laden der Gerätestati.')
    } finally {
        loading.value = false
    }
}

function onEditMerchant(merchant: Merchant) {
    window.location.href = generateUrl(`/apps/sfxonitam/merchant/detail?merchantId=${merchant.id}`);
}

async function onDeleteMerchant(merchant: Merchant) {
    generalError.value = ''
    merchantToDelete.value = merchant
}

watch(() => listState, loadMerchants, { deep: true })
onMounted(loadMerchants)
</script>

<template>
    <NcContent app-name="sfxonitam">
        <NcAppNavigation>
            <NcAppNavigationList>
                <NcAppNavigationNew
                :text="t('sfxonitam', 'Neuer Händler')"
                @click="addItem"
                >
                    <template #icon>
                        <NcIconSvgWrapper :path="mdiPlus" :size="20" />
                    </template>
                </NcAppNavigationNew>
            </NcAppNavigationList>
            <SfxonMainNavigation :currentPage="'merchants'" />
        </NcAppNavigation>

        <!-- Inhaltsbereich -->
        <NcAppContent>
            <div :class="$style.sfxonItamHeader">
                Händler-Verwaltung
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
                <div v-if="error" class="merchants-list__error">{{ error }}</div>

                <!-- Ladeindikator -->
                <div v-else-if="loading" class="merchants-list__loading">
                    <NcLoadingIcon :size="32" />
                </div>

                <!-- Leerer Zustand -->
                <div v-else-if="merchants.length === 0" class="merchants-list__empty">
                    {{ t('sfxonitam', 'Keine Händler gefunden.') }}
                </div>

                <SfxonTable
                    :columns="columns"
                    :dataArray="merchants"
                    :dataArrayKey="'id'"
                    :deleteCallback="onDeleteMerchant"
                    :editCallback="onEditMerchant"
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
        v-if="merchantToDelete"
        :name="t('sfxonitam', 'Händler löschen')"
        :open="!!merchantToDelete"
        @closing="cancelDelete"
    >
        <p>
            {{ t('sfxonitam', `Händler „${merchantToDelete.name}" wirklich löschen?`) }}
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
