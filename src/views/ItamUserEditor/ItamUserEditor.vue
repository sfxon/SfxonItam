<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import NcAppContent from '@nextcloud/vue/components/NcAppContent'
import NcAppNavigation from '@nextcloud/vue/components/NcAppNavigation'
import NcAppNavigationList from '@nextcloud/vue/components/NcAppNavigationList'
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
import { fetchItamUser, createItamUser, updateItamUser } from '@/services/ItamUserService'
import SfxonMainNavigation from '@/components/SfxonMainNavigation'

// Formulardaten
const firstname = ref('')
const lastname = ref('')
const email = ref('')
const comment = ref('')
const savedSuccessfully = ref(false);
const itamUserLoading = ref(false)
const isSaving = ref(false)

// Fehlerbehandlung
const { fieldErrors, generalError, handleApiError, clearErrors, clearFieldError } = useApiErrors()

// Id und Modus laden.
const itamUserId = computed(() => {
    const param = new URLSearchParams(window.location.search).get('itamUserId')
    return param ? parseInt(param, 10) : undefined
})
const isEditMode = computed(() => !!itamUserId.value)

function addItem() {
    window.location.href = generateUrl('/apps/sfxonitam/itam-user/detail')
}

// Funktionen definieren.
async function loadItamUser(id: number): Promise<void> {
    itamUserLoading.value = true

    try {
        const d = await fetchItamUser(id)
        firstname.value = d.firstname ?? ''
        lastname.value = d.lastname ?? ''
        email.value = d.email ?? ''
        comment.value = d.comment ? d.comment : ''
    } catch (e: any) {
        generalError.value = t('sfxonitam', 'Mitarbeiter konnte nicht geladen werden.')
        console.error('Fehler beim Laden des Mitarbeiters:', e)
    } finally {
        itamUserLoading.value = false
    }
}

async function submitForm() {
    clearErrors()
    savedSuccessfully.value = false;
    isSaving.value = true

    const payload = {
        firstname: firstname.value,
        lastname: lastname.value,
        email: email.value,
        comment: comment.value,
    }

    try {
        const data = isEditMode.value
            ? await updateItamUser(itamUserId.value!, payload)
            : await createItamUser(payload)

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
    if (itamUserId.value) {
        await loadItamUser(itamUserId.value)
    }
})
</script>

<template>
    <NcContent app-name="sfxonitamitamusereditor">
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
        <NcAppContent :class="$style.content">
            <div :class="$style.form">
                <h2>
                    {{ isEditMode
                        ? t('sfxonitam', 'Mitarbeiter bearbeiten')
                        : t('sfxonitam', 'Mitarbeiter erfassen') }}
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

                <!-- Firstname -->
                <div :class="$style.field">
                    <NcTextField
                        id="firstname"
                        v-model="firstname"
                        :label="t('sfxonitam', 'Firstname')"
                        :placeholder="t('sfxonitam', 'E.g. Exs')"
                        :class="fieldErrors.firstname ? $style.fieldError : ''"
                        @input="clearFieldError('firstname')"
                    />
                    <span v-if="fieldErrors.firstname" :class="$style.errorText">
                        {{ fieldErrors.firstname }}
                    </span>
                </div>

                <!-- Lastname -->
                <div :class="$style.field">
                    <NcTextField
                        id="lastname"
                        v-model="lastname"
                        :label="t('sfxonitam', 'Lastname')"
                        :placeholder="t('sfxonitam', 'E.g. Ample')"
                        :class="fieldErrors.lastname ? $style.fieldError : ''"
                        @input="clearFieldError('lastname')"
                    />
                    <span v-if="fieldErrors.lastname" :class="$style.errorText">
                        {{ fieldErrors.lastname }}
                    </span>
                </div>

                <!-- Email -->
                <div :class="$style.field">
                    <NcTextField
                        id="email"
                        v-model="email"
                        :label="t('sfxonitam', 'Email')"
                        :placeholder="t('sfxonitam', 'E.g. me@example.com')"
                        :class="fieldErrors.email ? $style.fieldError : ''"
                        @input="clearFieldError('email')"
                    />
                    <span v-if="fieldErrors.email" :class="$style.errorText">
                        {{ fieldErrors.email }}
                    </span>
                </div>

                <!-- Comment -->
                <div :class="$style.field">
                    <NcTextArea
                        id="comment"
                        v-model="comment"
                        :label="t('sfxonitam', 'Description/Comment')"
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
