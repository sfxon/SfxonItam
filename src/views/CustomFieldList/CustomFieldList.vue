<script setup lang="ts">
import { computed } from 'vue'
import { generateUrl } from '@nextcloud/router'
import { translate as t } from '@nextcloud/l10n'
import { deleteCustomField, fetchCustomFields } from '@/services/CustomFieldService'
import type { CustomField } from '@/services/CustomFieldService'
import SfxonColumnOrderModal from '@/components/SfxonColumnOrderModal'
import SfxonConfirmDialog from '@/components/SfxonConfirmDialog'
import SfxonFilterBar from '@/components/SfxonFilterBar'
import SfxonListLayout from '@/components/SfxonListLayout'
import SfxonPagination from '@/components/SfxonPagination'
import SfxonTable from '@/components/SfxonTable'
import type { BreadcrumbItem } from '@/components/SfxonItamHeaderBc'
import { createListColumns } from '@/composables/createListColumns'
import { useColumnOrder } from '@/composables/useColumnOrder'
import { useEntityList } from '@/composables/useEntityList'
import { useListViewUiState } from '@/composables/useListViewUiState'
import type { CellMounted } from '@/composables/useMediaPreview'

const props = defineProps<{
    customFieldGroupId: number
    customFieldGroup?: { name?: string }
}>()
const VIEW_ID = 'custom-field-list'
const customFieldUrl = (customField: Pick<CustomField, 'id'>) => generateUrl(`/apps/sfxonitam/custom-field/detail?customFieldId=${customField.id}`)
const groupName = computed(() => props.customFieldGroup?.name ?? '')
const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    {
        label: t('sfxonitam', 'Custom Field Sets'),
        link: generateUrl('/apps/sfxonitam/custom-field-group/'),
        forceIconText: true,
        disableDrop: true,
    },
    {
        label: t('sfxonitam', 'Custom Fields for {name}', { name: groupName.value }),
        link: '#',
        forceIconText: true,
        disableDrop: true,
    },
])
const { filterSidebarOpen } = useListViewUiState(VIEW_ID)
const {
    listState,
    items: customFields,
    loading,
    error,
    filterValues,
    applyFilters,
    itemToDelete: customFieldToDelete,
    requestDelete,
    cancelDelete,
    confirmDelete,
    relatedEntityData,
    relatedEntitySearchFns,
    relatedEntityOf,
    detailUrl,
} = useEntityList<CustomField>({
    // The group id comes from the page, so it is bound here.
    // fetchCustomFields returns { result: { mainData, relations }, total }, the list expects { data: ..., total }.
    fetch: async (params) => {
        const response = await fetchCustomFields(props.customFieldGroupId, params)

        return { ...response, data: response.result }
    },
    remove: deleteCustomField,
    errorMessage: t('sfxonitam', 'Error on loading custom fields.'),
})
const deleteMessage = computed(() => t('sfxonitam', 'Delete custom field „{name}"?', {
    name: customFieldToDelete.value?.name ?? '',
}))
const noopCellMounted: CellMounted = () => () => {}
const col = createListColumns({
    relatedEntityOf,
    detailUrl,
    rowUrl: customFieldUrl,
    cellMounted: noopCellMounted,
})
const columns = [
    col.text('name', t('sfxonitam', 'Name')),
    col.text('technicalName', t('sfxonitam', 'Technical Name')),
    col.text('comment', t('sfxonitam', 'Comment'), { sortable: false }),
    col.actions(t('sfxonitam', 'Action')),
]
const defaultColumns = ['name', 'technicalName', 'comment', '#actions']
const { orderedColumns, showModal: columnOrderModalOpen, onSaved } = useColumnOrder(VIEW_ID, columns, defaultColumns)
const filterFields = [
    col.textFilter('name', t('sfxonitam', 'Name')),
    col.textFilter('technicalName', t('sfxonitam', 'Technical Name')),
]

function addItem() {
    window.location.href = generateUrl('/apps/sfxonitam/custom-field/detail?customFieldGroupId=' + props.customFieldGroupId)
}

function onEditCustomField(customField: CustomField) {
    window.location.href = customFieldUrl(customField)
}
</script>

<template>
    <SfxonListLayout
        v-model:filterSidebarOpen="filterSidebarOpen"
        :breadcrumbs="breadcrumbs"
        current-page="customFields"
        :add-label="t('sfxonitam', 'Create Custom Field')"
        :loading="loading"
        :error="error"
        @add="addItem"
        @edit-columns="columnOrderModalOpen = true"
    >
        <SfxonTable
            :columns="orderedColumns"
            :dataArray="customFields"
            :dataArrayKey="'id'"
            :deleteCallback="requestDelete"
            :editCallback="onEditCustomField"
            :listState="listState"
            :orderByCallback="listState.sortBy"
            :relatedEntityData="relatedEntityData"
        />

        <SfxonPagination
            v-model:page="listState.page"
            :listState="listState"
        />

        <template #filter>
            <SfxonFilterBar
                v-model:filterSidebarOpen="filterSidebarOpen"
                :filterFields="filterFields"
                :filterValues="filterValues"
                :onFilterBtn="applyFilters"
                :relatedEntityData="relatedEntityData"
                :relatedEntitySearchFns="relatedEntitySearchFns"
            />
        </template>

        <template #dialogs>
            <SfxonConfirmDialog
                :open="!!customFieldToDelete"
                :title="t('sfxonitam', 'Delete custom field')"
                :message="deleteMessage"
                :confirm-label="t('sfxonitam', 'Delete')"
                @confirm="confirmDelete"
                @cancel="cancelDelete"
            />

            <SfxonColumnOrderModal
                :active-columns="orderedColumns"
                :all-columns="columns"
                :default-columns="defaultColumns"
                :list-id="VIEW_ID"
                :show="columnOrderModalOpen"
                @close="columnOrderModalOpen = false"
                @saved="onSaved"
            />
        </template>
    </SfxonListLayout>
</template>