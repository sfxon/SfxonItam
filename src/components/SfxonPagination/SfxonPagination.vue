<script setup lang="ts" generic="T">

import { translate as t } from '@nextcloud/l10n'

const props = defineProps<{
    limit: number,
    page: number,
    total: number,
    totalPages: number,
}>()

</script>
<template>
    <div v-if="totalPages > 1" class="device-list__pagination">
        <button :disabled="page === 1" @click="page--">‹</button>

        <template v-for="p in totalPages" :key="p">
            <!-- Fenster: erste, letzte, und ±2 um aktuelle Seite -->
            <template v-if="p === 1 || p === totalPages || (p >= page - 2 && p <= page + 2)">
                <button :class="{ active: p === page }" @click="page = p">{{ p }}</button>
            </template>
            <span v-else-if="p === page - 3 || p === page + 3">…</span>
        </template>

        <button :disabled="page === totalPages" @click="page++">›</button>

        <span class="device-list__info">
            {{ (page - 1) * limit + 1 }}–{{ Math.min(page * limit, total) }}
            {{ t('sfxonitam', 'von') }} {{ total }}
        </span>
    </div>
</template>