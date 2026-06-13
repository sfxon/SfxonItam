<script setup lang="ts">
import { reactive, ref, watch, onMounted } from 'vue'
import NcAppContent from '@nextcloud/vue/components/NcAppContent'
import NcAppNavigation from '@nextcloud/vue/components/NcAppNavigation'
import NcAppNavigationList from '@nextcloud/vue/components/NcAppNavigationList'
import NcContent from '@nextcloud/vue/components/NcContent'
import NcLoadingIcon from '@nextcloud/vue/components/NcLoadingIcon'
import { translate as t } from '@nextcloud/l10n'
import { generateUrl } from '@nextcloud/router'
import SfxonMainNavigation from '@/components/SfxonMainNavigation'
import SfxonPagination from '@/components/SfxonPagination'
import SfxonTable from '@/components/SfxonTable'
import { useListState } from '@/composables/useListState'
import { fetchCustomFieldGroups } from '@/services/CustomFieldGroupService'
import type { CustomFieldGroup } from '@/services/CustomFieldGroupService'
import NcNoteCard from '@nextcloud/vue/components/NcNoteCard'

const customFieldGroups = ref<CustomFieldGroup[]>([])
const error = ref<string | null>(null)
const generalError = ref<string>('')
const filterValues = reactive<Record<string, { value: any }[]>>({})
const listState = useListState()
const loading   = ref(false)

const columns = [
    { key: 'name', label: t('sfxonitam', 'Name'), sortable: true },
    { key: 'entityName', label: t('sfxonitam', 'Entity Name'), sortable: true },
    { key: 'comment', label: t('sfxonitam', 'Beschreibung/Kommentare'), sortable: false },
    { type: 'actions', label: t('sfxonitam', 'Aktion'), sortable: false },
];

async function loadCustomFieldGroups() {
    error.value = null

    try {
        const filters = Object.fromEntries(
            Object.entries(filterValues).map(([key, entries]) => [
                key,
                entries.map(e => e.value)
            ])
        )

        const data = await fetchCustomFieldGroups({
            orderBy: listState.orderBy,
            direction: listState.orderDirection,
            page: listState.page,
            limit: listState.limit,
            filters
        })

        customFieldGroups.value = data.result.mainData
        listState.total = data.total
    } catch (e) {
        error.value = t('sfxonitam', 'Error loading Custom Field Groups.')
        console.log(e)
    }
}


function onEditCustomFieldGroup(customFieldGroup: CustomFieldGroup) {
    //window.location.href = generateUrl(`/apps/sfxonitam/position/detail?positionId=${position.id}`);
    alert('Edit group');
}

watch(() => listState, loadCustomFieldGroups, { deep: true })

onMounted(async () => {
    await loadCustomFieldGroups()
})
</script>

<template>
    <NcContent app-name="sfxonitam">
        <NcAppNavigation>
            <NcAppNavigationList>
            </NcAppNavigationList>
            <SfxonMainNavigation :currentPage="'customFieldGroups'" />
        </NcAppNavigation>

        <!-- Inhaltsbereich -->
        <NcAppContent>
            <div :class="$style.sfxonItamHeader">
                Custom Fields
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
                <div v-if="error" class="custom-field-groups-list__error">{{ error }}</div>

                <!-- Ladeindikator -->
                <div v-else-if="loading" class="custom-field-groups-list__loading">
                    <NcLoadingIcon :size="32" />
                </div>

                <!-- Leerer Zustand -->
                <div v-else-if="customFieldGroups.length === 0" class="custom-field-groups-list__empty">
                    {{ t('sfxonitam', 'No custom field groups found.') }}
                </div>

                <SfxonTable
                    :columns="columns"
                    :dataArray="customFieldGroups"
                    :dataArrayKey="'id'"
                    :editCallback="onEditCustomFieldGroup"
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
