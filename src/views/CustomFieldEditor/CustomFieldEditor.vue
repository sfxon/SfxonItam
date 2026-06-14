<script setup lang="ts">
import { reactive, ref, computed, onMounted, watch } from 'vue'
import { mdiPlus } from '@mdi/js'
import NcAppContent from '@nextcloud/vue/components/NcAppContent'
import NcAppNavigation from '@nextcloud/vue/components/NcAppNavigation'
import NcAppNavigationList from '@nextcloud/vue/components/NcAppNavigationList'
import NcAppNavigationNew from '@nextcloud/vue/components/NcAppNavigationNew'
import NcCheckboxRadioSwitch from '@nextcloud/vue/components/NcCheckboxRadioSwitch'
import NcContent from '@nextcloud/vue/components/NcContent'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import NcNoteCard from '@nextcloud/vue/components/NcNoteCard'
import NcTextArea from '@nextcloud/vue/components/NcTextArea'
import NcTextField from '@nextcloud/vue/components/NcTextField'
import { translate as t } from '@nextcloud/l10n'
import { generateUrl } from '@nextcloud/router'
import { useApiErrors } from '@/composables/useApiErrors'
import { fetchCustomField, createCustomField } from '@/services/CustomFieldService'
import SfxonEditorFormEntitySelect from '@/components/SfxonEditorFormEntitySelect'
import SfxonMainNavigation from '@/components/SfxonMainNavigation'

const comment = ref('')
const customFieldId = computed(() => {
    const param = new URLSearchParams(window.location.search).get('customFieldId')
    return param ? parseInt(param, 10) : undefined
})
const customFieldLoading = ref(false)
const editable = ref(true)
const { fieldErrors, generalError, handleApiError, clearErrors, clearFieldError } = useApiErrors()
const isEditMode = computed(() => !!customFieldId.value)
const isSaving = ref(false)
const name = ref('')
const position = ref('')
const props = defineProps({
    customFieldGroupId: {
        type: Number,
        required: true,
    },
})
const savedSuccessfully = ref(false)
const selectedType = ref<{ id: string; label: string } | null>(null)
const technicalName = ref('')
const types = [{
    id: 'text',
    label: 'Text'
}]
const validation = reactive<Record<string, any>>({})

function addItem() {
    window.location.href = generateUrl('/apps/sfxonitam/custom-field/detail?customFieldGroupId=' + props.customFieldGroupId)
}

async function loadCustomField(id: number): Promise<void> {
    customFieldLoading.value = true

    try {
        const d = await fetchCustomField(id)
        name.value = d.name ?? ''
        comment.value = d.comment ? d.comment : ''
    } catch (e: any) {
        generalError.value = t('sfxonitam', 'Could not load custom field.')
        console.error('Could not load custom field:', e)
    } finally {
        customFieldLoading.value = false
    }
}

async function submitForm() {
    clearErrors()
    savedSuccessfully.value = false;
    isSaving.value = true

    const payload = {
        customFieldGroupId: props.customFieldGroupId,
        technicalName: technicalName.value,
        name: name.value,
        type: selectedType.value?.id,
        position: position.value,
        // options
        editable: editable.value,
        validation: validation,
        comment: comment.value,
    }

    try {
        const data = await createCustomField(payload)

        // On error the backend returns HTTP Status 200.
        if (data?.status === 'error') {
            handleApiError(data, t('sfxonitam', 'Please check your input.'))
            return
        }

        savedSuccessfully.value = true;
    } catch (error: any) {
        // HTTP error (4xx/5xx) – Backend still might return json with more information.
        const data = error?.response?.data

        if (data?.status === 'error') {
            handleApiError(data, t('sfxonitam', 'Please correct the marked fields.'))
        } else {
            generalError.value = t('sfxonitam', 'Could not save the changes.')
        }
    }
}

watch(() => selectedType.value?.id, (newType) => {
    if (newType === 'text' && !validation.text) {
        validation.text = {
            enabled: false,
            minLength: '',
            maxLength: '',
        }
    }
})

onMounted(async () => {
    if (customFieldId.value) {
        await loadCustomField(customFieldId.value)
    }
})
</script>

<template>
    <NcContent app-name="sfxonitamcustomfieldeditor">
        <NcAppNavigation>
            <NcAppNavigationList>
                <NcAppNavigationNew
                :text="t('sfxonitam', 'Create Custom Field')"
                @click="addItem"
                >
                    <template #icon>
                        <NcIconSvgWrapper :path="mdiPlus" :size="20" />
                    </template>
                </NcAppNavigationNew>
            </NcAppNavigationList>
            <SfxonMainNavigation :currentPage="'customFields'" />
        </NcAppNavigation>

        <NcAppContent :class="$style.content">
            <div :class="$style.form">
                <h2>
                    {{ isEditMode
                        ? t('sfxonitam', 'Custom Field Detail Information')
                        : t('sfxonitam', 'Create Custom Field') }}
                </h2>

                <NcNoteCard
                    v-if="generalError"
                    type="error"
                >
                    {{ generalError }}
                </NcNoteCard>
                <NcNoteCard
                    v-if="savedSuccessfully"
                    type="success"
                >
                    {{ t('sfxonitam', 'Changes have been saved') }}
                </NcNoteCard>

                <div :class="$style.field">
                    <NcTextField
                        id="name"
                        v-model="name"
                        :label="t('sfxonitam', 'Name')"
                        :placeholder="''"
                        :class="fieldErrors.name ? $style.fieldError : ''"
                        @input="clearFieldError('name')"
                    />
                    <span v-if="fieldErrors.name" :class="$style.errorText">
                        {{ fieldErrors.name }}
                    </span>
                </div>

                <div :class="$style.field">
                    <NcTextField
                        id="technicalName"
                        v-model="technicalName"
                        :label="t('sfxonitam', 'Technical Name')"
                        :placeholder="''"
                        :class="fieldErrors.technicalName ? $style.fieldError : ''"
                        @input="clearFieldError('technicalName')"
                    />
                    <span v-if="fieldErrors.technicalName" :class="$style.errorText">
                        {{ fieldErrors.technicalName }}
                    </span>
                </div>

                <SfxonEditorFormEntitySelect
                    field="type"
                    :fieldError="fieldErrors.type"
                    id="type-select"
                    @input="clearFieldError('type')"
                    :label="t('sfxonitam', 'Type') + ':'"
                    :options="types"
                    trackBy="id"
                    v-model="selectedType"
                />

                <div :class="$style.field">
                    <NcTextField
                        id="position"
                        v-model="position"
                        :label="t('sfxonitam', 'Position')"
                        :placeholder="''"
                        :class="fieldErrors.position ? $style.fieldError : ''"
                        @input="clearFieldError('position')"
                    />
                    <span v-if="fieldErrors.position" :class="$style.errorText">
                        {{ fieldErrors.position }}
                    </span>
                </div>

                <div :class="$style.field">
                    <NcCheckboxRadioSwitch
                        id="editable"
                        v-model="editable"
                        :placeholder="''"
                        :class="fieldErrors.editable ? $style.fieldError : ''"
                        @input="clearFieldError('editable')"
                        type="switch"
                    >
                        {{ t('sfxonitam', 'Editable') }}
                    </NcCheckboxRadioSwitch>
                    <span v-if="fieldErrors.editable" :class="$style.errorText">
                        {{ fieldErrors.editable }}
                    </span>
                </div>

                <!-- Add validation options here -->
                <template v-if="selectedType !== null">
                    <template v-if="selectedType.id === 'text' && validation.text">
                        <NcCheckboxRadioSwitch v-model="validation.text.enabled">
                            {{ t('sfxonitam', 'Validate') }}
                        </NcCheckboxRadioSwitch>

                        <template v-if="validation.text.enabled">
                            <NcTextField v-model="validation.text.minLength" :label="t('sfxonitam', 'Min length')" />
                            <NcTextField v-model="validation.text.maxLength" :label="t('sfxonitam', 'Max length')" />
                        </template>
                    </template>
                </template>

                <div :class="$style.field">
                    <NcTextArea
                        id="comment"
                        v-model="comment"
                        :label="t('sfxonitam', 'Comment')"
                        :class="fieldErrors.comment ? $style.fieldError : ''"
                        @input="clearFieldError('comment')"
                    />
                    <span v-if="fieldErrors.comment" :class="$style.errorText">
                        {{ fieldErrors.comment }}
                    </span>
                </div>


                <div :class="$style.actions">
                    <NcButton variant="primary" @click="submitForm">
                        {{ t('sfxonitam', 'Save') }}
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
