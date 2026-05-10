<script setup lang="ts">
import { translate as t } from '@nextcloud/l10n'
import NcAppSidebar from '@nextcloud/vue/components/NcAppSidebar'
import NcAppSidebarTab from '@nextcloud/vue/components/NcAppSidebarTab'
import NcButton from '@nextcloud/vue/components/NcButton'
import SfxonFilterFieldText from './SfxonFilterFieldText.vue'

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
                <SfxonFilterFieldText
                    v-model="filterValues.name"
                />
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
