<script setup lang="ts">
import { ref, watch, onMounted } from 'vue'
import NcAppContent from '@nextcloud/vue/components/NcAppContent'
import NcAppNavigation from '@nextcloud/vue/components/NcAppNavigation'
import NcAppNavigationList from '@nextcloud/vue/components/NcAppNavigationList'
import NcAppNavigationNew from '@nextcloud/vue/components/NcAppNavigationNew'
import NcContent from '@nextcloud/vue/components/NcContent'
import { mdiPlus } from '@mdi/js'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import NcLoadingIcon from '@nextcloud/vue/components/NcLoadingIcon'
import { translate as t } from '@nextcloud/l10n'
import { generateUrl } from '@nextcloud/router'
import SfxonMainNavigation from '@/components/SfxonMainNavigation'
import SfxonPagination from '@/components/SfxonPagination'
import SfxonTable from '@/components/SfxonTable'
import { useListState } from '@/composables/useListState'
import { fetchItamUsers, deleteItamUser } from '@/services/ItamUserService'
import type { ItamUser } from '@/services/ItamUserService'
import NcDialog from '@nextcloud/vue/components/NcDialog'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcNoteCard from '@nextcloud/vue/components/NcNoteCard'

const loading   = ref(false)
const error     = ref<string | null>(null)
const itamUsers   = ref<ItamUser[]>([])
const listState = useListState(25, 'email')
const itamUserToDelete = ref<ItamUser | null>(null)
const generalError = ref<string>('')

const columns = [
    { key: 'firstname', label: t('sfxonitam', 'Firstname'), sortable: true },
    { key: 'lastname', label: t('sfxonitam', 'Lastname'), sortable: true },
    { key: 'email', label: t('sfxonitam', 'Email'), sortable: true },
    { key: 'comment', label: t('sfxonitam', 'Beschreibung/Kommentare'), sortable: false },
    { type: 'actions', label: t('sfxonitam', 'Aktion'), sortable: false },
];

function addItem() {
    window.location.href = generateUrl('/apps/sfxonitam/itam-user/detail')
}

function cancelDelete() {
    itamUserToDelete.value = null
}

async function confirmDelete() {
    if (!itamUserToDelete.value) {
        return
    }

    try {
        let result = await deleteItamUser(itamUserToDelete.value.id)
    } catch (e: any) {
        itamUserToDelete.value = null

        if(e.response && e.response.status == 422) {
            generalError.value = e.response.data.errors.join('<br>')
        } else {
            generalError.value = 'An error occured on delete.'
        }
        return;
    }

    itamUserToDelete.value = null
    await loadItamUsers()
}

async function loadItamUsers() {
    generalError.value = ''
    loading.value = true
    error.value = null

    try {
        const data = await fetchItamUsers({
            orderBy: listState.orderBy,
            direction: listState.orderDirection,
            page: listState.page,
            limit: listState.limit
        })
        itamUsers.value = data.itamUsers.mainData
        listState.total = data.total
    } catch (e) {
        error.value = t('sfxonitam', 'Error while loading ItamUsers')
    } finally {
        loading.value = false
    }
}

function onEditItamUser(itamUser: ItamUser) {
    window.location.href = generateUrl(`/apps/sfxonitam/itam-user/detail?itamUserId=${itamUser.id}`);
}

async function onDeleteItamUser(itamUser: ItamUser) {
    generalError.value = ''
    itamUserToDelete.value = itamUser
}

watch(() => listState, loadItamUsers, { deep: true })
onMounted(loadItamUsers)
</script>

<template>
    <NcContent app-name="sfxonitam">
        <NcAppNavigation>
            <NcAppNavigationList>
                <NcAppNavigationNew
                :text="t('sfxonitam', 'Neuer Mitarbeiter')"
                @click="addItem"
                >
                    <template #icon>
                        <NcIconSvgWrapper :path="mdiPlus" :size="20" />
                    </template>
                </NcAppNavigationNew>
            </NcAppNavigationList>
            <SfxonMainNavigation :currentPage="'itamUsers'" />
        </NcAppNavigation>

        <!-- Inhaltsbereich -->
        <NcAppContent>
            <div :class="$style.sfxonItamHeader">
                Mitarbeiter-Verwaltung
            </div>

            <!-- Allgemeine Fehlermeldung -->
            <div :class="$style.sfxonItamGeneralError">
                <NcNoteCard
                    v-if="generalError"
                    type="error"
                >
                    {{ generalError }}
                </NcNoteCard>
            </div>

            <div :class="$style.sfxonItamContent">
                <!-- Fehler -->
                <div v-if="error" class="itamUsers-list__error">{{ error }}</div>

                <!-- Ladeindikator -->
                <div v-else-if="loading" class="itamUsers-list__loading">
                    <NcLoadingIcon :size="32" />
                </div>

                <!-- Leerer Zustand -->
                <div v-else-if="itamUsers.length === 0" class="itamUsers-list__empty">
                    {{ t('sfxonitam', 'Keine Mitarbeiter gefunden.') }}
                </div>

                <SfxonTable
                    :columns="columns"
                    :dataArray="itamUsers"
                    :dataArrayKey="'id'"
                    :deleteCallback="onDeleteItamUser"
                    :editCallback="onEditItamUser"
                    :listState="listState"
                    :orderByCallback="listState.sortBy"
                />

                <SfxonPagination
                    v-model:page="listState.page"
                    :listState="listState"
                />
            </div>
        </NcAppContent>
    </NcContent>

    <NcDialog
        v-if="itamUserToDelete"
        :name="t('sfxonitam', 'Mitarbeiter löschen')"
        :open="!!itamUserToDelete"
        @closing="cancelDelete"
    >
        <p>
            {{ t('sfxonitam', `Mitarbeiter „${itamUserToDelete.name}" wirklich löschen?`) }}
        </p>

        <template #actions>
            <NcButton 
                variant="tertiary" 
                @click="cancelDelete">
                {{ t('sfxonitam', 'Abbrechen') }}
            </NcButton>
            <NcButton
                variant="error"
                @click="confirmDelete">
                {{ t('sfxonitam', 'Löschen') }}
            </NcButton>
        </template>
    </NcDialog>
</template>

<style module>
    .sfxonItamHeader {
        align-items: center;
        display: flex;
        flex: 0 0;
        font-weight: bold;
        gap: var(--default-grid-baseline);
        margin-block: var(--app-navigation-padding, 4px);
        margin-inline: calc(var(--default-clickable-area) + 2*var(--app-navigation-padding, 4px)) var(--app-navigation-padding, 4px);
        max-width: 100%;
        min-height: 32px;
    }

    .sfxonItamContent {
        padding-left: 12px;
        padding-right: 12px;
    }

    .sfxonItamGeneralError {
        padding-left: 12px;
        padding-right: 12px;
    }
</style>
