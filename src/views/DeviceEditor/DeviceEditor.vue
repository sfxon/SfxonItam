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
import NcAppContent from '@nextcloud/vue/components/NcAppContent'
import NcAppNavigation from '@nextcloud/vue/components/NcAppNavigation'
import NcAppNavigationList from '@nextcloud/vue/components/NcAppNavigationList'
import NcAppNavigationNew from '@nextcloud/vue/components/NcAppNavigationNew'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcContent from '@nextcloud/vue/components/NcContent'
import { mdiPlus } from '@mdi/js'
import { translate as t } from '@nextcloud/l10n'
import { generateUrl } from '@nextcloud/router'
import NcNoteCard from '@nextcloud/vue/components/NcNoteCard'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import SfxonBarcode from '@/components/SfxonBarcode'
import SfxonCustomFields from '@/components/SfxonCustomFields'
import SfxonEditorFormDatePicker from '@/components/SfxonEditorFormDatePicker'
import SfxonEditorFormEntitySelect from '@/components/SfxonEditorFormEntitySelect'
import SfxonEditorFormImageSelector from '@/components/SfxonEditorFormImageSelector'
import SfxonEditorFormInput from '@/components/SfxonEditorFormInput'
import SfxonEditorFormTextarea from '@/components/SfxonEditorFormTextarea'
import SfxonEditorStyles from '@/components/SfxonEditor/SfxonEditor.module.css'
import SfxonEntityDialog from '@/components/SfxonEntityDialog'
import SfxonItamHeader from '@/components/SfxonItamHeader'
import SfxonMainNavigation from '@/components/SfxonMainNavigation'
import SfxonQrCodeView from '@/components/SfxonQrCodeView'
import { useApiErrors } from '@/composables/useApiErrors'
import { useSfxonFileUploadField } from '@/composables/useSfxonFileUploadField'
import { parseLocalDateString, toLocalDateString } from '@/services/DateService'

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
const assetNumber = ref('')
const description = ref('')
const invoiceNumber = ref('')
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
const purchaseDate = ref<Date | null>(null)
const quantity = ref('')

const saveFlashKey = ref(0) // Changing this key re-triggers the CSS animation.
const saveVisible = ref(false) // Used to control the visibility of symbols.
const saveIsReflash = ref(false) // Marks the distinction between initial vs. re-trigger animations.
let saveHideTimer: ReturnType<typeof setTimeout> | null = null
const selectedDeviceStatus = ref<{ id: string; label: string } | null>(null)
const selectedDeviceType = ref<{ id: string; label: string } | null>(null)
const selectedItamUser = ref<{ id: string; label: string } | null>(null)
const selectedMerchant = ref<{ id: string; label: string } | null>(null)
const selectedPosition = ref<{ id: string; label: string } | null>(null)
const selectedQuantityUnit = ref<{ id: string; label: string } | null>(null)
const serialNumber = ref('')
const serialNumber2 = ref('')
const deviceLoading = ref(false)
const isSaving = ref(false)
const { fieldErrors, generalError, handleApiError, clearErrors, clearFieldError } = useApiErrors()

// Id und Modus laden.
const deviceId = computed(() => {
    const param = new URLSearchParams(window.location.search).get('deviceId')
    return param ? parseInt(param, 10) : undefined
})
const isEditMode = computed(() => !!deviceId.value)

// Define related entities.
const itamUsers = ref<{ id: string; label: string }[]>([])
const deviceStatis = ref<{ id: string; label: string}[]>([])
const deviceTypes = ref<{ id: string; label: string}[]>([])
const merchants = ref<{ id: string; label: string }[]>([])
const positions = ref<{ id: string; label: string}[]>([])
const quantityUnits = ref<{ id: string; label: string}[]>([])
const deviceImage = reactive(useSfxonFileUploadField({ folder: 'ITAM-Images', accept: ['image/*'] }))

function onDeviceImageLocalFileChange(event: Event) {
    const files = Array.from((event.target as HTMLInputElement).files ?? [])
    deviceImage.onLocalFileSelected(files)
}

async function onDeviceImageFilePicker(): Promise<void> {
    await deviceImage.openNextcloudFilePicker(t('sfxonitam', 'Select device image'))
}

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
    deviceStatus: {
        selectTarget: selectedDeviceStatus,
        optionsTarget: deviceStatis,
        labelFields: { fields: ['name'] },
    },
    deviceType: {
        selectTarget: selectedDeviceType,
        optionsTarget: deviceTypes,
        labelFields: { fields: ['name'] },
    },
    quantityUnit: {
        selectTarget: selectedQuantityUnit,
        optionsTarget: quantityUnits,
        labelFields: { fields: ['name'] },
    },
    itamUser: {
        selectTarget: selectedItamUser,
        optionsTarget: itamUsers,
        labelFields: { fields: ['firstname', 'lastname'], technique: 'concat', separator: ' ' },
    },
    merchant: {
        selectTarget: selectedMerchant,
        optionsTarget: merchants,
        labelFields: { fields: ['name'] },
    },
    position: {
        selectTarget: selectedPosition,
        optionsTarget: positions,
        labelFields: { fields: ['name'] },
    },
}
const relations = reactive(buildRelations(props.entityDefinitions, entityConfig))
/* End: relation definition */

function addItem() {
    window.location.href = generateUrl('/apps/sfxonitam/device/detail')
}

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

async function loadDevice(id: number): Promise<void> {
    try {
        deviceLoading.value = true
        const data = await DeviceService.fetchDevice(id)

        /* Setup dropdowns */
        // Helper for checking, if object is set. If there was no relation set and so no relation loaded, this prevents a faulty access on the object.
       const firstOf = (obj: any) => obj ? Object.values(obj)[0] ?? null : null 

        addEntityData(deviceStatis, firstOf(data.relations.deviceStatus), 'id')
        addEntityData(deviceTypes, firstOf(data.relations.deviceType), 'id')
        addEntityData(itamUsers, firstOf(data.relations.itamUser), 'id', { fields: ['firstname', 'lastname'], technique: 'concat', separator: ' ' })
        addEntityData(merchants, firstOf(data.relations.merchant), 'id')
        addEntityData(positions, firstOf(data.relations.position), 'id')
        addEntityData(quantityUnits, firstOf(data.relations.quantityUnit), 'id')
        
        /* Load main data. */
        const d = data.mainData;

        assetNumber.value = d.assetNumber ?? ''
        description.value = d.description ?? ''
        invoiceNumber.value = d.invoiceNumber ?? ''
        name.value = d.name ?? ''
        purchaseDate.value = d.purchaseDate ? parseLocalDateString(d.purchaseDate) : null
        quantity.value = d.quantity ? String(parseFloat(d.quantity)) : ''
        selectedDeviceStatus.value = deviceStatis.value.find(s => s.id === d.deviceStatusId) ?? null
        selectedDeviceType.value = deviceTypes.value.find(s => s.id === d.deviceTypeId) ?? null
        selectedItamUser.value = itamUsers.value.find(u => u.id === d.itamUserId) ?? null
        selectedMerchant.value = merchants.value.find(u => u.id === d.merchantId) ?? null
        selectedPosition.value = positions.value.find(s => s.id === d.positionId) ?? null
        selectedQuantityUnit.value = quantityUnits.value.find(s => s.id === d.quantityUnitId) ?? null
        serialNumber.value = d.serialNumber ?? ''
        serialNumber2.value = d.serialNumber2 ?? ''

	// Load image preview.
        await deviceImage.setFromExisting(d.imageFileId ?? null)
	customFieldInitialValues.value = d.customFields ?? {}
    } catch (e: any) {
        generalError.value = t('sfxonitam', 'Could not load device.')
        console.error('Error while loading device:', e)
    } finally {
        deviceLoading.value = false
    }
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

async function saveAndBack() {
    const submitSuccess = await submitForm()
    
    if (submitSuccess) {
        window.location.href = generateUrl('/apps/sfxonitam')
    }
}

async function saveAndNew() {
    const submitSuccess = await submitForm()
    
    if (submitSuccess) {
        window.location.href = generateUrl('/apps/sfxonitam/device/detail')
    }
}

async function searchDeviceStatis(query: string, signal: AbortSignal): Promise<void> {
    let filters = {
        name: [query]
    };

    const data = await DeviceStatusService.findDeviceStatis({ filters: filters }, signal)

    if (data === null || data.mainData === null) {
        return;
    }

    deviceStatis.value = Object.values(data.mainData).map((deviceStatus: any) => ({
        id: deviceStatus.id,
        label: deviceStatus.name
    }))
}

async function searchDeviceTypes(query: string, signal: AbortSignal): Promise<void> {
    let filters = {
        name: [query]
    };

    const data = await DeviceTypeService.findDeviceTypes({ filters: filters }, signal)

    if (data === null || data.mainData === null) {
        return;
    }

    deviceTypes.value = Object.values(data.mainData).map((deviceType: any) => ({
        id: deviceType.id,
        label: deviceType.name
    }))
}

async function searchItamUsers(query: string, signal: AbortSignal): Promise<void> {
    let filters = {
        firstname: [query],
        lastname: [query],
        email: [query]
    };

    const data = await ItamUserService.findItamUsers({ filters: filters }, signal)

    if (data === null || data.mainData === null) {
        return;
    }

    itamUsers.value = Object.values(data.mainData).map((itamUser: any) => ({
        id: itamUser.id,
        label: itamUser.firstname + ' ' + itamUser.lastname
    }))
}

async function searchMerchants(query: string, signal: AbortSignal): Promise<void> {
    let filters = {
        name: [query]
    };

    const data = await MerchantService.findMerchants({ filters: filters }, signal)

    if (data === null || data.mainData === null) {
        return;
    }

    merchants.value = Object.values(data.mainData).map((merchant: any) => ({
        id: merchant.id,
        label: merchant.name
    }))
}

async function searchPositions(query: string, signal: AbortSignal): Promise<void> {
    let filters = {
        name: [query]
    };

    let include = {
        location: {}
    };

    const data = await PositionService.findPositions(
        { 
            filters: filters,
            include: include
        }, signal
    )

    if(data === null || data.result === null || data.result.mainData === null) {
        return;
    }

    positions.value = Object.values(data.result.mainData).map((position: any) => {
        const location = data.result.relations?.location?.[position.locationId];
        const label = location ? `${location.name} - ${position.name}` : position.name;
        return {
            id: position.id,
            label
        };
    });
}

async function searchQuantityUnits(query: string, signal: AbortSignal): Promise<void> {
    let filters = {
        name: [query]
    };

    const data = await QuantityUnitService.findQuantityUnits({ filters: filters }, signal)

    if (data === null || data.mainData === null) {
        return;
    }

    quantityUnits.value = Object.values(data.mainData).map((quantityUnit: any) => ({
        id: quantityUnit.id,
        label: quantityUnit.name
    }))
}

function onBackButton() {
    window.location.href = generateUrl('/apps/sfxonitam')
}

async function submitForm() {
    clearErrors()
    isSaving.value = true

    try {
        await deviceImage.uploadIfNeeded()
        await customFieldsRef.value?.uploadPendingFiles()
    } catch (e) {
        console.error('Image upload failed:', e)
        generalError.value = t('sfxonitam', 'Image upload failed.')
        isSaving.value = false
        return
    }

    const payload = {
        assetNumber: assetNumber.value,
        deviceStatusId: selectedDeviceStatus.value?.id ?? null,
        deviceTypeId: selectedDeviceType.value?.id ?? null,
        description: description.value,
        imageFileId: deviceImage.fileId,
        invoiceNumber: invoiceNumber.value,
        itamUserId: selectedItamUser.value?.id ?? null,
        merchantId: selectedMerchant.value?.id ?? null,
        name: name.value,
        positionId: selectedPosition.value?.id ?? null,
        quantity: quantity.value,
        quantityUnitId: selectedQuantityUnit.value?.id ?? null,
        purchaseDate: purchaseDate.value ? toLocalDateString(purchaseDate.value) : null,
        serialNumber: serialNumber.value,
        serialNumber2: serialNumber2.value,
        customFields: customFieldValues.value,
    }

    try {
        const data = isEditMode.value
            ? await DeviceService.updateDevice(deviceId.value!, payload)
            : await DeviceService.createDevice(payload)

        // Backend returns status: 'error' with HTTP 200
        if (data?.status === 'error') {
            handleApiError(data, t('sfxonitam', 'Bitte korrigiere die markierten Felder.'))
            return
        }

        triggerSaveSuccess()
        isSaving.value = false
        return true
    } catch (error: any) {
        // HTTP-Error (4xx/5xx), backend may despite return JSON .
        const data = error?.response?.data

        if (data?.status === 'error') {
            handleApiError(data, t('sfxonitam', 'Bitte korrigiere die markierten Felder.'))
        } else {
            generalError.value = t('sfxonitam', 'Unbekannter Fehler beim Speichern.')
        }

        isSaving.value = false
    }

    return false
}

function triggerSaveSuccess() {
    const HIDE_DELAY = 3500 // ms

    if (saveVisible.value) {
        // Retrigger: with prominent highlighting.
        saveIsReflash.value = true
        saveFlashKey.value++
        setTimeout(() => { saveIsReflash.value = false }, 600)
    } else {
        saveVisible.value = true
        saveIsReflash.value = false
        saveFlashKey.value++
    }

    if (saveHideTimer) clearTimeout(saveHideTimer)
    saveHideTimer = setTimeout(() => {
        saveVisible.value = false
    }, HIDE_DELAY)
}

onMounted(async () => {
    if (deviceId.value) {
        await loadDevice(deviceId.value)
    }
})
</script>

<template>
    <NcContent app-name="sfxonitamdeviceeditor">
        <NcAppNavigation>
            <NcAppNavigationList>
                <NcAppNavigationNew
                :text="t('sfxonitam', 'Add Device')"
                @click="addItem"
                >
                    <template #icon>
                        <NcIconSvgWrapper :path="mdiPlus" :size="20" />
                    </template>
                </NcAppNavigationNew>
            </NcAppNavigationList>
            <SfxonMainNavigation :currentPage="'devices'" />
        </NcAppNavigation>

        <!-- Inhaltsbereich -->
        <NcAppContent>
            <SfxonItamHeader
                :titleLabel="isEditMode ? t('sfxonitam', 'Edit device') : t('sfxonitam', 'Create device')"
            >
                <template #actionButtonsRight>
                    <NcButton
                        @click="onBackButton"
                    >
                        {{ t('sfxonitam', 'Back') }}
                    </NcButton>
                </template>
            </SfxonItamHeader>

            <!-- Form -->
            <div :class="SfxonEditorStyles.form">
                <div
                    :class="SfxonEditorStyles.myfavNotificationContainer"
                    v-if="generalError"
                >
                    <NcNoteCard type="error">
                        {{ generalError }}
                    </NcNoteCard>
                </div>

                <!-- Save animation -->
                <div :class="$style.saveBadgeOuter">
                    <Transition name="save-badge">
                        <div
                            v-if="saveVisible"
                            :key="saveFlashKey"
                            :class="[$style.saveBadge, saveIsReflash && $style.saveBadgeReflash]"
                            aria-live="polite"
                            role="status"
                        >
                            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" :class="$style.saveBadgeIcon">
                                <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="1.8" fill="rgba(255,255,255,0.1)"/>
                                <polyline points="7,12.5 10.5,16 17,9" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
                            </svg>
                        </div>
                    </Transition>
                </div>

                <div :class="[SfxonEditorStyles.sfxonFormRow, $style.sfxonFormRow1]">
                    <div :class="[SfxonEditorStyles.sfxonFormSection, $style.sfxonFormSection1]">
                        <div :class="[SfxonEditorStyles.sfxonFormColumn, $style.sfxonFormSection1Column]">
                            <SfxonEditorFormInput
                                field="name"
                                id="name"
                                v-model="name"
                                :label="t('sfxonitam', 'Device Identifier') + ':'"
                                type="text"
                                :placeholder="t('sfxonitam', 'e.g. JP001')"
                                @input="clearFieldError('name')"
                                :fieldError="fieldErrors.name"
                            />

                            <SfxonEditorFormInput
                                field="quantity"
                                id="quantity"
                                v-model="quantity"
                                :label="t('sfxonitam', 'Quantity') + ':'"
                                type="number"
                                @input="clearFieldError('quantity')"
                                :fieldError="fieldErrors.quantity"
                            />

                            <SfxonEditorFormEntitySelect
                                :addRecordFn="() => openAddEntityDialog({ entity: 'quantityUnit' })"
                                field="quantityUnitId"
                                :fieldError="fieldErrors.quantityUnitId"
                                id="quantity-unit-select"
                                @input="clearFieldError('quantityUnitId')"
                                :label="t('sfxonitam', 'Quantity Unit') + ':'"
                                :options="quantityUnits"
                                :searchFn="searchQuantityUnits"
                                trackBy="id"
                                v-model="selectedQuantityUnit"
                            />

                            <SfxonEditorFormEntitySelect
                                :addRecordFn="() => openAddEntityDialog({ entity: 'itamUser' })"
                                field="itamUserId"
                                :fieldError="fieldErrors.itamUserId"
                                id="itam-user-select"
                                @input="clearFieldError('itamUserId')"
                                :label="t('sfxonitam', 'User') + ':'"
                                :options="itamUsers"
                                :searchFn="searchItamUsers"
                                trackBy="id"
                                v-model="selectedItamUser"
                            />

                            <SfxonEditorFormEntitySelect
                                :addRecordFn="() => openAddEntityDialog({ entity: 'position' })"
                                field="positionId"
                                :fieldError="fieldErrors.positionId"
                                id="position-select"
                                @input="clearFieldError('positionId')"
                                :label="t('sfxonitam', 'Position') + ':'"
                                :options="positions"
                                :searchFn="searchPositions"
                                trackBy="id"
                                v-model="selectedPosition"
                            />

                            <SfxonEditorFormInput
                                field="serialNumber"
                                id="serialNumber"
                                v-model="serialNumber"
                                :label="t('sfxonitam', 'Serial Number') + ':'"
                                type="text"
                                @input="clearFieldError('serialNumber')"
                                :fieldError="fieldErrors.serialNumber"
                            />

                            <SfxonEditorFormInput
                                field="serialNumber2"
                                id="serialNumber2"
                                v-model="serialNumber2"
                                :label="t('sfxonitam', 'Serial Number 2') + ':'"
                                type="text"
                                @input="clearFieldError('serialNumber2')"
                                :fieldError="fieldErrors.serialNumber2"
                            />

                            <SfxonEditorFormInput
                                field="assetNumber"
                                id="assetNumber"
                                v-model="assetNumber"
                                :label="t('sfxonitam', 'Asset Number') + ':'"
                                type="text"
                                @input="clearFieldError('assetNumber')"
                                :fieldError="fieldErrors.assetNumber"
                            />
                        </div>
                        <div :class="[SfxonEditorStyles.sfxonFormColumn, $style.sfxonFormColumnGeneralRight, $style.sfxonFormSection1Column]">
                            <SfxonEditorFormEntitySelect
                                :addRecordFn="() => openAddEntityDialog({ entity: 'deviceStatus' })"
                                field="deviceStatusId"
                                :fieldError="fieldErrors.deviceStatusId"
                                id="device-status-select"
                                @input="clearFieldError('deviceStatusId')"
                                :label="t('sfxonitam', 'Device Status') + ':'"
                                :options="deviceStatis"
                                :searchFn="searchDeviceStatis"
                                trackBy="id"
                                v-model="selectedDeviceStatus"
                            />

                            <SfxonEditorFormEntitySelect
                                :addRecordFn="() => openAddEntityDialog({ entity: 'deviceType' })"
                                field="deviceTypeId"
                                :fieldError="fieldErrors.deviceTypeId"
                                id="device-type-select"
                                @input="clearFieldError('deviceTypeId')"
                                :label="t('sfxonitam', 'Device Type') + ':'"
                                :options="deviceTypes"
                                :searchFn="searchDeviceTypes"
                                trackBy="id"
                                v-model="selectedDeviceType"
                            />

                            <SfxonCustomFields
                                ref="customFieldsRef"
                                :customFields="customFields"
                                :initial-values="customFieldInitialValues"
                                @update:values="onCustomFieldValuesUpdate"
                            />
                        </div>
                    </div>

                    <!-- Device Image -->
                    <div :class="[SfxonEditorStyles.sfxonFormSection, $style.sfxonFormSection2]">
                        <div :class="SfxonEditorStyles.sfxonFormColumn">
                            <SfxonEditorFormImageSelector
                                field="image"
                                :field-error="fieldErrors.image || deviceImage.validationError"
                                id="device-image"
                                :image-preview-url="deviceImage.previewUrl"
                                :is-image="deviceImage.isImage"
                                :download-url="deviceImage.downloadUrl"
                                :accept="deviceImage.acceptAttr"
                                @input="clearFieldError('image')"
                                :label="t('sfxonitam', 'Device Image')"
                                @local-file-change="onDeviceImageLocalFileChange"
                                @nextcloud-file-picker="onDeviceImageFilePicker"
                                @preview-error="deviceImage.onPreviewError"
                                :selected-image-label="deviceImage.selectedLabel"
                            />
                        </div>
                    </div>

                    <!-- Description -->
                    <div :class="[SfxonEditorStyles.sfxonFormSection, $style.sfxonFormSection3]">
                        <div :class="$style.sfxonFormColumnDescription">
                            <SfxonEditorFormTextarea
                                field="description"
                                :fieldError="fieldErrors.description"
                                id="description"
                                @input="clearFieldError('description')"
                                :label="t('sfxonitam', 'Description') + ':'"
                                v-model="description"
                            />
                        </div>
                    </div>

                    <!-- Purchase Information -->
                    <div :class="[SfxonEditorStyles.sfxonFormSection, $style.sfxonFormSection4]">
                        <div :class="SfxonEditorStyles.sfxonFormColumn">
                            <SfxonEditorFormEntitySelect
                                :addRecordFn="() => openAddEntityDialog({ entity: 'merchant' })"
                                field="merchantId"
                                :fieldError="fieldErrors.merchantId"
                                id="device-type-select"
                                @input="clearFieldError('merchantId')"
                                :label="t('sfxonitam', 'Merchant') + ':'"
                                :options="merchants"
                                :searchFn="searchMerchants"
                                trackBy="id"
                                v-model="selectedMerchant"
                            />

                            <SfxonEditorFormInput
                                field="invoiceNumber"
                                :fieldError="fieldErrors.invoiceNumber"
                                id="invoiceNumber"
                                @input="clearFieldError('invoiceNumber')"
                                :label="t('sfxonitam', 'Invoice Number') + ':'"
                                type="text"
                                v-model="invoiceNumber"
                            />

                            <SfxonEditorFormDatePicker
                                field="purchaseDate"
                                :fieldError="fieldErrors.purchaseDate"
                                id="purchaseDate"
                                @input="clearFieldError('purchaseDate')"
                                :label="t('sfxonitam', 'Purchase Date') + ':'"
                                v-model="purchaseDate"
                            />
                        </div>
                    </div>

                    <!-- QrCode and Barcode -->
                    <div :class="[SfxonEditorStyles.sfxonFormSection, $style.sfxonFormSection5]">
                        <div :class="SfxonEditorStyles.sfxonFormColumn">
                            <!-- QR Code -->
                            <SfxonQrCodeView
                                v-if="isEditMode"
                                :deviceId="deviceId"
                                customStyle="width: 100%; max-width: 150px; height: auto;"
                            />

                            <!-- Barcode -->
                            <SfxonBarcode
                                v-if="isEditMode && name"
                                :name="name"
                                prefix="DEV"
                                customStyle="width: 100%; margin-top: 12px; max-width: 150px; height: auto;"
                            />
                        </div>
                    </div>
                </div>

                <!-- Bottom Action Bar -->
                <div :class="[SfxonEditorStyles.sfxonFormRow, SfxonEditorStyles.sfxonFormRowActionBar, $style.sfxonFormRowActionBar]">
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
                                    @click="submitForm">
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

<style module>
.sfxonFormRow1 {
    display: grid !important;
    grid-template-columns: 1fr;
    gap: 1rem;
}

.sfxonFormRow1 > * {
    min-width: 0;
}

.sfxonFormSection1 :global(.sfxonFormColumn) {
    width: 100%;
}

@media (min-width: 1200px) and (max-width: 1699px) {
    .sfxonFormRow1 {
        grid-template-columns: 1fr 1fr;
    }

    .sfxonFormSection1 {
        grid-column: 1 / -1;
    }
    
    .sfxonFormSection1 :global(.sfxonFormColumn) {
        flex: 1 1 0;
    }

    .sfxonFormSection2 {
        grid-column: 1 / 2;
    }

    .sfxonFormSection3 {
        grid-column: 2 / 3;
    }

    .sfxonFormSection4 {
        grid-column: 1 / 2;
    }

    .sfxonFormSection5 {
        grid-column: 2 / 3;
    }
}

@media (min-width: 1700px) {
    .sfxonFormRow1 {
        grid-template-columns: 1fr 1fr 1fr;
        grid-template-areas:
            "sec1 sec1 sec2"
            "sec3 sec4 sec5";
    }

    .sfxonFormSection1 {
        grid-area: sec1;
        gap: 3rem;
    }

    .sfxonFormSection2 {
        grid-area: sec2;
    }

    .sfxonFormSection3 {
        grid-area: sec3;
    }

    .sfxonFormSection4 {
        grid-area: sec4;
    }

    .sfxonFormSection5 {
        grid-area: sec5;
    }
}

/* Description Textarea */
.sfxonFormColumnDescription {
    min-height: 200px;
    width: 100%;
}

/* Save Badge */
.saveBadgeOuter {
    position: absolute;
    top: 6px;
    right: -134px;
    width: 126px;
    height: 100%;
    overflow: visible;
    pointer-events: none;
    z-index: 1500;
}

.saveBadge {
    align-items: center;
    animation: saveBadgeEnter 0.45s cubic-bezier(0.34, 1.56, 0.64, 1) both;
    background: color-mix(in srgb, var(--color-success) 80%, transparent);
    border-radius: var(--border-radius-large, 12px);
    box-shadow:
        0 6px 20px color-mix(in srgb, var(--color-success) 50%, transparent),
        0 2px 6px rgba(0,0,0,0.15),
        inset 0 1px 0 rgba(255,255,255,0.15);
    display: flex;
    height: 108px;
    justify-content: center;
    pointer-events: auto;
    position: sticky;
    right: 0;
    top: 12px; /*top: calc(var(--header-height, 50px) + 50px);*/
    transform-origin: top right;
    translate: -126px 0;
    width: 108px;
    z-index: 9999;
}

.saveBadgeIcon {
    color: var(--color-success-text);
    height: 66px;
    width: 66px;
}

/* Retrigger: Bounce and Flash */
.saveBadgeReflash {
    animation:
        saveBadgeEnter 0.45s cubic-bezier(0.34, 1.56, 0.64, 1) both,
        saveBadgeFlash 0.55s ease-out both;
}

/* Vue Transition for fadeout. */
:global(.save-badge-leave-active) {
    display: none;
    position: absolute; /* Step out of the flow, so that the new icon can stick at the top. */
    transition: opacity 0.4s ease, transform 0.4s ease;
}

:global(.save-badge-leave-to) {
    opacity: 0;
    transform: scale(0.8) translateY(-4px);
}

@keyframes saveBadgeEnter {
    0%   { opacity: 0; transform: scale(0.5) translateY(-8px); }
    100% { opacity: 1; transform: scale(1) translateY(0); }
}

@keyframes saveBadgeFlash {
    0%   { box-shadow: 0 6px 20px color-mix(in srgb, var(--color-success) 50%, transparent), 0 2px 6px rgba(0,0,0,0.15); }
    30%  { box-shadow: 0 0 0 10px color-mix(in srgb, var(--color-success) 35%, transparent), 0 8px 30px color-mix(in srgb, var(--color-success) 70%, transparent); }
    100% { box-shadow: 0 6px 20px color-mix(in srgb, var(--color-success) 50%, transparent), 0 2px 6px rgba(0,0,0,0.15); }
}

/* Error Messages */
.sfxonModalDialogErrorMsg {
    margin-top: 0!important;
}

.sfxonModalDialogErrorMsg :global(span.icon-vue) {
    align-self: start;
    padding-top: 3px;
}
</style>