<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { mdiPlus } from '@mdi/js'
import NcAppContent from '@nextcloud/vue/components/NcAppContent'
import NcAppNavigation from '@nextcloud/vue/components/NcAppNavigation'
import NcAppNavigationList from '@nextcloud/vue/components/NcAppNavigationList'
import NcAppNavigationNew from '@nextcloud/vue/components/NcAppNavigationNew'
import NcContent from '@nextcloud/vue/components/NcContent'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import NcNoteCard from '@nextcloud/vue/components/NcNoteCard'
import NcSelect from '@nextcloud/vue/components/NcSelect'
import NcTextArea from '@nextcloud/vue/components/NcTextArea'
import NcTextField from '@nextcloud/vue/components/NcTextField'
import { translate as t } from '@nextcloud/l10n'
import { generateUrl } from '@nextcloud/router'
import { useApiErrors } from '@/composables/useApiErrors'
import { fetchPosition, createPosition, updatePosition } from '@/services/PositionService'
import SfxonMainNavigation from '@/components/SfxonMainNavigation'
import { fetchAllLocations } from '@/services/LocationService'

// Formulardaten
const name = ref('')
const selectedLocation = ref<{ id: string; label: string } | null>(null)
const comment = ref('')
const savedSuccessfully = ref(false);
const locationsLoading = ref(false)
const positionLoading = ref(false)
const isSaving = ref(false)

// Fehlerbehandlung
const { fieldErrors, generalError, handleApiError, clearErrors, clearFieldError } = useApiErrors()

// Id und Modus laden.
const positionId = computed(() => {
    const param = new URLSearchParams(window.location.search).get('positionId')
    return param ? parseInt(param, 10) : undefined
})
const isEditMode = computed(() => !!positionId.value)
// Abhängige Entitäten definieren.
const locations = ref<{ id: string; label: string}[]>([])

// Funktionen definieren.
function addItem() {
    window.location.href = generateUrl('/apps/sfxonitam/position/detail')
}

async function loadPosition(id: number): Promise<void> {
    positionLoading.value = true

    try {
        const d = await fetchPosition(id)
        name.value = d.name ?? ''
        selectedLocation.value = locations.value.find(s => s.id == d.locationId) ?? null
        comment.value = d.comment ? d.comment : ''
    } catch (e: any) {
        generalError.value = t('sfxonitam', 'Position konnte nicht geladen werden.')
        console.error('Fehler beim Laden der Position:', e)
    } finally {
        positionLoading.value = false
    }
}

async function loadLocations() {
    locationsLoading.value = true;

    try {
        const data = await fetchAllLocations({})

        locations.value = Object.values(data.locations).map((location: any) => ({
            id: location.id,
            label: location.name
        }))
    } catch(e) {
        console.error('Fehler beim Laden der Locations', e)
    } finally {
        locationsLoading.value = false
    }
}

async function submitForm() {
    clearErrors()
    savedSuccessfully.value = false;
    isSaving.value = true

    const payload = {
        name: name.value,
        locationId: selectedLocation.value?.id ?? null,
        comment: comment.value,
    }

    try {
        const data = isEditMode.value
            ? await updatePosition(positionId.value!, payload)
            : await createPosition(payload)

        // Backend gibt status: 'error' mit HTTP 200 zurück
        if (data?.status === 'error') {
            handleApiError(data, t('sfxonitam', 'Bitte korrigiere die markierten Felder.'))
            return
        }

        savedSuccessfully.value = true;
    } catch (error: any) {
        // HTTP-Fehler (4xx/5xx) – Backend gibt evtl. trotzdem JSON zurück
        const data = error?.response?.data

        if (data?.status === 'error') {
            handleApiError(data, t('sfxonitam', 'Bitte korrigiere die markierten Felder.'))
        } else {
            generalError.value = t('sfxonitam', 'Unbekannter Fehler beim Speichern.')
        }
    }
}

onMounted(async () => {
    await loadLocations()

    if (positionId.value) {
        await loadPosition(positionId.value)
    }
})
</script>

<template>
    <NcContent app-name="sfxonitampositioneditor">
        <NcAppNavigation>
            <NcAppNavigationList>
                <NcAppNavigationNew
                :text="t('sfxonitam', 'Neue Position')"
                @click="addItem"
                >
                    <template #icon>
                        <NcIconSvgWrapper :path="mdiPlus" :size="20" />
                    </template>
                </NcAppNavigationNew>
            </NcAppNavigationList>
            <SfxonMainNavigation :currentPage="'positions'" />
        </NcAppNavigation>

        <!-- Inhaltsbereich -->
        <NcAppContent :class="$style.content">
            <div :class="$style.form">
                <h2>
                    {{ isEditMode
                        ? t('sfxonitam', 'Position bearbeiten')
                        : t('sfxonitam', 'Position erfassen') }}
                </h2>

                <!-- Allgemeine Fehlermeldung -->
                <NcNoteCard
                    v-if="generalError"
                    type="error"
                >
                    {{ generalError }}
                </NcNoteCard>

                <!-- Erfolgsmeldung -->
                <NcNoteCard
                    v-if="savedSuccessfully"
                    type="success"
                >
                    {{ t('sfxonitam', 'Die Änderungen wurden gespeichert.') }}
                </NcNoteCard>

                <!-- Name -->
                <div :class="$style.field">
                    <NcTextField
                        id="name"
                        v-model="name"
                        :label="t('sfxonitam', 'Name')"
                        :placeholder="t('sfxonitam', 'z.B. In Nutzung')"
                        :class="fieldErrors.name ? $style.fieldError : ''"
                        @input="clearFieldError('name')"
                    />
                    <span v-if="fieldErrors.name" :class="$style.errorText">
                        {{ fieldErrors.name }}
                    </span>
                </div>

                <!-- Locatoin -->
                <div :class="$style.field">
                    <label for="location-select" :class="$style.label">
                        {{ t('sfxonitam', 'Location') }}
                    </label>
                    <NcSelect
                        id="device-status-select"
                        v-model="selectedLocation"
                        :options="locations"
                        :loading="locationsLoading"
                        :placeholder="t('sfxonitam', 'Position auswählen')"
                        :label="'label'"
                        track-by="id"
                        :class="fieldErrors.locationId ? $style.fieldError : ''"
                        @input="clearFieldError('locationId')"
                    />
                    <span v-if="fieldErrors.locationId" :class="$style.errorText">
                        {{ fieldErrors.locationId }}
                    </span>
                </div>

                <!-- Kommentar -->
                <div :class="$style.field">
                    <NcTextArea
                        id="comment"
                        v-model="comment"
                        :label="t('sfxonitam', 'Beschreibung/Kommentare')"
                        :class="fieldErrors.comment ? $style.fieldError : ''"
                        @input="clearFieldError('comment')"
                    />
                    <span v-if="fieldErrors.comment" :class="$style.errorText">
                        {{ fieldErrors.comment }}
                    </span>
                </div>


                <!-- Absenden -->
                <div :class="$style.actions">
                    <NcButton variant="primary" @click="submitForm">
                        {{ t('sfxonitam', 'Speichern') }}
                    </NcButton>
                </div>
            </div>
        </NcAppContent>
    </NcContent>
</template>

<style module>
.content {
    display: flex;
    justify-content: center;
    margin: 16px;
}

.form {
    width: 100%;
    max-width: 480px;
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.field {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.label {
    font-weight: bold;
    font-size: 0.875rem;
    color: var(--color-text-maxcontrast);
}

.actions {
    display: flex;
    justify-content: flex-end;
    margin-top: 8px;
}

.fieldError :deep(input),
.fieldError :deep(.v-select) {
    border-color: var(--color-error) !important;
    box-shadow: 0 0 0 2px var(--color-error-hover) !important;
}

.errorText {
    color: var(--color-element-error);
    margin-top: 2px;
}
</style>
