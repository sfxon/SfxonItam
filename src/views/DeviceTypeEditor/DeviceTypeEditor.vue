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
import { mdiPlus } from '@mdi/js'
import NcAppContent from '@nextcloud/vue/components/NcAppContent'
import NcAppNavigation from '@nextcloud/vue/components/NcAppNavigation'
import NcAppNavigationList from '@nextcloud/vue/components/NcAppNavigationList'
import NcAppNavigationNew from '@nextcloud/vue/components/NcAppNavigationNew'
import NcContent from '@nextcloud/vue/components/NcContent'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import NcNoteCard from '@nextcloud/vue/components/NcNoteCard'
import { fetchDeviceType, createDeviceType, updateDeviceType } from '@/services/DeviceTypeService'
import SfxonCustomFields from '@/components/SfxonCustomFields'
import SfxonEntityDialog from '@/components/SfxonEntityDialog'
import SfxonItamHeaderBc, { type BreadcrumbItem } from '@/components/SfxonItamHeaderBc'
import SfxonSaveBadge from '@/components/SfxonSaveBadge'
import SfxonEditorFormEntitySelect from '@/components/SfxonEditorFormEntitySelect'
import SfxonEditorFormInput from '@/components/SfxonEditorFormInput'
import SfxonEditorFormTextareaLabeled from '@/components/SfxonEditorFormTextareaLabeled'
import SfxonEditorStyles from '@/components/SfxonEditor/SfxonEditor.module.css'
import SfxonMainNavigation from '@/components/SfxonMainNavigation'
import { fetchAllManufacturers } from '@/services/ManufacturerService'
import { translate as t } from '@nextcloud/l10n'
import { useApiErrors } from '@/composables/useApiErrors'
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
const selectedManufacturer = ref<{ id: string; label: string } | null>(null)
const comment = ref('')
const manufacturersLoading = ref(false)
const deviceTypeLoading = ref(false)
const isSaving = ref(false)
const { fieldErrors, generalError, handleApiError, clearErrors, clearFieldError } = useApiErrors()
const { visible: saveVisible, isReflash: saveIsReflash, flashKey: saveFlashKey, trigger: triggerSaveSuccess } = useSaveBadge()
const deviceTypeId = computed(() => {
    const param = new URLSearchParams(window.location.search).get('deviceTypeId')
    return param ? parseInt(param, 10) : undefined
})
const isEditMode = computed(() => !!deviceTypeId.value)
const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    {
        label: t('sfxonitam', 'Device Type'),
        link: generateUrl('/apps/sfxonitam/device-type/'),
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
const manufacturers = ref<{ id: string; label: string}[]>([])
const customFieldsRef = ref<InstanceType<typeof SfxonCustomFields> | null>(null)
const customFieldValues = ref<Record<string, unknown>>({})

function onCustomFieldValuesUpdate(newValues: Record<string, unknown>) {
    customFieldValues.value = newValues
}

const customFieldInitialValues = ref<Record<string, unknown>>({})

/**
 * @deprecated
 * 
 * This is in subject to be more streamlined.
 * Since entities will be even more dynamic in the future, especially when we think of custom fields,
 * this is only a first draft, on how this could be archived.
 * Later, the fields "selectTarget, optionsTarget and labelFields" should be dynamically generated in the buildRelations method.
 * The input fields below should then only be rendered in a loop, with the given input fields.
 * For the time between, we use this as a middle solution.
 * 
 */
const entityConfig = {
    manufacturer: {
        selectTarget: selectedManufacturer,
        optionsTarget: manufacturers,
        labelFields: { fields: ['name'] },
    },
}
const relations = reactive(buildRelations(props.entityDefinitions, entityConfig))

function addEntityData(entity, dataObject, identifierField, valueFields = null) {
    if (dataObject == null) {
        return
    }

    let id = dataObject[identifierField];
    let label = '';
    
    if(null !== valueFields) {
        if(valueFields.technique === 'concat') {
            for(let fieldName of valueFields.fields) {
                if(label.length > 0) {
                    label += valueFields.separator;
                }

                label += dataObject[fieldName];
            }
        } else {
            label = dataObject.name
        } 
    } else {
        label = dataObject.name
    }

    entity.value.push({id: id, label: label})
}

function addItem() {
    window.location.href = generateUrl('/apps/sfxonitam/device-type/detail')
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

async function loadDeviceType(id: number): Promise<void> {
    try {
        deviceTypeLoading.value = true
        const data = await fetchDeviceType(id)

        // Helper for checking, if object is set. If there was no relation set and so no relation loaded, this prevents a faulty access on the object.
        const firstOf = (obj: any) => obj ? Object.values(obj)[0] ?? null : null 
        addEntityData(manufacturers, firstOf(data.relations.manufacturer), 'id')

        const d = data.mainData
        name.value = d.name ?? ''
        selectedManufacturer.value = manufacturers.value.find(s => s.id == d.manufacturerId) ?? null
        comment.value = d.comment ? d.comment : ''
        customFieldInitialValues.value = d.customFields ?? {}
    } catch (e: any) {
        generalError.value = t('sfxonitam', 'Device Type could not be loaded.')
        console.error('Error while loading DeviceType:', e)
    } finally {
        deviceTypeLoading.value = false
    }
}

async function loadManufacturers() {
    try {
        manufacturersLoading.value = true
        const data = await fetchAllManufacturers({})

        manufacturers.value = Object.values(data.manufacturers).map((manufacturer: any) => ({
            id: manufacturer.id,
            label: manufacturer.name
        }))
    } catch(e) {
        console.error('Error on loading manufacturers:', e)
    } finally {
        manufacturersLoading.value = false
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

    if(submitSuccess !== false && typeof deviceTypeId.value === 'undefined') {
        window.location.href = generateUrl('apps/sfxonitam/device-type/detail?deviceTypeId=' + submitSuccess)
    }
}

async function saveAndBack() {
    const submitSuccess = await submitForm()

    if (submitSuccess !== false) {
        window.location.href = generateUrl('/apps/sfxonitam/device-type')
    }
}

async function saveAndNew() {
    const submitSuccess = await submitForm()
    
    if (submitSuccess !== false) {
        window.location.href = generateUrl('/apps/sfxonitam/device-type/detail')
    }
}

async function searchManufacturers(query: string, signal: AbortSignal): Promise<void> {
    let filters = {
        name: [query]
    };

    const data = await ManufacturerService.findManufacturer({ filters: filters }, signal)

    if (data === null || data.mainData === null) {
        return;
    }

    manufacturers.value = Object.values(data.mainData).map((merchant: any) => ({
        id: merchant.id,
        label: merchant.name
    }))
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
        manufacturerId: selectedManufacturer.value?.id ?? null,
        comment: comment.value,
        customFields: customFieldValues.value,
    }

    try {
        const data = isEditMode.value
            ? await updateDeviceType(deviceTypeId.value!, payload)
            : await createDeviceType(payload)

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
    if (deviceTypeId.value) {
        await loadDeviceType(deviceTypeId.value)
    }
})
</script>

<template>
    <NcContent app-name="sfxonitamdevicetypeeditor">
        <NcAppNavigation>
            <NcAppNavigationList>
                <NcAppNavigationNew
                :text="t('sfxonitam', 'Add device type')"
                @click="addItem"
                >
                    <template #icon>
                        <NcIconSvgWrapper :path="mdiPlus" :size="20" />
                    </template>
                </NcAppNavigationNew>
            </NcAppNavigationList>
            <SfxonMainNavigation :currentPage="'deviceTypes'" />
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
                                :addRecordFn="() => openAddEntityDialog({ entity: 'manufacturer' })"
                                field="manufacturerId"
                                :fieldError="fieldErrors.manufacturerId"
                                id="manufacturer-select"
                                @input="clearFieldError('manufacturerId')"
                                :label="t('sfxonitam', 'Manufacturer') + ':'"
                                :options="manufacturers"
                                :searchFn="searchManufacturers"
                                trackBy="id"
                                v-model="selectedManufacturer"
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