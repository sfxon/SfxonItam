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
}>()

</script>
<template>
    <tbody>
        <tr v-for="dataRow in props.dataArray" :key="dataRow[props.dataArrayKey]">
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
            />
        </tr>
    </tbody>
</template>