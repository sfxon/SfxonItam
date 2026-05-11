<script setup lang="ts" generic="T">

import { mdiDelete, mdiPencil } from '@mdi/js'
import NcActions from '@nextcloud/vue/components/NcActions'
import NcActionButton from '@nextcloud/vue/components/NcActionButton'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'

const props = defineProps<{
    columns: T[],
    dataArray: T[],
    dataArrayKey: "id", // This is a column in the given data, that tells, which key to use.
    dataRow: T[],
    deleteCallback: null,
    editCallback: null,
    orderBy: "",
    orderByCallback: null
    orderDirection: "ASC",
    relatedEntityData: T[]
}>()

</script>
<template>
    <td
        v-for="col in columns"
        :key="col.key"
        :class="col.type === 'actions' ? 'action-col' : ''"
    >
        <span v-if="col.type == null">
            {{ props.dataRow[col.key] }}
        </span>
        <span v-else-if="col.type == 'date'">
            {{ props.dataRow[col.key]
                ? new Intl.DateTimeFormat(undefined, { dateStyle: 'medium' }).format(new Date(props.dataRow[col.key]))
                : '&nbsp;'
            }}
        </span>
        <span
            v-else-if="col.type === 'quantityWithUnit'"
        >
            {{ props.dataRow[col.key] != null ? parseFloat(props.dataRow[col.key]) : '' }}
            {{ relatedEntityData[col.relatedEntityName]?.find(item => item.id === props.dataRow[col.relatedEntityKey])?.label ?? props.dataRow[col.relatedEntityName] }}
        </span>
        <span
            v-else-if="col.type === 'relatedEntity'"
        >
            {{ relatedEntityData[col.relatedEntityName]?.find(item => item.id === props.dataRow[col.key])?.label ?? props.dataRow[col.key] }}
        </span>
        <span v-else-if="col.type === 'actions'">
            <NcActions>
                <NcActionButton @click="props.editCallback && props.editCallback(props.dataRow)">
                    <template #icon>
                        <NcIconSvgWrapper :path="mdiPencil" :size="20" />
                    </template>
                    Bearbeiten
                </NcActionButton>
                <NcActionButton @click="props.deleteCallback && props.deleteCallback(props.dataRow)">
                    <template #icon>
                        <NcIconSvgWrapper :path="mdiDelete" :size="20" />
                    </template>
                    Löschen
                </NcActionButton>
            </NcActions>
        </span>
    </td>
</template>