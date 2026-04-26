<script setup lang="ts">
import NcAppContent from '@nextcloud/vue/components/NcAppContent'
import NcContent from '@nextcloud/vue/components/NcContent'
import { ref, computed, onMounted } from 'vue'
import { translate as t } from '@nextcloud/l10n'
import { generateUrl } from '@nextcloud/router'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcTextField from '@nextcloud/vue/components/NcTextField'
import NcDateTimePickerNative from '@nextcloud/vue/components/NcDateTimePickerNative'
import NcSelect from '@nextcloud/vue/components/NcSelect'
import axios from '@nextcloud/axios'
import NcNoteCard from '@nextcloud/vue/components/NcNoteCard'
import { useApiErrors } from '@/composables/useApiErrors'
import { mdiClose } from '@mdi/js'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'

// Formulardaten
const name = ref('')
const purchaseDate = ref<Date | null>(null)
const selectedUser = ref<{ id: string; label: string } | null>(null)
const savedSuccessfully = ref(false);

// Ladezustände
const usersLoading = ref(false)
const deviceLoading = ref(false)
const isSaving = ref(false)

// Fehlerbehandlung
const { fieldErrors, generalError, handleApiError, clearErrors, clearFieldError } = useApiErrors()

// Id und Modus laden.
const deviceId = computed(() => {

    const param = new URLSearchParams(window.location.search).get('deviceId')
    return param ? parseInt(param, 10) : undefined
})
const isEditMode = computed(() => !!deviceId.value)

// User per Nextcloud-API laden
const users = ref<{ id: string; label: string }[]>([])

const toLocalDateString = (date: Date): string => {
    const y = date.getFullYear()
    const m = String(date.getMonth() + 1).padStart(2, '0')
    const d = String(date.getDate()).padStart(2, '0')
    return `${y}-${m}-${d}`
}

// Funktionen definieren.
async function loadDevice(id: number): Promise<void> {
    deviceLoading.value = true

    try {
        const response = await axios.get(
            generateUrl(`/apps/sfxonitam/device/${id}`),
        )
        const d = response.data

        name.value = d.name ?? ''
        purchaseDate.value = d.purchaseDate ? new Date(d.purchaseDate + 'T00:00:00') : null

        // Passenden User aus der bereits geladenen Liste suchen
        selectedUser.value = users.value.find(u => u.id === d.userId) ?? null
    } catch (e: any) {
        generalError.value = t('sfxonitam', 'Gerät konnte nicht geladen werden.')
        console.error('Fehler beim Laden des Geräts:', e)
    } finally {
        deviceLoading.value = false
    }
}

async function loadUsers() {
    usersLoading.value = true

    try {
        const response = await axios.get(
            generateUrl('/ocs/v2.php/cloud/users/details?format=json'),
            { headers: { 'OCS-APIRequest': 'true' } }
        )
        const usersData = response.data?.ocs?.data?.users ?? {}
        users.value = Object.values(usersData).map((user: any) => ({
            id: user.id,
            label: user.displayname,
        }))
    } catch (e) {
        console.error('Fehler beim Laden der User:', e)
    } finally {
        usersLoading.value = false
    }
}

async function submitForm() {
    clearErrors()

    savedSuccessfully.value = false;
    isSaving.value = true

    const payload = {
        name: name.value,
        purchaseDate: purchaseDate.value ? toLocalDateString(purchaseDate.value) : null,
        userId: selectedUser.value?.id ?? null,
    }

    try {
        const url = isEditMode.value
            ? generateUrl(`/apps/sfxonitam/device/${deviceId.value}`)
            : generateUrl('/apps/sfxonitam/device/save')

        const response = isEditMode.value
            ? await axios.put(url, payload)
            : await axios.post(url, payload)

        // Backend gibt status: 'error' mit HTTP 200 zurück
        if (response.data?.status === 'error') {
            handleApiError(response.data, t('sfxonitam', 'Bitte korrigiere die markierten Felder.'))
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
    await loadUsers()

    if (deviceId.value) {
        await loadDevice(deviceId.value)
    }
})
</script>

<template>
    <NcContent app-name="sfxonitamdeviceeditor">
        <!-- Inhaltsbereich -->
        <NcAppContent :class="$style.content">
            <div :class="$style.form">
                <h2>
                    {{ isEditMode
                        ? t('sfxonitam', 'Gerät bearbeiten')
                        : t('sfxonitam', 'Gerät erfassen') }}
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

                <!-- Geräte-ID -->
                <div :class="$style.field">
                    <NcTextField
                        id="name"
                        v-model="name"
                        :label="t('sfxonitam', 'Name / Geräte-ID')"
                        :placeholder="t('sfxonitam', 'z.B. JP001')"
                        :class="fieldErrors.name ? $style.fieldError : ''"
                        @input="clearFieldError('name')"
                    />
                    <span v-if="fieldErrors.name" :class="$style.errorText">
                        {{ fieldErrors.name }}
                    </span>
                </div>

                <!-- Rechnungsdatum -->
                <div :class="$style.field">
                    <label for="purchaseDate" :class="$style.label">
                        {{ t('sfxonitam', 'Kaufdatum') }}
                    </label>
                    <div :class="$style.dateRow">
                        <NcDateTimePickerNative
                            id="purchase-date"
                            v-model="purchaseDate"
                            type="date"
                            :class="fieldErrors.purchaseDate ? $style.fieldError : ''"
                            :label="''"
                            @input="clearFieldError('purchaseDate')"
                        />
                        <NcButton
                            :disabled="purchaseDate === null"
                            type="button"
                            :aria-label="t('sfxonitam', 'Datum entfernen')"
                            @click="purchaseDate = null"
                            >
                            <NcIconSvgWrapper
                                :path="mdiClose"
                                :size="16"
                            />
                        </NcButton>
                    </div>

                    <span v-if="fieldErrors.purchaseDate" :class="$style.errorText">
                        {{ fieldErrors.purchaseDate }}
                    </span>
                </div>

                <!-- User-Auswahl -->
                <div :class="$style.field">
                    <label for="user-select" :class="$style.label">
                        {{ t('sfxonitam', 'Benutzer') }}
                    </label>
                    <NcSelect
                        id="user-select"
                        v-model="selectedUser"
                        :options="users"
                        :loading="usersLoading"
                        :placeholder="t('sfxonitam', 'Benutzer auswählen')"
                        :label="'label'"
                        track-by="id"
                        :class="fieldErrors.userId ? $style.fieldError : ''"
                        @input="clearFieldError('userId')"
                    />
                    <span v-if="fieldErrors.userId" :class="$style.errorText">
                        {{ fieldErrors.userId }}
                    </span>
                </div>

                <!-- Absenden -->
                <div :class="$style.actions">
                    <NcButton type="primary" @click="submitForm">
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

.dateRow {
    align-items: center;
    display: flex;
}

.dateRow button {
    max-height: 1rem;
    margin-left: 6px;
    margin-top: 1px;
}

.dateRow :global(.native-datetime-picker) {
    flex-grow: 1;
}
</style>
