<script setup lang="ts" generic="T">

import { mdiPencil, mdiDelete } from '@mdi/js'
import NcActions from '@nextcloud/vue/components/NcActions'
import NcActionButton from '@nextcloud/vue/components/NcActionButton'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import SfxonTableHeader from './SfxonTableHeader.vue';

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
        <SfxonTableHeader
            :columns="props.columns"
            :dataArray="props.dataArray"
            :dataArrayKey="props.dataArrayKey"
            :deleteCallback="props.deleteCallback"
            :editCallback="props.editCallback"
            :orderBy="props.orderBy"
            :orderByCallback="props.orderByCallback"
            :orderDirection="props.orderDirection"
        />
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

    .sfxonTable td:empty,
    .sfxonTable td span:empty {
        display: inline-block;
        min-width: 2rem;
    }
</style>
