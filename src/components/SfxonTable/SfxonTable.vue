<script setup lang="ts" generic="T">

import { ref } from 'vue'
import { mdiPlus, mdiPencil, mdiTrashCan, mdiChevronUp, mdiChevronDown, mdiDelete } from '@mdi/js'
import NcActions from '@nextcloud/vue/components/NcActions'
import NcActionButton from '@nextcloud/vue/components/NcActionButton'
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
    <table :class="$style.sfxonTable">
        <thead>
            <tr>
                <th
                    v-for="col in columns"
                    :key="col.key"
                    :class="{ active: props.orderBy === col.key }"
                    :disabled="!col.sortable"
                    :aria-sort="props.orderBy === col.key ? (direction === 'ASC' ? 'ascending' : 'descending') : undefined"
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
        <tbody>
            <tr v-for="dataRow in props.dataArray" :key="dataRow[props.dataArrayKey]">
                <td
                    v-for="col in columns"
                    :key="col.key"
                >
                    <span v-if="col.type == null">
                        {{ dataRow[col.key] }}
                    </span>
                    <span v-else-if="col.type === 'actions'">
                        <NcActions>
                            <NcActionButton @click="props.editCallback && props.editCallback(dataRow)">
                                <template #icon>
                                    <NcIconSvgWrapper :path="mdiPencil" :size="20" />
                                </template>
                                Bearbeiten
                            </NcActionButton>
                            <NcActionButton @click="props.deleteCallback && props.deleteCallback(dataRow)">
                                <template #icon>
                                    <NcIconSvgWrapper :path="mdiDelete" :size="20" />
                                </template>
                                Löschen
                            </NcActionButton>
                        </NcActions>
                    </span>
                </td>
            </tr>
        </tbody>
    </table>
</template>

<style module>
    .sfxonTable {
        border-collapse: collapse;
        min-width: 100%;
    }

    .sfxonTable th span:global(.icon-vue) {
        display: inline;
    }

    .sfxonTable th, .sfxonTable td {
        border: 1px solid var(--color-main-background);
        padding: 0px 6px;
    }

    .sfxonTable th {
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

    .sfxonTable th[disabled="true"] {
        background-color: var(--color-background-dark);
        color: var(--color-main-text);
        cursor: default;
        opacity: .5;
    }

    .sfxonTable td:empty,
    .sfxonTable td span:empty {
        display: inline-block;
        min-width: 2rem;
    }
</style>
