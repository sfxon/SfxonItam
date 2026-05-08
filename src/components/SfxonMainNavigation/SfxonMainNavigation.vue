<script setup lang="ts">

import NcAppNavigationCaption from '@nextcloud/vue/components/NcAppNavigationCaption'
import NcAppNavigationItem from '@nextcloud/vue/components/NcAppNavigationItem'
import NcAppNavigationList from '@nextcloud/vue/components/NcAppNavigationList'
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
        case 'deviceTypes':
            window.location.href = generateUrl(`/apps/sfxonitam/device-type`)
            break
        case 'itamUsers':
            window.location.href = generateUrl(`/apps/sfxonitam/itam-user`)
            break
        case 'locations':
            window.location.href = generateUrl(`/apps/sfxonitam/location`)
            break
        case 'manufacturers':
            window.location.href = generateUrl(`/apps/sfxonitam/manufacturer`)
            break
        case 'positions':
            window.location.href = generateUrl(`/apps/sfxonitam/position`)
            break
    }
}

</script>
<template>
    <NcAppNavigationCaption heading-id="bereiche-heading" is-heading name="Bereiche" />
    <NcAppNavigationList aria-labelledby="bereiche-heading">
        <NcAppNavigationItem name="Geräte Verwaltung" @click="navigateToPage('devices')" :active="currentPage == 'devices'" />
    </NcAppNavigationList>

    <NcAppNavigationCaption heading-id="stammdaten-heading" is-heading name="Stammdaten" />
    <NcAppNavigationList aria-labelledby="stammdaten-heading">
        <NcAppNavigationItem name="Geräte-Status" @click="navigateToPage('deviceStatis')" :active="currentPage == 'deviceStatis'" />
        <NcAppNavigationItem name="Geräte-Typen" @click="navigateToPage('deviceTypes')" :active="currentPage == 'deviceTypes'" />
        <NcAppNavigationItem name="Hersteller" @click="navigateToPage('manufacturers')" :active="currentPage == 'manufacturers'" />
        <NcAppNavigationItem name="Mitarbeiter" @click="navigateToPage('itamUsers')" :active="currentPage == 'itamUsers'" />
        <NcAppNavigationItem name="Positionen" @click="navigateToPage('positions')" :active="currentPage == 'positions'" />
        <NcAppNavigationItem name="Standorte" @click="navigateToPage('locations')" :active="currentPage == 'locations'" />
    </NcAppNavigationList>
</template>