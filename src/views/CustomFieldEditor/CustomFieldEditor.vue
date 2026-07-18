<script setup lang="ts">
import { reactive, ref, computed, onMounted, watch } from 'vue'
import { mdiPlus } from '@mdi/js'
import NcAppContent from '@nextcloud/vue/components/NcAppContent'
import NcAppNavigation from '@nextcloud/vue/components/NcAppNavigation'
import NcAppNavigationList from '@nextcloud/vue/components/NcAppNavigationList'
import NcAppNavigationNew from '@nextcloud/vue/components/NcAppNavigationNew'
import NcBreadcrumbs from '@nextcloud/vue/components/NcBreadcrumbs'
import NcBreadcrumb from '@nextcloud/vue/components/NcBreadcrumb'
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
import { fetchCustomField, createCustomField, updateCustomField } from '@/services/CustomFieldService'
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
const options = reactive<Record<string, any>>({})
const position = ref('')
const props = defineProps({
    customFieldGroupId: {
        type: Number,
        required: true,
    },
    customFieldGroup: {
        type: Object,
        default: () => ({})
    }
})
const savedSuccessfully = ref(false)
const selectedType = ref<{ id: string; label: string } | null>(null)
const technicalName = ref('')
const types = [
    { id: 'text', label: 'Text' },
    { id: 'integer', label: 'Integer' },
    { id: 'decimal', label: 'Decimal' },
    { id: 'boolean', label: 'Boolean' },
    { id: 'file', label: 'File' },
    { id: 'longtext', label: 'Long text' }
]
const validation = reactive<Record<string, any>>({})

function addItem() {
    window.location.href = generateUrl('/apps/sfxonitam/custom-field/detail?customFieldGroupId=' + props.customFieldGroupId)
}

async function loadCustomField(id: number): Promise<void> {
    customFieldLoading.value = true

    try {
        const d = await fetchCustomField(id)
        comment.value = d.comment ? d.comment : ''
        editable.value = d.editable ?? true
        position.value = d.position
        name.value = d.name ?? ''
        selectedType.value = d.type
            ? types.find(t => t.id === d.type) ?? null
            : null
        technicalName.value = d.technicalName

        if (d.options) {
            const parsedOptions = typeof d.options === 'string'
                ? JSON.parse(d.options)
                : d.options
            Object.assign(options, parsedOptions)
        }
        
        if (d.validation) {
            const parsedValidation = typeof d.validation === 'string'
                ? JSON.parse(d.validation)
                : d.validation
            Object.assign(validation, parsedValidation)
        }
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
    let payload = {}

    if(isEditMode.value) {
         payload = {
            comment: comment.value,
            editable: editable.value,
            name: name.value,
            options: options,
            position: position.value,
            validation: validation,
        }
    } else {
        payload = {
            comment: comment.value,
            customFieldGroupId: props.customFieldGroupId,
            editable: editable.value,
            name: name.value,
            options: options,
            position: position.value,
            technicalName: technicalName.value,
            type: selectedType.value?.id,
            validation: validation,
        }
    }

    try {
        const data = isEditMode.value
            ? await updateCustomField(customFieldId.value!, payload)
            : await createCustomField(payload)

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
    // text
    if (newType === 'text' && !options.text) {
        options.text = {
            maxLength: '',
        }
    }
    
    if (newType === 'text' && !validation.text) {
        validation.text = {
            enabled: false,
            minLength: '',
            required: false,
        }
    }

    // integer
    if (newType === 'integer' && !validation.integer) {
        validation.integer = {
            enabled: false,
            required: false
        }
    }

    // decimal
    if (newType === 'decimal' && !options.decimal) {
        options.decimal = {
            integerDigitsLength: '',
            fractionDigitsLength: '',
        }
    }

    if (newType === 'decimal' && !validation.decimal) {
        validation.decimal = {
            enabled: false,
            required: false
        }
    }

    // boolean
    if (newType === 'boolean' && !validation.boolean) {
        validation.boolean = {
            enabled: false,
            required: false
        }
    }

    // file
    if (newType === 'file' && !validation.file) {
        validation.file = {
            enabled: false,
            required: false
        }
    }

    // longtext
     if (newType === 'longtext' && !validation.longtext) {
         validation.longtext = {
            enabled: false,
            minLength: '',
            required: false,
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

        <NcAppContent>
            <div :class="$style.sfxonItamHeader">
                <NcBreadcrumbs root-icon="">
                    <NcBreadcrumb
                        :disable-drop="true"
                        :force-icon-text="true"
                        :href="generateUrl('/apps/sfxonitam/custom-field-group/')"
                        name="Custom Field Sets"
                        title="Custom Field Sets" />
                    <NcBreadcrumb
                        :disable-drop="true"
                        :force-icon-text="true"
                        :href="generateUrl('/apps/sfxonitam/custom-field/?customFieldGroupId=' + customFieldGroup.id)"
                        :name="'Custom Fields for ' + props.customFieldGroup.name"
                        :title="'Custom Fields for ' + props.customFieldGroup.name" />
                    <NcBreadcrumb
                        :disable-drop="true"
                        :force-icon-text="true"
                        href="#"
                        :name="isEditMode ? 'Edit' : 'Create'"
                        :title="isEditMode ? 'Edit' : 'Create'" />
                </NcBreadcrumbs>
            </div>

            <div :class="$style.content">
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
                            :readonly="isEditMode"
                            :disabled="isEditMode"
                        />
                        <span v-if="fieldErrors.technicalName" :class="$style.errorText">
                            {{ fieldErrors.technicalName }}
                        </span>
                    </div>

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

                    <SfxonEditorFormEntitySelect
                        :disabled="isEditMode"
                        field="type"
                        :fieldError="fieldErrors.type"
                        id="type-select"
                        @input="clearFieldError('type')"
                        :label="t('sfxonitam', 'Type') + ':'"
                        :options="types"
                        trackBy="id"
                        v-model="selectedType"
                        :readonly="isEditMode"
                    />

                    <template v-if="selectedType !== null">
                        <template v-if="selectedType.id === 'text'">
                            <div :class="$style.field">
                                <NcTextField
                                    :disabled="isEditMode"
                                    :class="fieldErrors.optionsMaxLength ? $style.fieldError : ''"
                                    @input="clearFieldError('optionsMaxLength')"
                                    :label="t('sfxonitam', 'Max length')"
                                    :readonly="isEditMode"
                                    v-model="options.text.maxLength"
                                />
                                <span v-if="fieldErrors.optionsMaxLength" :class="$style.errorText">
                                    {{ fieldErrors.optionsMaxLength }}
                                </span>
                            </div>
                        </template>

                        <template v-if="selectedType.id === 'decimal'">
                            <div :class="$style.field">
                                <NcTextField
                                    :disabled="isEditMode"
                                    :class="fieldErrors.optionsIntegerDigitsLength ? $style.fieldError : ''"
                                    @input="clearFieldError('optionsIntegerDigitsLength')"
                                    :label="t('sfxonitam', 'Integer Digits')"
                                    :readonly="isEditMode"
                                    v-model="options.decimal.integerDigitsLength"
                                    
                                />
                                <span v-if="fieldErrors.optionsIntegerDigitsLength" :class="$style.errorText">
                                    {{ fieldErrors.optionsIntegerDigitsLength }}
                                </span>
                            </div>

                            <div :class="$style.field">
                                <NcTextField
                                    :disabled="isEditMode"
                                    :class="fieldErrors.optionsFractionDigitsLength ? $style.fieldError : ''"
                                    @input="clearFieldError('optionsFractionDigitsLength')"
                                    :label="t('sfxonitam', 'Fraction Digits')"
                                    :readonly="isEditMode"
                                    v-model="options.decimal.fractionDigitsLength"
                                />
                                <span v-if="fieldErrors.optionsFractionDigitsLength" :class="$style.errorText">
                                    {{ fieldErrors.optionsFractionDigitsLength }}
                                </span>
                            </div>
                        </template>
                    </template>

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
                                <NcCheckboxRadioSwitch v-model="validation.text.required">
                                    {{ t('sfxonitam', 'Required') }}
                                </NcCheckboxRadioSwitch>
                                <NcTextField
                                    v-model="validation.text.minLength"
                                    :label="t('sfxonitam', 'Min length')"
                                    :class="fieldErrors.validationMinLength ? $style.fieldError : ''"
                                    @input="clearFieldError('validationMinLength')"
                                />
                                <span v-if="fieldErrors.validationMinLength" :class="$style.errorText">
                                    {{ fieldErrors.validationMinLength }}
                                </span>
                            </template>
                        </template>

                        <template v-if="selectedType.id === 'integer' && validation.integer">
                            <NcCheckboxRadioSwitch v-model="validation.integer.enabled">
                                {{ t('sfxonitam', 'Validate') }}
                            </NcCheckboxRadioSwitch>
                            <template v-if="validation.integer.enabled">
                                <NcCheckboxRadioSwitch v-model="validation.integer.required">
                                    {{ t('sfxonitam', 'Required') }}
                                </NcCheckboxRadioSwitch>
                            </template>
                        </template>

                        <template v-if="selectedType.id === 'decimal' && validation.decimal">
                            <NcCheckboxRadioSwitch v-model="validation.decimal.enabled">
                                {{ t('sfxonitam', 'Validate') }}
                            </NcCheckboxRadioSwitch>
                            <template v-if="validation.decimal.enabled">
                                <NcCheckboxRadioSwitch v-model="validation.decimal.required">
                                    {{ t('sfxonitam', 'Required') }}
                                </NcCheckboxRadioSwitch>
                            </template>
                        </template>

                        <template v-if="selectedType.id === 'boolean' && validation.boolean">
                            <NcCheckboxRadioSwitch v-model="validation.boolean.enabled">
                                {{ t('sfxonitam', 'Validate') }}
                            </NcCheckboxRadioSwitch>
                            <template v-if="validation.boolean.enabled">
                                <NcCheckboxRadioSwitch v-model="validation.boolean.required">
                                    {{ t('sfxonitam', 'Required') }}
                                </NcCheckboxRadioSwitch>
                            </template>
                        </template>

                        <template v-if="selectedType.id === 'file' && validation.file">
                            <NcCheckboxRadioSwitch v-model="validation.file.enabled">
                                {{ t('sfxonitam', 'Validate') }}
                            </NcCheckboxRadioSwitch>
                            <template v-if="validation.file.enabled">
                                <NcCheckboxRadioSwitch v-model="validation.file.required">
                                    {{ t('sfxonitam', 'Required') }}
                                </NcCheckboxRadioSwitch>
                            </template>
                        </template>

                        <template v-if="selectedType.id === 'longtext' && validation.longtext">
                            <NcCheckboxRadioSwitch v-model="validation.longtext.enabled">
                                {{ t('sfxonitam', 'Validate') }}
                            </NcCheckboxRadioSwitch>
                            <template v-if="validation.longtext.enabled">
                                <NcCheckboxRadioSwitch v-model="validation.longtext.required">
                                    {{ t('sfxonitam', 'Required') }}
                                </NcCheckboxRadioSwitch>
                                <NcTextField
                                    v-model="validation.longtext.minLength"
                                    :label="t('sfxonitam', 'Min length')"
                                    :class="fieldErrors.validationMinLength ? $style.fieldError : ''"
                                    @input="clearFieldError('validationMinLength')"
                                />
                                <span v-if="fieldErrors.validationMinLength" :class="$style.errorText">
                                    {{ fieldErrors.validationMinLength }}
                                </span>
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
            </div>
        </NcAppContent>
    </NcContent>
</template>

<style module>
.sfxonItamHeader {
    align-items: start;
    display: flex;
    flex: 0 0;
    font-weight: bold;
    gap: var(--default-grid-baseline);
    margin-block: var(--app-navigation-padding, 4px);
    margin-inline: calc(var(--default-clickable-area) + 2*var(--app-navigation-padding, 4px)) var(--app-navigation-padding, 4px);
    max-width: 100%;
    min-height: 32px;
}

.sfxonItamHeader :global(.breadcrumb) {
    align-items: start!important;
}

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
