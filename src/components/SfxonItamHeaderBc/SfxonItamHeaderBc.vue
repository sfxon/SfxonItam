<script setup lang="ts">

import NcBreadcrumbs from '@nextcloud/vue/components/NcBreadcrumbs'
import NcBreadcrumb from '@nextcloud/vue/components/NcBreadcrumb'

export interface BreadcrumbItem {
    label: string
    clickable?: boolean
    forceIconText?: boolean
    disableDrop?: boolean
    link?: string
}

const props = withDefaults(defineProps<{
    titleLabel: string
    breadcrumbs?: BreadcrumbItem[]
}>(), {
    titleLabel: '',
    breadcrumbs: () => []
})

</script>

<template>
    <div :class="$style.sfxonItamHeader">
        <div class="content-title" v-if="titleLabel !== ''" v-html="titleLabel"></div>
        <div v-else-if="breadcrumbs.length">
            <NcBreadcrumbs root-icon="">
                <NcBreadcrumb
                    v-for="(crumb, index) in breadcrumbs"
                    :key="index"
                    :name="crumb.label"
                    :title="crumb.label"
                    :href="crumb.link"
                    :force-icon-text="crumb.forceIconText"
                    :disable-drop="crumb.disableDrop"
                />
            </NcBreadcrumbs>
        </div>
        <div :class="$style.sfxonItamHeaderSidebarToggleBtn">
            <slot name="actionButtonsRight"></slot>
        </div>
    </div>
</template>

<style module>
.sfxonItamHeader {
    align-items: center;
    background-color: var(--color-background-assistant);
    display: flex;
    flex: 0 0;
    font-weight: bold;
    gap: var(--default-grid-baseline);
    max-width: 100%;
    min-height: 50px;
    padding-bottom: 0!important;
    padding-left: calc(var(--default-clickable-area) + 2 * var(--app-navigation-padding, 4px));
    padding-top: 0!important;
}

.sfxonItamHeaderSidebarToggleBtn {
    margin-left: auto;
    margin-right: 0;
}
</style>
