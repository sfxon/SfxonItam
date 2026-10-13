<script setup lang="ts">
import * as DeviceService from '@/services/DeviceService'
import * as DeviceStatusService from '@/services/DeviceStatusService'
import * as DeviceTypeService from '@/services/DeviceTypeService'
import * as ItamUserService from '@/services/ItamUserService'
import * as LocationService from '@/services/LocationService'
import * as ManufacturerService from '@/services/ManufacturerService'
import * as MerchantService from '@/services/MerchantService'
import * as PositionService from '@/services/PositionService'
import * as QuantityUnitService from '@/services/QuantityUnitService'
import { computed, onMounted, reactive, ref } from 'vue'
import { generateUrl } from '@nextcloud/router'
import { loadState } from '@nextcloud/initial-state'
import { mdiPlus } from '@mdi/js'
import NcAppContent from '@nextcloud/vue/components/NcAppContent'
import NcAppNavigation from '@nextcloud/vue/components/NcAppNavigation'
import NcAppNavigationList from '@nextcloud/vue/components/NcAppNavigationList'
import NcAppNavigationNew from '@nextcloud/vue/components/NcAppNavigationNew'
import NcContent from '@nextcloud/vue/components/NcContent'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import NcNoteCard from '@nextcloud/vue/components/NcNoteCard'
import { fetchPosition, createPosition, updatePosition } from '@/services/PositionService'
import SfxonCustomFields from '@/components/SfxonCustomFields'
import SfxonEntityDialog from '@/components/SfxonEntityDialog'
import SfxonItamHeaderBc, { type BreadcrumbItem } from '@/components/SfxonItamHeaderBc'
import SfxonSaveBadge from '@/components/SfxonSaveBadge'
import SfxonEditorFormEntitySelect from '@/components/SfxonEditorFormEntitySelect'
import SfxonEditorFormInput from '@/components/SfxonEditorFormInput'
import SfxonEditorFormTextareaLabeled from '@/components/SfxonEditorFormTextareaLabeled'
import SfxonEditorStyles from '@/components/SfxonEditor/SfxonEditor.module.css'
import SfxonMainNavigation from '@/components/SfxonMainNavigation'
import { translate as t } from '@nextcloud/l10n'
import { useApiErrors } from '@/composables/useApiErrors'
import { useRelatedEntities } from '@/composables/useRelatedEntities'
import type { RelationMeta } from '@/composables/useRelatedEntities'
import { useSaveBadge } from '@/composables/useSaveBadge'

const services = {
    DeviceService,
    DeviceStatusService,
    DeviceTypeService,
    ItamUserService,
    ManufacturerService,
    MerchantService,
    LocationService,
    PositionService,
    QuantityUnitService
}
const addEntityEntryDialogEntityName = ref('')
const name = ref('')
const props = defineProps({
    entityDefinitions: {
        type: Object,
        required: true,
    },
    customFields: {
        type: Array,
        default: () => [],
    },
})
const selectedLocation = ref<{ id: string; label: string } | null>(null)
const comment = ref('')
const positionLoading = ref(false)
const isSaving = ref(false)
const { fieldErrors, generalError, handleApiError, clearErrors, clearFieldError } = useApiErrors()
const { visible: saveVisible, isReflash: saveIsReflash, flashKey: saveFlashKey, trigger: triggerSaveSuccess } = useSaveBadge()
const positionId = computed(() => {
    const param = new URLSearchParams(window.location.search).get('positionId')
    return param ? parseInt(param, 10) : undefined
})
const isEditMode = computed(() => !!positionId.value)
const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    {
        label: t('sfxonitam', 'Position'),
        link: generateUrl('/apps/sfxonitam/position/'),
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
const entityMeta = loadState<Record<string, RelationMeta>>('sfxonitam', 'entityMeta', {})
const {
    data: relatedEntityData,
    applyRelations,
    searchFns: relatedEntitySearchFns,
} = useRelatedEntities(entityMeta)
const customFieldsRef = ref<InstanceType<typeof SfxonCustomFields> | null>(null)
const customFieldValues = ref<Record<string, unknown>>({})

function onCustomFieldValuesUpdate(newValues: Record<string, unknown>) {
    customFieldValues.value = newValues
}

const customFieldInitialValues = ref<Record<string, unknown>>({})
const entityConfig = {
    location: {
        selectTarget: selectedLocation,
        optionsTarget: computed(() => relatedEntityData.location),
        labelFields: { fields: ['name'] },
    },
}
const relations = reactive(buildRelations(props.entityDefinitions, entityConfig))

function addItem() {
    window.location.href = generateUrl('/apps/sfxonitam/position/detail')
}

function buildRelations(entityDefinitions, dropdownBindings = {}) {
    let retval = {};

    for(const [entityName, entityDefinition] of Object.entries(entityDefinitions)) {
        const fields = []

        for(const fieldDefinition of entityDefinition) {
            if(!fieldDefinition.requiredOnCreate) {
                continue;
            }

            const field = {
                fieldName: fieldDefinition.name,
                label: t('sfxonitam', fieldDefinition.label) + ':',
                sfxonType: mapFrontendBackendFieldType(fieldDefinition.type),
                type: mapFrontendBackendFieldSubtype(fieldDefinition.type),
                value: ''
            }

            fields.push(field);
        }

        const capitalizedEntityName = entityName.charAt(0).toUpperCase() + entityName.slice(1)
        const entry = {
            heading: t('sfxonitam', 'Add ' + entityName),
            fields: fields,
            saveFunction: services[`${capitalizedEntityName}Service`][`create${capitalizedEntityName}`]
        }

        retval[entityName] = {
            addRecordModal: entry
        }

        const binding = dropdownBindings[entityName] ?? {}

        retval[entityName] = {
            addRecordModal: entry,
            selectTarget: binding.selectTarget ?? null,
            optionsTarget: binding.optionsTarget ?? null,
            labelFields: binding.labelFields ?? { fields: ['name'] },
        }
    }

    return retval;
}

async function loadPosition(id: number): Promise<void> {
    try {
        positionLoading.value = true
        const data = await fetchPosition(id)

        applyRelations(data.relations)

        const d = data.mainData
        name.value = d.name ?? ''
        selectedLocation.value = relatedEntityData.location.find(s => s.id == d.locationId) ?? null
        comment.value = d.comment ? d.comment : ''
        customFieldInitialValues.value = d.customFields ?? {}
    } catch (e: any) {
        generalError.value = t('sfxonitam', 'Position could not be loaded.')
        console.error('Error while loading Position:', e)
    } finally {
        positionLoading.value = false
    }
}

function mapFrontendBackendFieldType(backendType) {
    switch(backendType) {
        case 'VARCHAR':
            return 'SfxonEditorFormInput';
    }

    throw new Error('Unknown field type ' + backendType);
}

function mapFrontendBackendFieldSubtype(backendType) {
    switch(backendType) {
        case 'VARCHAR':
            return 'text';
    }

    throw new Error('Unknown field type ' + backendType);
}

function onEntitySaved(payload: { entityName: string; newOption: { id: string; label: string } }) {
    const { entityName, newOption } = payload
    const relation = relations[entityName]
    const config = entityConfig[entityName]

     if (relation?.optionsTarget) {
        if (!relation.optionsTarget.some((o: any) => o.id === newOption.id)) {
            relation.optionsTarget.push(newOption)
        }
    }

    if (config?.selectTarget) {
        config.selectTarget.value = newOption
    }

    triggerSaveSuccess()
}

async function openAddEntityDialog(payload: any) {
    addEntityEntryDialogEntityName.value = payload.entity
}

async function save() {
    const submitSuccess = await submitForm()

    if(submitSuccess !== false && typeof positionId.value === 'undefined') {
        window.location.href = generateUrl('apps/sfxonitam/position/detail?positionId=' + submitSuccess)
    }
}

async function saveAndBack() {
    const submitSuccess = await submitForm()

    if (submitSuccess !== false) {
        window.location.href = generateUrl('/apps/sfxonitam/position')
    }
}

async function saveAndNew() {
    const submitSuccess = await submitForm()
    
    if (submitSuccess !== false) {
        window.location.href = generateUrl('/apps/sfxonitam/position/detail')
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
        locationId: selectedLocation.value?.id ?? null,
        comment: comment.value,
        customFields: customFieldValues.value,
    }

    try {
        const data = isEditMode.value
            ? await updatePosition(positionId.value!, payload)
            : await createPosition(payload)

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
    if (positionId.value) {
        await loadPosition(positionId.value)
    }
})
</script>

<template>
    <NcContent app-name="sfxonitampositioneditor">
        <NcAppNavigation>
            <NcAppNavigationList>
                <NcAppNavigationNew
                :text="t('sfxonitam', 'Add position')"
                @click="addItem"
                >
                    <template #icon>
                        <NcIconSvgWrapper :path="mdiPlus" :size="20" />
                    </template>
                </NcAppNavigationNew>
            </NcAppNavigationList>
            <SfxonMainNavigation :currentPage="'positions'" />
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
                                :placeholder="t('sfxonitam', 'e.g. Laptop')"
                                @input="clearFieldError('name')"
                                :fieldError="fieldErrors.name"
                            />

                            <SfxonEditorFormEntitySelect
                                :addRecordFn="() => openAddEntityDialog({ entity: 'location' })"
                                field="locationId"
                                :fieldError="fieldErrors.locationId"
                                id="location-select"
                                @input="clearFieldError('locationId')"
                                :label="t('sfxonitam', 'Location') + ':'"
                                :options="relatedEntityData.location"
                                :searchFn="relatedEntitySearchFns.location"
                                trackBy="id"
                                v-model="selectedLocation"
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

    <SfxonEntityDialog
        :relations="relations"
        :entity-name="addEntityEntryDialogEntityName"
        @close="addEntityEntryDialogEntityName = ''"
        @saved="onEntitySaved"
    />
</template>

<style module scoped>
.formSection1 {
    max-width: 600px;
}

.commentField :deep(textarea) {
    min-height: 160px!important;
}
</style>