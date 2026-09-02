<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { generateUrl } from '@nextcloud/router'
import { mdiPlus } from '@mdi/js'
import NcAppContent from '@nextcloud/vue/components/NcAppContent'
import NcAppNavigation from '@nextcloud/vue/components/NcAppNavigation'
import NcAppNavigationList from '@nextcloud/vue/components/NcAppNavigationList'
import NcAppNavigationNew from '@nextcloud/vue/components/NcAppNavigationNew'
import NcContent from '@nextcloud/vue/components/NcContent'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcNoteCard from '@nextcloud/vue/components/NcNoteCard'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import { fetchItamUser, createItamUser, updateItamUser } from '@/services/ItamUserService'
import SfxonCustomFields from '@/components/SfxonCustomFields'
import SfxonItamHeaderBc, { type BreadcrumbItem } from '@/components/SfxonItamHeaderBc'
import SfxonSaveBadge from '@/components/SfxonSaveBadge'
import SfxonEditorFormInput from '@/components/SfxonEditorFormInput'
import SfxonEditorFormTextareaLabeled from '@/components/SfxonEditorFormTextareaLabeled'
import SfxonEditorStyles from '@/components/SfxonEditor/SfxonEditor.module.css'
import SfxonMainNavigation from '@/components/SfxonMainNavigation'
import { translate as t } from '@nextcloud/l10n'
import { useApiErrors } from '@/composables/useApiErrors'
import { useSaveBadge } from '@/composables/useSaveBadge'

// Formulardaten
const firstname = ref('')
const lastname = ref('')
const email = ref('')
const props = defineProps({
    customFields: {
        type: Array,
        default: () => [],
    },
})

const comment = ref('')
const itamUserLoading = ref(false)
const isSaving = ref(false)
const { fieldErrors, generalError, handleApiError, clearErrors, clearFieldError } = useApiErrors()
const { visible: saveVisible, isReflash: saveIsReflash, flashKey: saveFlashKey, trigger: triggerSaveSuccess } = useSaveBadge()
const itamUserId = computed(() => {
    const param = new URLSearchParams(window.location.search).get('itamUserId')
    return param ? parseInt(param, 10) : undefined
})
const isEditMode = computed(() => !!itamUserId.value)
const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    {
        label: t('sfxonitam', 'User'),
        link: generateUrl('/apps/sfxonitam/itam-user/'),
        forceIconText: true,
        disableDrop: true,
    },
    {
        label: isEditMode.value ? t('sfxonitam', 'Edit') : t('sfxonitam', 'Create'),
        clickable: false,
        forceIconText: true,
        disableDrop: true,
    },
])

const customFieldsRef = ref<InstanceType<typeof SfxonCustomFields> | null>(null)
const customFieldValues = ref<Record<string, unknown>>({})

function onCustomFieldValuesUpdate(newValues: Record<string, unknown>) {
    customFieldValues.value = newValues
}

const customFieldInitialValues = ref<Record<string, unknown>>({})


function addItem() {
    window.location.href = generateUrl('/apps/sfxonitam/itam-user/detail')
}

async function loadItamUser(id: number): Promise<void> {
    try {
        itamUserLoading.value = true
        const data = await fetchItamUser(id)
        const d = data.mainData
        firstname.value = d.firstname ?? ''
        lastname.value = d.lastname ?? ''
        email.value = d.email ?? ''
        comment.value = d.comment ? d.comment : ''
        customFieldInitialValues.value = d.customFields ?? {}
    } catch (e: any) {
        generalError.value = t('sfxonitam', 'Itam Users could not be loaded.')
        console.error('Error while loading ItamUser: ', e)
    } finally {
        itamUserLoading.value = false
    }
}

async function save() {
    const submitSuccess = await submitForm()

    if(submitSuccess !== false && typeof itamUserId.value === 'undefined') {
        window.location.href = generateUrl('apps/sfxonitam/itam-user/detail?itamUserId=' + submitSuccess)
    }
}

async function saveAndBack() {
    const submitSuccess = await submitForm()

    if (submitSuccess !== false) {
        window.location.href = generateUrl('/apps/sfxonitam/itam-user')
    }
}

async function saveAndNew() {
    const submitSuccess = await submitForm()
    
    if (submitSuccess !== false) {
        window.location.href = generateUrl('/apps/sfxonitam/itam-user/detail')
    }
}

async function submitForm() {
    clearErrors()
    isSaving.value = true

    try {
        await customFieldsRef.value?.uploadPendingFiles()
    } catch (e) {
        console.error('Image upload failed:', e)
        generalError.value = t('sfxonitam', 'Image upload failed.')
        isSaving.value = false
        return false
    }

    const payload = {
        firstname: firstname.value,
        lastname: lastname.value,
        email: email.value,
        comment: comment.value,
        customFields: customFieldValues.value,
    }

    try {
        const data = isEditMode.value
            ? await updateItamUser(itamUserId.value!, payload)
            : await createItamUser(payload)

        // Backend gibt status: 'error' mit HTTP 200 zurück
        if (data?.status === 'error') {
            handleApiError(data, t('sfxonitam', 'Please correct the highlighted fields.'))
            return false
        }

        triggerSaveSuccess()
        isSaving.value = false
        return data.id
    } catch (error: any) {
        // HTTP-Fehler (4xx/5xx) – Backend gibt evtl. trotzdem JSON zurück
        const data = error?.response?.data

        if (data?.status === 'error') {
            handleApiError(data, t('sfxonitam', 'Please correct the highlighted fields.'))
        } else {
            generalError.value = t('sfxonitam', 'Unknown error while saving.')
        }

        isSaving.value = false
    }

    return false
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
                :text="t('sfxonitam', 'Add User')"
                @click="addItem"
                >
                    <template #icon>
                        <NcIconSvgWrapper :path="mdiPlus" :size="20" />
                    </template>
                </NcAppNavigationNew>
            </NcAppNavigationList>
            <SfxonMainNavigation :currentPage="'itamUsers'" />
        </NcAppNavigation>

        <NcAppContent>
            <SfxonItamHeaderBc
                :breadcrumbs="breadcrumbs">
            </SfxonItamHeaderBc>

            <div :class="SfxonEditorStyles.form">
                <div
                    :class="SfxonEditorStyles.myfavNotificationContainer"
                    v-if="generalError"
                >
                    <NcNoteCard type="error">
                        {{ generalError }}
                    </NcNoteCard>
                </div>

                <SfxonSaveBadge
                    :visible="saveVisible"
                    :is-reflash="saveIsReflash"
                    :flash-key="saveFlashKey"
                />

                <div :class="[SfxonEditorStyles.sfxonFormRow]">
                    <div :class="[SfxonEditorStyles.sfxonFormSection, $style.formSection1]">
                        <div :class="[SfxonEditorStyles.sfxonFormColumn]">
                            <SfxonEditorFormInput
                                field="firstname"
                                id="firstname"
                                v-model="firstname"
                                :label="t('sfxonitam', 'Firstname') + ':'"
                                type="text"
                                :placeholder="t('sfxonitam', 'e.g. Joe')"
                                @input="clearFieldError('firstname')"
                                :fieldError="fieldErrors.firstname"
                            />

                            <SfxonEditorFormInput
                                field="lastname"
                                id="lastname"
                                v-model="lastname"
                                :label="t('sfxonitam', 'Lastname') + ':'"
                                type="text"
                                :placeholder="t('sfxonitam', 'e.g. Bloggs')"
                                @input="clearFieldError('lastname')"
                                :fieldError="fieldErrors.lastname"
                            />

                            <SfxonEditorFormInput
                                field="email"
                                id="email"
                                v-model="email"
                                :label="t('sfxonitam', 'Email') + ':'"
                                type="text"
                                :placeholder="t('sfxonitam', 'e.g. joe.bloggs@example.com')"
                                @input="clearFieldError('email')"
                                :fieldError="fieldErrors.email"
                            />

                            <SfxonEditorFormTextareaLabeled
                                field="comment"
                                id="comment"
                                v-model="comment"
                                :label="t('sfxonitam', 'Description/Comment') + ':'"
                                @input="clearFieldError('comment')"
                                :field-error="fieldErrors.comment"
                                :class="$style.commentField"
                            />

                            <SfxonCustomFields
                                ref="customFieldsRef"
                                :customFields="customFields"
                                :field-errors="fieldErrors"
                                :initial-values="customFieldInitialValues"
                                @update:values="onCustomFieldValuesUpdate"
                                @input="(technicalName) => clearFieldError(`customFields.${technicalName}`)"
                            />
                        </div>
                    </div>
                </div>

                <!-- Bottom Action Bar -->
                <div :class="[SfxonEditorStyles.sfxonFormRow, SfxonEditorStyles.sfxonFormRowActionBar]">
                    <div :class="[SfxonEditorStyles.sfxonFormSection, SfxonEditorStyles.sfxonFormSectionSave]">
                        <div :class="SfxonEditorStyles.sfxonFormColumn">
                            <div :class="SfxonEditorStyles.actions">
                                <NcButton
                                    :disabled="isSaving"
                                    variant="secondary"
                                    @click="saveAndBack"
                                >
                                    {{ t('sfxonitam', 'Save & Back') }}
                                </NcButton>

                                <NcButton
                                    :disabled="isSaving"
                                    variant="secondary"
                                    @click="saveAndNew"
                                >
                                    {{ t('sfxonitam', 'Save & New') }}
                                </NcButton>
                                
                                <NcButton
                                    :disabled="isSaving"
                                    variant="primary"
                                    @click="save">
                                    {{ t('sfxonitam', 'Save') }}
                                </NcButton>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </NcAppContent>
    </NcContent>
</template>

<style module>
.formSection1 {
    max-width: 600px;
}

.commentField :deep(textarea) {
    min-height: 160px!important;
}
</style>
