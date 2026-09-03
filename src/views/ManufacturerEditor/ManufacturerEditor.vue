<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { generateUrl } from '@nextcloud/router'
import { mdiPlus } from '@mdi/js'
import { fetchManufacturer, createManufacturer, updateManufacturer } from '@/services/ManufacturerService'
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
const manufacturerLoading = ref(false)
const isSaving = ref(false)
const { fieldErrors, generalError, handleApiError, clearErrors, clearFieldError } = useApiErrors()
const { visible: saveVisible, isReflash: saveIsReflash, flashKey: saveFlashKey, trigger: triggerSaveSuccess } = useSaveBadge()
const manufacturerId = computed(() => {
    const param = new URLSearchParams(window.location.search).get('manufacturerId')
    return param ? parseInt(param, 10) : undefined
})
const isEditMode = computed(() => !!manufacturerId.value)
const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    {
        label: t('sfxonitam', 'Manufacturers'),
        link: generateUrl('/apps/sfxonitam/manufacturer/'),
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
    window.location.href = generateUrl('/apps/sfxonitam/manufacturer/detail')
}

async function loadManufacturer(id: number): Promise<void> {
    manufacturerLoading.value = true

    try {
        const data = await fetchManufacturer(id)
        const d = data.mainData
        name.value = d.name ?? ''
        comment.value = d.comment ? d.comment : ''
        customFieldInitialValues.value = d.customFields ?? {}
    } catch (e: any) {
        generalError.value = t('sfxonitam', 'Manufacturer could not be loaded.')
        console.error('Error while loading manufacturer:', e)
    } finally {
        manufacturerLoading.value = false
    }
}

async function save() {
    const submitSuccess = await submitForm()

    if(submitSuccess !== false && typeof manufacturerId.value === 'undefined') {
        window.location.href = generateUrl('apps/sfxonitam/manufacturer/detail?manufacturerId=' + submitSuccess)
    }
}

async function saveAndBack() {
    const submitSuccess = await submitForm()

    if (submitSuccess !== false) {
        window.location.href = generateUrl('/apps/sfxonitam/manufacturer')
    }
}

async function saveAndNew() {
    const submitSuccess = await submitForm()
    
    if (submitSuccess !== false) {
        window.location.href = generateUrl('/apps/sfxonitam/manufacturer/detail')
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
            ? await updateManufacturer(manufacturerId.value!, payload)
            : await createManufacturer(payload)

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
    if (manufacturerId.value) {
        await loadManufacturer(manufacturerId.value)
    }
})
</script>

<template>
    <NcContent app-name="sfxonitammanufacturereditor">
        <NcAppNavigation>
            <NcAppNavigationList>
                <NcAppNavigationNew
                :text="t('sfxonitam', 'Add manufacturer')"
                @click="addItem"
                >
                    <template #icon>
                        <NcIconSvgWrapper :path="mdiPlus" :size="20" />
                    </template>
                </NcAppNavigationNew>
            </NcAppNavigationList>
            <SfxonMainNavigation :currentPage="'manufacturers'" />
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
                                :placeholder="t('sfxonitam', 'e.g. Oishii Desu')"
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
