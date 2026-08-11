<script setup lang="ts">
import { reactive, ref, computed, onMounted, watch } from 'vue'
import { mdiPlus, mdiDelete, mdiArrowUp, mdiArrowDown, mdiDrag } from '@mdi/js'
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
    },
    foreignKeyTargets: {
        type: Array,
        default: () => ([])
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
    { id: 'longtext', label: 'Long text' },
    { id: 'date', label: 'Date' },
    { id: 'datetime', label: 'Date & Time' },
    { id: 'foreign_key', label: 'Foreign Key' }
]
const validation = reactive<Record<string, any>>({})

// Foreign key specific state
const selectedForeignKeyTarget = ref<{ id: string; label: string } | null>(null)
const composingType = ref<'field' | 'text' | null>(null)
const composingFieldId = ref('')
const composingTextValue = ref('')
const draggedIndex = ref<number | null>(null)
const dragOverIndex = ref<number | null>(null)

const availableLabelFields = computed(() => {
    const target = props.foreignKeyTargets.find((t: any) => t.id === options.foreignKey?.targetEntity)
    return target?.labelFields ?? []
})

const labelPreview = computed(() => {
    if (!options.foreignKey?.labelComposition?.length) {
        return t('sfxonitam', 'No elements added yet')
    }
    return options.foreignKey.labelComposition.map((item: any) => {
        if (item.type === 'field') {
            return `{${labelForFieldId(item.id)}}`
        }
        return item.value || '␣'
    }).join('')
})

type TextSegment = { type: 'text' | 'space'; value: string }

function startComposing(type: 'field' | 'text') {
    composingType.value = type
    composingFieldId.value = ''
    composingTextValue.value = ''
}

function confirmComposing() {
    if (composingType.value === 'field' && composingFieldId.value) {
        options.foreignKey.labelComposition.push({
            type: 'field',
            id: composingFieldId.value,
        })
    }

    if (composingType.value === 'text') {
        options.foreignKey.labelComposition.push({
            type: 'text',
            value: composingTextValue.value,
        })
    }

    composingType.value = null
    clearFieldError('optionsLabelComposition')
}

function cancelComposing() {
    composingType.value = null
}

function removeCompositionItem(index: number) {
    options.foreignKey.labelComposition.splice(index, 1)
}

function moveItemUp(index: number) {
    if (index <= 0) return
    const items = options.foreignKey.labelComposition
    const temp = items[index]
    items[index] = items[index - 1]
    items[index - 1] = temp
}

function moveItemDown(index: number) {
    const items = options.foreignKey.labelComposition
    if (index >= items.length - 1) return
    const temp = items[index]
    items[index] = items[index + 1]
    items[index + 1] = temp
}

// Drag and drop handlers
function onDragStart(index: number) {
    draggedIndex.value = index
}

function onDragOver(index: number, event: DragEvent) {
    event.preventDefault()
    dragOverIndex.value = index
}

function onDragLeave() {
    dragOverIndex.value = null
}

function onDrop(targetIndex: number) {
    if (draggedIndex.value === null || draggedIndex.value === targetIndex) {
        draggedIndex.value = null
        dragOverIndex.value = null
        return
    }
    const items = options.foreignKey.labelComposition
    const item = items.splice(draggedIndex.value, 1)[0]
    items.splice(targetIndex, 0, item)
    draggedIndex.value = null
    dragOverIndex.value = null
}

function onDragEnd() {
    draggedIndex.value = null
    dragOverIndex.value = null
}

function labelForFieldId(fieldId: string) {
    return availableLabelFields.value.find((f: any) => f.id === fieldId)?.label ?? fieldId
}

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

        // Selected type has to be set after options and validation to have a correct load order.
        // Otherwise it will flush the options for foreign key labels for example.
        selectedType.value = d.type
            ? types.find(t => t.id === d.type) ?? null
            : null

        if (selectedType.value?.id === 'foreign_key' && options.foreignKey?.targetEntity) {
            selectedForeignKeyTarget.value = props.foreignKeyTargets.find(
                (t: any) => t.id === options.foreignKey.targetEntity
            ) ?? null
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
        }
     }

     // date
     if (newType === 'date' && !validation.date) {
        validation.date = {
            enabled: false,
            required: false,
        }
     }

     // datetime
     if (newType === 'datetime' && !validation.datetime) {
        validation.datetime = {
            enabled: false,
            required: false,
        }
     }

     // foreign_key
     if (newType === 'foreign_key' && !options.foreignKey) {
         options.foreignKey = {
             targetEntity: '',
             labelComposition: [],
         }
     }

     if (newType === 'foreign_key' && !validation.foreignKey) {
         validation.foreignKey = {
             enabled: false,
             required: false,
         }
     }
})

// Reset label composition whenever the target entity changes,
// since previously selected fields may not exist on the new entity.
watch(() => options.foreignKey?.targetEntity, (newTarget, oldTarget) => {
    if (options.foreignKey && oldTarget !== undefined && newTarget !== oldTarget) {
        options.foreignKey.labelComposition = []
    }
})

// Keep options.foreignKey.targetEntity in sync with the entity-select widget,
// which operates on the {id, label} object rather than the raw string id.
watch(selectedForeignKeyTarget, (newVal) => {
    if (options.foreignKey) {
        options.foreignKey.targetEntity = newVal?.id ?? ''
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

                        <template v-if="selectedType.id === 'foreign_key'">
                            <div :class="$style.field">
                                <SfxonEditorFormEntitySelect
                                    :disabled="isEditMode"
                                    field="targetEntity"
                                    :fieldError="fieldErrors.optionsTargetEntity"
                                    id="foreign-key-target-select"
                                    @input="clearFieldError('optionsTargetEntity')"
                                    :label="t('sfxonitam', 'Target Entity') + ':'"
                                    :options="foreignKeyTargets"
                                    trackBy="id"
                                    v-model="selectedForeignKeyTarget"
                                    :readonly="isEditMode"
                                />
                            </div>

                            <div :class="$style.field" v-if="options.foreignKey.targetEntity">
                                <!-- Label Composition Section -->
                                <div :class="$style.compositionSection">
                                    <div :class="$style.compositionHeader">
                                        <label :class="$style.label">{{ t('sfxonitam', 'Label composition') }}</label>
                                    </div>

                                    <!-- Live Preview -->
                                    <div v-if="options.foreignKey.labelComposition?.length" :class="$style.previewBox">
                                        <span :class="$style.previewLabel">{{ t('sfxonitam', 'Preview') }}:</span>
                                        <span :class="$style.previewValue">{{ labelPreview }}</span>
                                    </div>

                                    <!-- Composition List -->
                                    <div :class="$style.compositionList">
                                        <div
                                            v-for="(item, index) in options.foreignKey.labelComposition"
                                            :key="index"
                                            :class="[
                                                $style.compositionItem,
                                                item.type === 'field' ? $style.fieldItem : $style.textItem,
                                                dragOverIndex === index ? $style.dragOver : '',
                                                draggedIndex === index ? $style.dragging : ''
                                            ]"
                                            draggable="true"
                                            @dragstart="onDragStart(index)"
                                            @dragover="onDragOver(index, $event)"
                                            @dragleave="onDragLeave"
                                            @drop="onDrop(index)"
                                            @dragend="onDragEnd"
                                        >
                                            <!-- Drag Handle -->
                                            <div :class="$style.dragHandle" title="Drag to reorder">
                                                <NcIconSvgWrapper :path="mdiDrag" :size="24" />
                                            </div>

                                            <span :class="$style.itemContent">
                                                <template v-if="item.type === 'field'">
                                                    <span :class="$style.fieldTag">{{ labelForFieldId(item.id) }}</span>
                                                </template>
                                                <template v-else>
                                                    <span :class="$style.textValue">"{{ item.value }}"</span>
                                                </template>
                                            </span>

                                            <!-- Sort Controls -->
                                            <div :class="$style.sortControls">
                                                <NcButton
                                                    variant="tertiary"
                                                    :disabled="index === 0"
                                                    :aria-label="t('sfxonitam', 'Move up')"
                                                    @click="moveItemUp(index)"
                                                    :class="$style.sortBtn"
                                                >
                                                    <template #icon>
                                                        <NcIconSvgWrapper :path="mdiArrowUp" :size="14" />
                                                    </template>
                                                </NcButton>
                                                <NcButton
                                                    variant="tertiary"
                                                    :disabled="index === options.foreignKey.labelComposition.length - 1"
                                                    :aria-label="t('sfxonitam', 'Move down')"
                                                    @click="moveItemDown(index)"
                                                    :class="$style.sortBtn"
                                                >
                                                    <template #icon>
                                                        <NcIconSvgWrapper :path="mdiArrowDown" :size="14" />
                                                    </template>
                                                </NcButton>
                                            </div>

                                            <!-- Delete Button -->
                                            <NcButton
                                                variant="tertiary"
                                                :aria-label="t('sfxonitam', 'Remove element')"
                                                @click="removeCompositionItem(index)"
                                                :class="$style.removeBtn"
                                            >
                                                <template #icon>
                                                    <NcIconSvgWrapper :path="mdiDelete" :size="14" />
                                                </template>
                                            </NcButton>
                                        </div>

                                        <!-- Empty State -->
                                        <div v-if="!options.foreignKey.labelComposition?.length" :class="$style.emptyState">
                                            {{ t('sfxonitam', 'No elements added yet. Add fields or text to compose the label.') }}
                                        </div>
                                    </div>

                                    <span v-if="fieldErrors.optionsLabelComposition" :class="$style.errorText">
                                        {{ fieldErrors.optionsLabelComposition }}
                                    </span>

                                    <!-- Add Controls -->
                                    <div :class="$style.addControls">
                                        <template v-if="composingType === null">
                                            <NcButton variant="secondary" @click="startComposing('field')">
                                                <template #icon>
                                                    <NcIconSvgWrapper :path="mdiPlus" :size="16" />
                                                </template>
                                                {{ t('sfxonitam', 'Add Field') }}
                                            </NcButton>
                                            <NcButton variant="secondary" @click="startComposing('text')">
                                                <template #icon>
                                                    <NcIconSvgWrapper :path="mdiPlus" :size="16" />
                                                </template>
                                                {{ t('sfxonitam', 'Add Text') }}
                                            </NcButton>
                                        </template>

                                        <template v-else-if="composingType === 'field'">
                                            <div :class="$style.composingBox">
                                                <select v-model="composingFieldId" :class="$style.composingSelect">
                                                    <option value="" disabled>{{ t('sfxonitam', 'Select a field...') }}</option>
                                                    <option
                                                        v-for="field in availableLabelFields"
                                                        :key="field.id"
                                                        :value="field.id"
                                                    >
                                                        {{ field.label }}
                                                    </option>
                                                </select>
                                                <NcButton variant="primary" :disabled="!composingFieldId" @click="confirmComposing">
                                                    {{ t('sfxonitam', 'Add') }}
                                                </NcButton>
                                                <NcButton variant="tertiary" @click="cancelComposing">
                                                    {{ t('sfxonitam', 'Cancel') }}
                                                </NcButton>
                                            </div>
                                        </template>

                                        <template v-else-if="composingType === 'text'">
                                            <div :class="$style.composingBox">
                                                <NcTextField
                                                    v-model="composingTextValue"
                                                    :label="t('sfxonitam', 'Text (e.g. space, parenthesis, dash)')"
                                                    :placeholder="t('sfxonitam', 'Type text here...')"
                                                    :class="$style.composingInput"
                                                />
                                                <NcButton variant="primary" @click="confirmComposing">
                                                    {{ t('sfxonitam', 'Add') }}
                                                </NcButton>
                                                <NcButton variant="tertiary" @click="cancelComposing">
                                                    {{ t('sfxonitam', 'Cancel') }}
                                                </NcButton>
                                            </div>
                                        </template>
                                    </div>
                                </div>
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

                        <template v-if="selectedType.id === 'date' && validation.date">
                            <NcCheckboxRadioSwitch v-model="validation.date.enabled">
                                {{ t('sfxonitam', 'Validate') }}
                            </NcCheckboxRadioSwitch>
                            <template v-if="validation.date.enabled">
                                <NcCheckboxRadioSwitch v-model="validation.date.required">
                                    {{ t('sfxonitam', 'Required') }}
                                </NcCheckboxRadioSwitch>
                            </template>
                        </template>

                        <template v-if="selectedType.id === 'datetime' && validation.datetime">
                            <NcCheckboxRadioSwitch v-model="validation.datetime.enabled">
                                {{ t('sfxonitam', 'Validate') }}
                            </NcCheckboxRadioSwitch>
                            <template v-if="validation.datetime.enabled">
                                <NcCheckboxRadioSwitch v-model="validation.datetime.required">
                                    {{ t('sfxonitam', 'Required') }}
                                </NcCheckboxRadioSwitch>
                            </template>
                        </template>

                        <template v-if="selectedType.id === 'foreign_key' && validation.foreignKey">
                            <NcCheckboxRadioSwitch v-model="validation.foreignKey.enabled">
                                {{ t('sfxonitam', 'Validate') }}
                            </NcCheckboxRadioSwitch>
                            <template v-if="validation.foreignKey.enabled">
                                <NcCheckboxRadioSwitch v-model="validation.foreignKey.required">
                                    {{ t('sfxonitam', 'Required') }}
                                </NcCheckboxRadioSwitch>
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
    display: flex;
    flex-direction: column;
    gap: 16px;
    max-width: 480px;
    width: 100%;
}

.field {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.label {
    color: var(--color-text-maxcontrast);
    font-weight: bold;
    font-size: 0.875rem;
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

.compositionSection {
    background: var(--color-main-background);
    border: 1px solid var(--color-border);
    border-radius: var(--border-radius-large, 8px);
    padding: 12px;
}

.compositionHeader {
    align-items: center;
    display: flex;
    justify-content: space-between;
    margin-bottom: 8px;
}

.previewBox {
    align-items: center;
    background: var(--color-background-dark, #f5f5f5);
    border: 1px dashed var(--color-border-maxcontrast);
    border-radius: var(--border-radius, 6px);
    display: flex;
    font-family: 'SF Mono', Monaco, 'Cascadia Code', monospace;
    font-size: 0.9rem;
    gap: 8px;
    padding: 8px 12px;
    margin-bottom: 12px;
}

.previewLabel {
    color: var(--color-text-maxcontrast);
    font-size: 0.75rem;
    font-weight: 600;
    letter-spacing: 0.05em;
    text-transform: uppercase;
}

.previewValue {
    color: var(--color-main-text);
    word-break: break-all;
}

.compositionList {
    display: flex;
    flex-direction: column;
    gap: 6px;
    margin-bottom: 12px;
}

.compositionItem {
    align-items: center;
    border-radius: var(--border-radius, 6px);
    border: 1px solid transparent;
    display: flex;
    gap: 8px;
    padding: 6px 10px;
    transition: background 0.15s ease;
}

.compositionItem:hover {
    background: var(--color-background-hover);
}

.fieldItem {
    background: var(--color-primary-element-light, #e6f0fa);
    border-color: var(--color-primary-element, #006aa3);
}

.textItem {
    background: var(--color-background-dark, #f0f0f0);
    border-color: var(--color-border);
}

.itemIcon {
    flex-shrink: 0;
    font-size: 1rem;
    line-height: 1;
    text-align: center;
    width: 20px;
}

.itemContent {
    align-items: center;
    display: flex;
    flex: 1;
    min-width: 0;
}

.fieldTag {
    background: var(--color-primary-element, #006aa3);
    border-radius: var(--border-radius-pill, 16px);
    color: var(--color-primary-element-text, #fff);
    font-size: 0.85rem;
    font-weight: 500;
    padding: 2px 10px;
}

.textValue {
    background: var(--color-main-background);
    border-radius: 4px;
    border: 1px solid var(--color-border);
    color: var(--color-text-maxcontrast);
    font-family: 'SF Mono', Monaco, 'Cascadia Code', monospace;
    font-size: 0.9rem;
    padding: 2px 8px;
}

.sortControls {
    display: flex;
    flex-direction: row;
    gap: 1px;
    opacity: 0;
    transition: opacity 0.15s ease;
}

.compositionItem:hover .sortControls {
    opacity: 1;
}

.sortBtn {
    height: 22px !important;
    min-height: 22px !important;
    padding: 2px !important;
}

.sortBtn:disabled {
    opacity: 0.2;
}

.removeBtn {
    flex-shrink: 0;
    opacity: 0;
    transition: opacity 0.15s ease;
}

.compositionItem:hover .removeBtn {
    opacity: 1;
}

.emptyState {
    border: 2px dashed var(--color-border);
    border-radius: var(--border-radius, 6px);
    color: var(--color-text-maxcontrast);
    font-style: italic;
    font-size: 0.9rem;
    padding: 16px;
    text-align: center;
}

.addControls {
    border-top: 1px solid var(--color-border);
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    padding-top: 8px;
}

.composingBox {
    align-items: flex-end;
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    width: 100%;
}

.composingSelect {
    background: var(--color-main-background);
    border: 1px solid var(--color-border);
    border-radius: var(--border-radius, 6px);
    color: var(--color-main-text);
    flex: 1;
    font-size: 0.9rem;
    min-width: 200px;
    padding: 8px 12px;
}

.composingInput {
    flex: 1;
    min-width: 200px;
}

.dragHandle {
    align-items: center;
    cursor: grab;
    color: var(--color-text-maxcontrast);
    display: flex;
    justify-content: center;
    min-width: 32px;
    opacity: 0.4;
    padding: 2px 6px;
    transition: opacity 0.15s ease;
}
</style>
