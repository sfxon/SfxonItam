<script setup lang="ts" generic="T">

import { mdiChevronUp, mdiChevronDown } from '@mdi/js'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'

const props = defineProps<{
    columns: T[],
    dataArray: T[],
    dataArrayKey: "id", // This is a column in the given data, that tells, which key to use.
    deleteCallback: null,
    editCallback: null,
    orderBy: "",
    orderByCallback: null
    orderDirection: "ASC",
}>()

</script>
<template>
    <thead :class="$style.sfxonTableHeader">
        <tr>
            <th
                v-for="col in columns"
                :key="col.key"
                :class="{ active: props.orderBy === col.key }"
                :disabled="!col.sortable"
                :aria-sort="props.orderBy === col.key ? (props.orderDirection === 'ASC' ? 'ascending' : 'descending') : undefined"
                @click="col.sortable && props.orderByCallback !== null && props.orderByCallback(col.key)"
            >
                {{ col.label }}
                <NcIconSvgWrapper
                    v-if="col.sortable && props.orderBy === col.key"
                    :path="props.orderDirection === 'ASC' ? mdiChevronUp : mdiChevronDown"
                    :size="16"
                />
            </th>
        </tr>
    </thead>
</template>

<style module>
    .sfxonTableHeader th {
        background-color: var(--color-primary-element-light);
        color: var(--color-primary-element-light-text);
        cursor: pointer;
        font-size: var(--default-font-size);
        font-weight: bold;
        margin: 3px;
        min-height: var(--default-clickable-area);
        padding: calc((var(--default-clickable-area) - 1lh)/2) calc(3*var(--default-grid-baseline));
        width: auto;
    }

    .sfxonTableHeader th[disabled="true"] {
        background-color: var(--color-background-dark);
        color: var(--color-main-text);
        cursor: default;
        opacity: .5;
    }

    .sfxonTableHeader th span:global(.icon-vue) {
        display: inline;
        vertical-align: middle;
    }
</style>
