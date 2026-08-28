<script setup lang="ts" generic="T">

import { translate as t } from '@nextcloud/l10n'

const props = defineProps<{
    page: number,
    listState: any
}>()

const emit = defineEmits<{
    (e: 'update:page', value: number): void
}>()

const entriesPerPageOptions = [ 10, 25, 50, 100, 500, 1000 ];

</script>
<template>
    <div :class="[$style.customPagination, 'device-list__pagination']">
        <button :disabled="page <= 1" @click="emit('update:page', page - 1)">‹</button>

        <template v-for="p in listState.totalPages" :key="p">
            <!-- Fenster: erste, letzte, und ±2 um aktuelle Seite -->
            <template v-if="p === 1 || p === listState.totalPages || (p >= page - 2 && p <= page + 2)">
                <button :class="{ active: p === page }" @click="emit('update:page', p)">{{ p }}</button>
            </template>
            <span v-else-if="p === page - 3 || p === page + 3">…</span>
        </template>

        <button :disabled="page >= listState.totalPages" @click="emit('update:page', page + 1)">›</button>

        <span :class="$style.deviceListInfo" v-if="listState.total > 0">
            {{ (page - 1) * listState.limit + 1 }}–{{ Math.min(page * listState.limit, listState.total) }}
            {{ t('sfxonitam', 'von') }} {{ listState.total }}
        </span>
        <span :class="$style.deviceListInfo" v-else>
            Keine Ergebnisse gefunden
        </span>

        <span :class="$style.paginationEntriesPerPageContainer">
            <span>Einträge pro Seite: </span>
            <select
                :value="listState.limit"
                @change="listState.setLimit(Number($event.target.value))">
                <option v-for="option in entriesPerPageOptions" :key="option" :value="option">
                    {{ option }}
                </option>
            </select>
        </span>
    </div>
</template>

<style module>
.customPagination {
    display: flex;
}

.paginationEntriesPerPageContainer {
    margin-left: auto;
    margin-right: 0;
}

.paginationEntriesPerPageContainer span {
    margin-right: 6px;
}

.deviceListInfo {
    align-items: center;
    display: flex;
    margin-left: 6px;
}
</style>