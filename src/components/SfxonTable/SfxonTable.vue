<script setup lang="ts" generic="T">

import SfxonTableHeader from './SfxonTableHeader.vue';
import SfxonTableBody from './SfxonTableBody.vue';

const props = defineProps<{
    columns: T[],
    dataArray: T[],
    dataArrayKey: "id", // This is a column in the given data, that tells, which key to use.
    deleteCallback: null,
    editCallback: null,
    listState: null
    orderByCallback: null
}>()

</script>
<template>
    <div :class="$style.sfxonTableWrapper">
        <table :class="$style.sfxonTable">
            <SfxonTableHeader
                :columns="props.columns"
                :dataArray="props.dataArray"
                :dataArrayKey="props.dataArrayKey"
                :deleteCallback="props.deleteCallback"
                :editCallback="props.editCallback"
                :listState="props.listState"
                :orderByCallback="props.orderByCallback"
            />
            <SfxonTableBody
                :columns="props.columns"
                :dataArray="props.dataArray"
                :dataArrayKey="props.dataArrayKey"
                :deleteCallback="props.deleteCallback"
                :editCallback="props.editCallback"
                :listState="props.listState"
                :orderByCallback="props.orderByCallback"
            />
        </table>
    </div>
</template>

<style module>
    .sfxonTableWrapper {
        min-width: 100%;
        overflow-x: scroll;
        padding-bottom: 6px; /* Fix Firefox Scrollbar that is hanging over the content, when scroll is activated. */
    }

    .sfxonTable {
        border-collapse: collapse;
        width: 100%;
    }

    .sfxonTable th, .sfxonTable td {
        border: 1px solid var(--color-main-background);
    }

    .sfxonTable td {
        background-color: var(--color-main-background);
        padding: 0 calc(3*var(--default-grid-baseline));
    }

    .sfxonTable td:global(.action-col) {
        padding-left: 0;
        padding-right: 0;
    }

    /* Give the button of the action column a width of 100%. */
    .sfxonTable td:global(.action-col) button,
    .sfxonTable td:global(.action-col) button:global(.button-vue[data-v-09093702]):has(:global(.button-vue__text):empty),
    .sfxonTable td:global(.action-col) button :global(.button-vue__icon),
    .sfxonTable td:global(.action-col) :global(.action-item) {
        border-radius: 0;
        width: 100%!important;
    }

    .sfxonTable tr:nth-child(2n) td {
        background-color: var(--color-background-assistant);
    }

    .sfxonTable tr:hover td, .sfxonTable tr:nth-child(2n):hover td {
        background-color: var(--color-info-hover);
    }

    .sfxonTable td:empty,
    .sfxonTable td span:empty {
        display: inline-block;
        min-width: 2rem;
    }
</style>
