<script setup lang="ts" generic="T">

import { translate as t } from '@nextcloud/l10n'

const props = defineProps<{
    page: number,
    listState: any
}>()

const emit = defineEmits<{
    (e: 'update:page', value: number): void
}>()

</script>
<template>
    <div class="device-list__pagination">
        <button :disabled="page <= 1" @click="emit('update:page', page - 1)">‹</button>

        <template v-for="p in listState.totalPages" :key="p">
            <!-- Fenster: erste, letzte, und ±2 um aktuelle Seite -->
            <template v-if="p === 1 || p === listState.totalPages || (p >= page - 2 && p <= page + 2)">
                <button :class="{ active: p === page }" @click="emit('update:page', p)">{{ p }}</button>
            </template>
            <span v-else-if="p === page - 3 || p === page + 3">…</span>
        </template>

        <button :disabled="page >= listState.totalPages" @click="emit('update:page', page + 1)">›</button>

        <span class="device-list__info" v-if="listState.total > 0">
            {{ (page - 1) * listState.limit + 1 }}–{{ Math.min(page * listState.limit, listState.total) }}
            {{ t('sfxonitam', 'von') }} {{ listState.total }}
        </span>
        <span class="device-list__info" v-else>
            Keine Ergebnisse gefunden
        </span>
    </div>
</template>