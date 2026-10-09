<script setup lang="ts">
import { mdiPlus } from '@mdi/js'
import { translate as t } from '@nextcloud/l10n'
import NcAppContent from '@nextcloud/vue/components/NcAppContent'
import NcAppNavigation from '@nextcloud/vue/components/NcAppNavigation'
import NcAppNavigationList from '@nextcloud/vue/components/NcAppNavigationList'
import NcAppNavigationNew from '@nextcloud/vue/components/NcAppNavigationNew'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcContent from '@nextcloud/vue/components/NcContent'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import NcLoadingIcon from '@nextcloud/vue/components/NcLoadingIcon'
import SfxonItamHeaderBc, { type BreadcrumbItem } from '@/components/SfxonItamHeaderBc'
import SfxonMainNavigation from '@/components/SfxonMainNavigation'

withDefaults(defineProps<{
    breadcrumbs: BreadcrumbItem[]
    currentPage: string
    addLabel: string
    filterSidebarOpen: boolean
    loading?: boolean
    error?: string | null
    appName?: string
}>(), {
    loading: false,
    error: null,
    appName: 'sfxonitam',
})

defineEmits<{
    (e: 'add'): void
    (e: 'editColumns'): void
    (e: 'update:filterSidebarOpen', value: boolean): void
}>()
</script>

<template>
    <NcContent :app-name="appName">
        <NcAppNavigation>
            <NcAppNavigationList :class="$style.navList">
                <NcAppNavigationNew
                    :text="addLabel"
                    @click="$emit('add')"
                >
                    <template #icon>
                        <NcIconSvgWrapper :path="mdiPlus" :size="20" />
                    </template>
                </NcAppNavigationNew>
            </NcAppNavigationList>

            <SfxonMainNavigation :currentPage="currentPage" />

            <template v-if="$slots['navigation-footer']" #footer>
                <slot name="navigation-footer" />
            </template>
        </NcAppNavigation>

        <NcAppContent>
            <SfxonItamHeaderBc
                :titleLabel="''"
                :breadcrumbs="breadcrumbs"
            >
                <template #actionButtonsRight>
                    <NcButton @click="$emit('editColumns')">
                        {{ t('sfxonitam', 'Edit columns') }}
                    </NcButton>
                    <NcButton @click.prevent="$emit('update:filterSidebarOpen', !filterSidebarOpen)">
                        {{ t('sfxonitam', 'Search/Filter') }}
                    </NcButton>
                </template>
            </SfxonItamHeaderBc>

            <div :class="$style.content">
                <div v-if="error">{{ error }}</div>

                <div v-else-if="loading">
                    <NcLoadingIcon :size="32" />
                </div>

                <slot />
            </div>
        </NcAppContent>

        <slot name="filter" />
    </NcContent>

    <slot name="dialogs" />
</template>

<style module>
    .content {
        padding-left: 12px;
        padding-right: 12px;
    }

    .navList {
        flex: 1 1 auto;
        overflow-y: auto;
        min-height: 0;
    }
</style>