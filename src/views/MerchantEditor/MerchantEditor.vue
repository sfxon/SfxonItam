<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { generateUrl } from '@nextcloud/router'
import { mdiPlus } from '@mdi/js'
import { fetchMerchant, createMerchant, updateMerchant } from '@/services/MerchantService'
import NcAppContent from '@nextcloud/vue/components/NcAppContent'
import NcAppNavigation from '@nextcloud/vue/components/NcAppNavigation'
import NcAppNavigationList from '@nextcloud/vue/components/NcAppNavigationList'
import NcAppNavigationNew from '@nextcloud/vue/components/NcAppNavigationNew'
import NcContent from '@nextcloud/vue/components/NcContent'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import NcNoteCard from '@nextcloud/vue/components/NcNoteCard'
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

const name = ref('')
const props = defineProps({
    customFields: {
        type: Array,
        default: () => [],
    },
})
const comment = ref('')
const merchantLoading = ref(false)
const isSaving = ref(false)
const { fieldErrors, generalError, handleApiError, clearErrors, clearFieldError } = useApiErrors()
const { visible: saveVisible, isReflash: saveIsReflash, flashKey: saveFlashKey, trigger: triggerSaveSuccess } = useSaveBadge()
const merchantId = computed(() => {
    const param = new URLSearchParams(window.location.search).get('merchantId')
    return param ? parseInt(param, 10) : undefined
})
const isEditMode = computed(() => !!merchantId.value)
const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    {
        label: t('sfxonitam', 'Merchants'),
        link: generateUrl('/apps/sfxonitam/merchant/'),
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
    window.location.href = generateUrl('/apps/sfxonitam/merchant/detail')
}

async function loadMerchant(id: number): Promise<void> {
    merchantLoading.value = true

    try {
        const data = await fetchMerchant(id)
        const d = data.mainData
        name.value = d.name ?? ''
        comment.value = d.comment ? d.comment : ''
        customFieldInitialValues.value = d.customFields ?? {}
    } catch (e: any) {
        generalError.value = t('sfxonitam', 'Merchant could not be loaded.')
        console.error('Error while loading merchant:', e)
    } finally {
        merchantLoading.value = false
    }
}

async function save() {
    const submitSuccess = await submitForm()

    if(submitSuccess !== false && typeof merchantId.value === 'undefined') {
        window.location.href = generateUrl('apps/sfxonitam/merchant/detail?merchantId=' + submitSuccess)
    }
}

async function saveAndBack() {
    const submitSuccess = await submitForm()

    if (submitSuccess !== false) {
        window.location.href = generateUrl('/apps/sfxonitam/merchant')
    }
}

async function saveAndNew() {
    const submitSuccess = await submitForm()
    
    if (submitSuccess !== false) {
        window.location.href = generateUrl('/apps/sfxonitam/merchant/detail')
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
        name: name.value,
        comment: comment.value,
        customFields: customFieldValues.value,
    }

    try {
        const data = isEditMode.value
            ? await updateMerchant(merchantId.value!, payload)
            : await createMerchant(payload)

        // Backend returns status: 'error' with HTTP 200.
        if (data?.status === 'error') {
            handleApiError(data, t('sfxonitam', 'Please correct the highlighted fields.'))
            return false
        }

        triggerSaveSuccess()
        isSaving.value = false
        return data.id
    } catch (error: any) {
        // HTTP-Error (4xx/5xx), backend may despite return JSON.
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
    if (merchantId.value) {
        await loadMerchant(merchantId.value)
    }
})
</script>

<template>
    <NcContent app-name="sfxonitammerchanteditor">
        <NcAppNavigation>
            <NcAppNavigationList>
                <NcAppNavigationNew
                :text="t('sfxonitam', 'Add merchant')"
                @click="addItem"
                >
                    <template #icon>
                        <NcIconSvgWrapper :path="mdiPlus" :size="20" />
                    </template>
                </NcAppNavigationNew>
            </NcAppNavigationList>
            <SfxonMainNavigation :currentPage="'merchants'" />
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
                                field="name"
                                id="name"
                                v-model="name"
                                :label="t('sfxonitam', 'Name') + ':'"
                                type="text"
                                :placeholder="t('sfxonitam', 'e.g. Your IT supplier')"
                                @input="clearFieldError('name')"
                                :fieldError="fieldErrors.name"
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

<style module scoped>
.formSection1 {
    max-width: 600px;
}

.commentField :deep(textarea) {
    min-height: 160px!important;
}
</style>
