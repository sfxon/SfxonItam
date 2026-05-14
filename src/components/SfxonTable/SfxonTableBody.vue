<script setup lang="ts" generic="T">

import SfxonTableRow from './SfxonTableRow.vue';

const props = defineProps<{
    columns: T[],
    dataArray: T[],
    dataArrayKey: "id", // This is a column in the given data, that tells, which key to use.
    deleteCallback: null,
    editCallback: null,
    orderBy: "",
    orderByCallback: null
    orderDirection: "ASC",
    relatedEntityData: T[],
}>()

function handleRowLeave(dataRow: any) {
    for (const col of props.columns) {
        col.rowLeaveHandler?.(dataRow)
    }
}
</script>
<template>
    <tbody>
        <tr
            v-for="dataRow in props.dataArray"
            :key="dataRow[props.dataArrayKey]"
            @mouseleave="handleRowLeave(dataRow)"
        >
            <SfxonTableRow
                :columns="props.columns"
                :dataArray="props.dataArray"
                :dataArrayKey="props.dataArrayKey"
                :dataRow="dataRow"
                :deleteCallback="props.deleteCallback"
                :editCallback="props.editCallback"
                :orderBy="props.orderBy"
                :orderByCallback="props.orderByCallback"
                :orderDirection="props.orderDirection"
                :relatedEntityData="props.relatedEntityData"
            />
        </tr>
    </tbody>
</template>