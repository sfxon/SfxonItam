<script setup lang="ts">
import NcAppContent from '@nextcloud/vue/components/NcAppContent'
import NcAppNavigation from '@nextcloud/vue/components/NcAppNavigation'
import NcAppNavigationNew from '@nextcloud/vue/components/NcAppNavigationNew'
import NcAppNavigationItem from '@nextcloud/vue/components/NcAppNavigationItem'
import NcContent from '@nextcloud/vue/components/NcContent'
import NcListItem from '@nextcloud/vue/components/NcListItem'
import NcActions from '@nextcloud/vue/components/NcActions'
import NcActionButton from '@nextcloud/vue/components/NcActionButton'
import { mdiPlus, mdiPencil, mdiTrashCan } from '@mdi/js'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import { ref, onMounted } from 'vue'
import { translate as t } from '@nextcloud/l10n'
import { generateUrl } from '@nextcloud/router'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcTextField from '@nextcloud/vue/components/NcTextField'
import NcDateTimePicker from '@nextcloud/vue/components/NcDateTimePicker'
import NcSelect from '@nextcloud/vue/components/NcSelect'
import axios from '@nextcloud/axios'
import NcNoteCard from '@nextcloud/vue/components/NcNoteCard'
import { useApiErrors } from '@/composables/useApiErrors'

// Formulardaten
const name = ref('')
const invoiceDate = ref<Date | null>(null)
const selectedUser = ref<{ id: string; label: string } | null>(null)

// User-Liste
const users = ref<{ id: string; label: string }[]>([])
const usersLoading = ref(false)

// Fehlerbehandlung
const { fieldErrors, generalError, handleApiError, clearErrors, clearFieldError } = useApiErrors()

// User per Nextcloud-API laden
onMounted(async () => {
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
})

async function submitForm() {
    clearErrors()

    try {
        const response = await axios.post(
            generateUrl('/apps/sfxonitam/device/save'),
            {
                name:        name.value,
                invoiceDate: invoiceDate.value?.toISOString().split('T')[0] ?? null,
                userId:      selectedUser.value?.id ?? null,
            }
            // Es ist kein manueller CSRF-Header nötig, denn @nextcloud/axios ergänzt ihn automatisch.
        )

        // Backend gibt status: 'error' mit HTTP 200 zurück
        if (response.data?.status === 'error') {
            handleApiError(response.data, t('sfxonitam', 'Bitte korrigiere die markierten Felder.'))
            return
        }

        console.log('Gespeichert:', response.data)
        // Ggf. weiterleiten: window.location.href = generateUrl('/apps/sfxonitam')
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
</script>

<template>
    <NcContent app-name="sfxonitamdeviceeditor">
        <!-- Inhaltsbereich -->
        <NcAppContent :class="$style.content">
            <div :class="$style.form">
                <h2>{{ t('sfxonitam', 'Gerät erfassen') }}</h2>

                <!-- Allgemeine Fehlermeldung -->
                <NcNoteCard
                    v-if="generalError"
                    type="error"
                >
                    {{ generalError }}
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
                    <label for="invoice-date" :class="$style.label">
                        {{ t('sfxonitam', 'Rechnungsdatum') }}
                    </label>
                    <NcDateTimePicker
                        id="invoice-date"
                        v-model="invoiceDate"
                        type="date"
                        :placeholder="t('sfxonitam', 'Datum wählen')"
                        :class="fieldErrors.invoiceDate ? $style.fieldError : ''"
                        @input="clearFieldError('invoiceDate')"
                    />
                    <span v-if="fieldErrors.invoiceDate" :class="$style.errorText">
                        {{ fieldErrors.invoiceDate }}
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
</style>
