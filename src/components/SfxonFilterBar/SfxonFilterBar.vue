<script setup lang="ts">
import { translate as t } from '@nextcloud/l10n'
import NcAppSidebar from '@nextcloud/vue/components/NcAppSidebar'
import NcAppSidebarTab from '@nextcloud/vue/components/NcAppSidebarTab'
import NcButton from '@nextcloud/vue/components/NcButton'
import SfxonFilterFieldDate from './SfxonFilterFieldDate.vue'
import SfxonFilterFieldEntity from './SfxonFilterFieldEntity.vue'
import SfxonFilterFieldNumericFromTo from './SfxonFilterFieldNumericFromTo.vue'
import SfxonFilterFieldText from './SfxonFilterFieldText.vue'

type FilterField = {
    key: string
    label: string
    type?: string
    relatedEntityName?: string
}

type FilterValue = {
    value: any
}

const props = defineProps<{
    filterFields: FilterField[]
    filterValues: Record<string, FilterValue[]>
    filterSidebarOpen: boolean
    relatedEntityData: []
    onFilterBtn?: (() => void) | null
}>()

const emit = defineEmits<{ 'update:filterSidebarOpen': [value: boolean] }>()
</script>
<template>
   <NcAppSidebar
            v-if="filterSidebarOpen"
            :name="t('sfxonitam', 'Search/Filter')"
            @close="emit('update:filterSidebarOpen', false)">
            <NcAppSidebarTab name="Settings" id="settings-tab">
                <template
                    v-for="filterField in filterFields"
                    :key="filterField.label"
                >
                    <template v-if="filterField.type == 'relatedEntity'">
                        <SfxonFilterFieldEntity
                            :label="filterField.label"
                            :entityData="relatedEntityData[filterField.relatedEntityName]"
                            v-model="filterValues[filterField.key]"
                        />
                    </template>
                    <template v-else-if="filterField.type == 'date'">
                        <SfxonFilterFieldDate
                            :labelFrom="filterField.labelFrom"
                            :labelTo="filterField.labelTo"
                            v-model="filterValues[filterField.key]"
                        />
                    </template>
                    <template v-else-if="filterField.type == 'numericFromTo'">
                        <SfxonFilterFieldNumericFromTo
                            :labelFrom="filterField.labelFrom"
                            :labelTo="filterField.labelTo"
                            v-model="filterValues[filterField.key]"
                        />
                    </template>
                    <template v-else>
                        <SfxonFilterFieldText
                            v-model="filterValues[filterField.key]"
                            :label="filterField.label"
                        />
                    </template>
                </template>
                <div>
                    <NcButton variant="primary" @click="onFilterBtn?.()">
                        {{ t('sfxonitam', 'Update') }}
                    </NcButton>
                </div>
            </NcAppSidebarTab>
        </NcAppSidebar> 
</template>

<style module>
    :global(#sfxonitam) :global(.app-sidebar) {
        width: 24rem;
    }
</style>
