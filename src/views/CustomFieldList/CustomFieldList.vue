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
import { fetchCustomFields, deleteCustomField } from '@/services/CustomFieldService'
import type { CustomField } from '@/services/CustomFieldService'
import NcDialog from '@nextcloud/vue/components/NcDialog'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcNoteCard from '@nextcloud/vue/components/NcNoteCard'

const customFields   = ref<CustomField[]>([])
const customFieldToDelete = ref<CustomField | null>(null)
const columns = [
    { key: 'name', label: t('sfxonitam', 'Name'), sortable: true },
    { key: 'comment', label: t('sfxonitam', 'Comment'), sortable: false },
    { type: 'actions', label: t('sfxonitam', 'Action'), sortable: false },
]
const error = ref<string | null>(null)
const filterValues = reactive<Record<string, { value: any }[]>>({})
const generalError = ref<string>('')
const listState = useListState()
const loading = ref(false)
const props = defineProps({
    customFieldGroupId: {
        type: Number,
        required: true,
    },
})
const relatedEntityData = reactive({ 'location': {}, });

function addItem() {
    window.location.href = generateUrl('/apps/sfxonitam/custom-field/detail?customFieldGroupId=' + props.customFieldGroupId)
}

function cancelDelete() {
    customFieldToDelete.value = null
}

async function confirmDelete() {
    if (!customFieldToDelete.value) {
        return
    }

    try {
        let result = await deleteCustomField(customFieldToDelete.value.id)
    } catch (e: any) {
        customFieldToDelete.value = null

        if(e.response && e.response.status == 422) {
            generalError.value = e.response.data.errors.join('<br>')
        } else {
            generalError.value = 'Could not delete custom field.'
        }
        return;
    }

    customFieldToDelete.value = null
    await loadCustomFields()
}

async function loadCustomFields() {
    error.value = null

    try {
        const filters = Object.fromEntries(
            Object.entries(filterValues).map(([key, entries]) => [
                key,
                entries.map(e => e.value)
            ])
        )

        const data = await fetchCustomFields(
            props.customFieldGroupId,
            {
                orderBy: listState.orderBy,
                direction: listState.orderDirection,
                page: listState.page,
                limit: listState.limit,
                filters
            }
        )

        customFields.value = data.result.mainData
        listState.total = data.total
    } catch (e) {
        error.value = t('sfxonitam', 'Error loading Custom Field Groups.')
        console.log(e)
    }
}

function onEditCustomField(customField: CustomField) {
    window.location.href = generateUrl(`/apps/sfxonitam/custom-field/detail?customFieldId=${customField.id}`);
}

async function onDeleteCustomField(customField: CustomField) {
    generalError.value = ''
    customFieldToDelete.value = customField
}

watch(() => listState, loadCustomFields, { deep: true })

onMounted(async () => {
    await loadCustomFields()
})
</script>

<template>
    <NcContent app-name="sfxonitam">
        <NcAppNavigation>
            <NcAppNavigationList>
                <NcAppNavigationNew
                :text="t('sfxonitam', 'Create Custom Field')"
                @click="addItem"
                >
                    <template #icon>
                        <NcIconSvgWrapper :path="mdiPlus" :size="20" />
                    </template>
                </NcAppNavigationNew>
            </NcAppNavigationList>
            <SfxonMainNavigation :currentPage="'customFields'" />
        </NcAppNavigation>

        <!-- Inhaltsbereich -->
        <NcAppContent>
            <div :class="$style.sfxonItamHeader">
                Custom-Fields
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
                <div v-if="error" class="custom-fields-list__error">{{ error }}</div>

                <!-- Ladeindikator -->
                <div v-else-if="loading" class="custom-fields-list__loading">
                    <NcLoadingIcon :size="32" />
                </div>

                <!-- Leerer Zustand -->
                <div v-else-if="customFields.length === 0" class="custom-fields-list__empty">
                    {{ t('sfxonitam', 'No custom fields found.') }}
                </div>

                <SfxonTable
                    :columns="columns"
                    :dataArray="customFields"
                    :dataArrayKey="'id'"
                    :deleteCallback="onDeleteCustomField"
                    :editCallback="onEditCustomField"
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
        v-if="customFieldToDelete"
        :name="t('sfxonitam', 'Position löschen')"
        :open="!!customFieldToDelete"
        @closing="cancelDelete"
    >
        <p>
            {{ t('sfxonitam', `Delete custom field „${customFieldToDelete.name}"?`) }}
        </p>

        <template #actions>
            <NcButton 
                variant="tertiary" 
                @click="cancelDelete">
                {{ t('sfxonitam', 'Cancel') }}
            </NcButton>
            <NcButton
                variant="error"
                @click="confirmDelete">
                {{ t('sfxonitam', 'Confirm delete') }}
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
