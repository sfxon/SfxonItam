<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import NcAppContent from '@nextcloud/vue/components/NcAppContent'
import NcAppNavigation from '@nextcloud/vue/components/NcAppNavigation'
import NcAppNavigationNew from '@nextcloud/vue/components/NcAppNavigationNew'
import NcContent from '@nextcloud/vue/components/NcContent'
import { mdiPlus } from '@mdi/js'
import { translate as t } from '@nextcloud/l10n'
import { generateUrl } from '@nextcloud/router'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcTextArea from '@nextcloud/vue/components/NcTextArea'
import NcTextField from '@nextcloud/vue/components/NcTextField'
import NcNoteCard from '@nextcloud/vue/components/NcNoteCard'
import { useApiErrors } from '@/composables/useApiErrors'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import { fetchLocation, createLocation, updateLocation } from '@/services/LocationService'
import SfxonMainNavigation from '@/components/SfxonMainNavigation'

// Formulardaten
const name = ref('')
const comment = ref('')
const savedSuccessfully = ref(false);
const locationLoading = ref(false)
const isSaving = ref(false)

// Fehlerbehandlung
const { fieldErrors, generalError, handleApiError, clearErrors, clearFieldError } = useApiErrors()

// Id und Modus laden.
const locationId = computed(() => {
    const param = new URLSearchParams(window.location.search).get('locationId')
    return param ? parseInt(param, 10) : undefined
})
const isEditMode = computed(() => !!locationId.value)

function addItem() {
    window.location.href = generateUrl('/apps/sfxonitam/location/detail')
}

// Funktionen definieren.
async function loadLocation(id: number): Promise<void> {
    locationLoading.value = true

    try {
        const d = await fetchLocation(id)
        name.value = d.name ?? ''
        comment.value = d.comment ? d.comment : ''
    } catch (e: any) {
        generalError.value = t('sfxonitam', 'Standort konnte nicht geladen werden.')
        console.error('Fehler beim Laden des Standorts:', e)
    } finally {
        locationLoading.value = false
    }
}

async function submitForm() {
    clearErrors()
    savedSuccessfully.value = false;
    isSaving.value = true

    const payload = {
        name: name.value,
        comment: comment.value,
    }

    try {
        const data = isEditMode.value
            ? await updateLocation(locationId.value!, payload)
            : await createLocation(payload)

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
    if (locationId.value) {
        await loadLocation(locationId.value)
    }
})
</script>

<template>
    <NcContent app-name="sfxonitamlocationeditor">
        <NcAppNavigation>
            <template #list>
                <NcAppNavigationNew
                :text="t('sfxonitam', 'Neuer Standort')"
                @click="addItem"
                >
                    <template #icon>
                        <NcIconSvgWrapper :path="mdiPlus" :size="20" />
                    </template>
                </NcAppNavigationNew>
                <SfxonMainNavigation :currentPage="'locations'" />
            </template>
        </NcAppNavigation>

        <!-- Inhaltsbereich -->
        <NcAppContent :class="$style.content">
            <div :class="$style.form">
                <h2>
                    {{ isEditMode
                        ? t('sfxonitam', 'Standort bearbeiten')
                        : t('sfxonitam', 'Standort erfassen') }}
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
