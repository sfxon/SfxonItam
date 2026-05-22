<script setup lang="ts">

const props = defineProps<{
    titleLabel: string
}>()

</script>

<template>
    <div :class="$style.sfxonItamHeader">
        <div class="content-title" v-html="titleLabel"></div>
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
    padding: var(--app-navigation-padding);
    padding-left: calc(var(--default-clickable-area) + 2 * var(--app-navigation-padding, 4px));
}

.sfxonItamHeaderSidebarToggleBtn {
    margin-left: auto;
    margin-right: 0;
}
</style>
