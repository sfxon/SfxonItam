<script setup lang="ts">
import { translate as t } from '@nextcloud/l10n'
import NcAppSidebar from '@nextcloud/vue/components/NcAppSidebar'
import NcAppSidebarTab from '@nextcloud/vue/components/NcAppSidebarTab'
import NcTextField from '@nextcloud/vue/components/NcTextField'
import NcButton from '@nextcloud/vue/components/NcButton'

const props = defineProps<{
    filterSidebarOpen: boolean
    filterValues: { name: string }
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
                <div>
                    <NcTextField
                        id="filterValuesName"
                        v-model="props.filterValues.name"
                        :label="t('sfxonitam', 'Name / Device-ID')"
                        :placeholder="t('sfxonitam', 'e.g. JP001')"
                    />
                </div>
                <div>
                    <NcButton variant="primary" @click="props.onFilterBtn?.()">
                        {{ t('sfxonitam', 'Update') }}
                    </NcButton>
                </div>
            </NcAppSidebarTab>
        </NcAppSidebar> 
</template>

<style module>
</style>
