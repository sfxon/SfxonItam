<script setup lang="ts">

import NcAppNavigationItem from '@nextcloud/vue/components/NcAppNavigationItem'
import { translate as t } from '@nextcloud/l10n'
import { generateUrl } from '@nextcloud/router'

defineProps({
    currentPage: {
        type: String,
        default: 'devices'
    }
})

function navigateToPage(pageName: String) {
    console.log('navigate to page: ', pageName);

    switch(pageName) {
        case 'devices':
            window.location.href = generateUrl(`/apps/sfxonitam/`)
            break
        case 'deviceStatis':
            window.location.href = generateUrl(`/apps/sfxonitam/device-status`)
            break
        case 'locations':
            window.location.href = generateUrl(`/apps/sfxonitam/location`)
            break
    }
}

</script>
<template>
    <NcAppNavigationItem name="Geräte Verwaltung" @click="navigateToPage('devices')" :active="currentPage == 'devices'" />
    <NcAppNavigationItem name="Geräte-Status" @click="navigateToPage('deviceStatis')" :active="currentPage == 'deviceStatis'" />
    <NcAppNavigationItem name="Standorte" @click="navigateToPage('locations')" :active="currentPage == 'locations'" />
</template>