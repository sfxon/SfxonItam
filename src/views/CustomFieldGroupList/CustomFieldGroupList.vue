<script setup lang="ts">
import NcAppContent from '@nextcloud/vue/components/NcAppContent'
import NcAppNavigation from '@nextcloud/vue/components/NcAppNavigation'
import NcAppNavigationList from '@nextcloud/vue/components/NcAppNavigationList'
import NcContent from '@nextcloud/vue/components/NcContent'

import NcListItem from '@nextcloud/vue/components/NcListItem'
import { generateUrl } from '@nextcloud/router'
import SfxonMainNavigation from '@/components/SfxonMainNavigation'
import type { CustomFieldGroup } from '@/services/CustomFieldGroupService'
import { mdiChevronRight } from '@mdi/js'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'

const props = defineProps<{
    customFieldGroups: CustomFieldGroup[]
}>()

function editUrl(customFieldGroup: CustomFieldGroup) {
    return generateUrl(`/apps/sfxonitam/custom-field/?customFieldGroupId=${customFieldGroup.id}`)
}

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
                Custom Fields > Sets
            </div>

            <div :class="$style.sfxonItamContent">
                <ul :class="$style.sfxonItamList">
                    <NcListItem
                        v-for="customFieldGroup in props.customFieldGroups.mainData"
                        :key="customFieldGroup.id"
                        :name="customFieldGroup.name"
                        :href="editUrl(customFieldGroup)"
                        target="_self">
                        <template #details>
                            <NcIconSvgWrapper :path="mdiChevronRight" :size="20" />
                        </template>
                    </NcListItem>
                </ul>
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

    .sfxonItamList {
        border: 1px solid var(--color-border);
        border-radius: var(--border-radius-large);
        list-style: none;
        margin: 12px auto;
        max-width: 900px;
        overflow: hidden;
        padding: 0;
    }

    .sfxonItamList > :global(li) + :global(li) {
        border-top: 1px solid var(--color-border);
    }
</style>
