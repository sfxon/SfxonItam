<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import NcAppContent from '@nextcloud/vue/components/NcAppContent'
import NcAppNavigation from '@nextcloud/vue/components/NcAppNavigation'
import NcAppNavigationList from '@nextcloud/vue/components/NcAppNavigationList'
import NcAppNavigationNew from '@nextcloud/vue/components/NcAppNavigationNew'
import NcContent from '@nextcloud/vue/components/NcContent'
import { mdiPlus } from '@mdi/js'
import { translate as t } from '@nextcloud/l10n'
import { generateUrl, generateRemoteUrl } from '@nextcloud/router'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcNoteCard from '@nextcloud/vue/components/NcNoteCard'
import { useApiErrors } from '@/composables/useApiErrors'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import { fetchDevice, createDevice, updateDevice } from '@/services/DeviceService'

/*
import { fetchAllLocations } from '@/services/LocationService'
*/

import { findDeviceStatis } from '@/services/DeviceStatusService'
import { findDeviceTypes } from '@/services/DeviceTypeService'
import { findItamUsers } from '@/services/ItamUserService'
import { findMerchants } from '@/services/MerchantService'
import { findPositions } from '@/services/PositionService'
import { findQuantityUnits } from '@/services/QuantityUnitService'

import SfxonBarcode from '@/components/SfxonBarcode'
import SfxonEditorFormDatePicker from '@/components/SfxonEditorFormDatePicker'
import SfxonEditorFormEntitySelect from '@/components/SfxonEditorFormEntitySelect'
import SfxonEditorFormImageSelector from '@/components/SfxonEditorFormImageSelector'
import SfxonEditorFormInput from '@/components/SfxonEditorFormInput'
import SfxonEditorFormTextarea from '@/components/SfxonEditorFormTextarea'
import SfxonEditorStyles from '@/components/SfxonEditor/SfxonEditor.module.css'
import SfxonItamHeader from '@/components/SfxonItamHeader'
import SfxonMainNavigation from '@/components/SfxonMainNavigation'
import SfxonQrCodeView from '@/components/SfxonQrCodeView'
import { getCurrentUser } from '@nextcloud/auth'

// Formulardaten
const assetNumber = ref('')
const description = ref('')
const imageFileId = ref<number | null>(null)
const imagePreviewUrl = ref<string | null>(null)
const invoiceNumber = ref('')
const name = ref('')
const purchaseDate = ref<Date | null>(null)
const quantity = ref('')
const selectedDeviceStatus = ref<{ id: string; label: string } | null>(null)
const selectedDeviceType = ref<{ id: string; label: string } | null>(null)
const selectedImageFile = ref<File | null>(null)
const selectedRemoteFile = ref<{ id: number; name: string; path: string } | null>(null)
const selectedItamUser = ref<{ id: string; label: string } | null>(null)
const selectedMerchant = ref<{ id: string; label: string } | null>(null)
const selectedPosition = ref<{ id: string; label: string } | null>(null)
const selectedQuantityUnit = ref<{ id: string; label: string } | null>(null)
const serialNumber = ref('')
const serialNumber2 = ref('')
const savedSuccessfully = ref(false)

// Ladezustände
const deviceLoading = ref(false)
const deviceStatisLoading = ref(false)
const deviceTypesLoading = ref(false)
const isSaving = ref(false)
const itamUsersLoading = ref(false)
// const locationsLoading = ref(false)
const merchantsLoading = ref(false)
const positionsLoading = ref(false)
const quantityUnitsLoading = ref(false)

// Fehlerbehandlung
const { fieldErrors, generalError, handleApiError, clearErrors, clearFieldError } = useApiErrors()

function addItem() {
    window.location.href = generateUrl('/apps/sfxonitam/device/detail')
}

// Id und Modus laden.
const deviceId = computed(() => {
    const param = new URLSearchParams(window.location.search).get('deviceId')
    return param ? parseInt(param, 10) : undefined
})
const isEditMode = computed(() => !!deviceId.value)

// Abhängige Entitäten definieren.
const itamUsers = ref<{ id: string; label: string }[]>([])
const deviceStatis = ref<{ id: string; label: string}[]>([])
const deviceTypes = ref<{ id: string; label: string}[]>([])
const locations = ref<{ id: string; label: string}[]>([])
const merchants = ref<{ id: string; label: string }[]>([])
const positions = ref<{ id: string; label: string}[]>([])
const quantityUnits = ref<{ id: string; label: string}[]>([])

const toLocalDateString = (date: Date): string => {
    const y = date.getFullYear()
    const m = String(date.getMonth() + 1).padStart(2, '0')
    const d = String(date.getDate()).padStart(2, '0')
    return `${y}-${m}-${d}`
}

const selectedImageLabel = computed(() => {
    if (selectedImageFile.value) {
        return selectedImageFile.value.name
    }
    if (selectedRemoteFile.value) {
        return selectedRemoteFile.value.name
    }
    return ''
})

function addEntityData(entity, dataObject, identifierField, valueFields = null) {
    if('undefined' === typeof dataObject) {
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

async function loadDevice(id: number): Promise<void> {
    deviceLoading.value = true

    try {
        const data = await fetchDevice(id)

        /* Setup dropdowns */
        addEntityData(deviceStatis, Object.values(data.relations.deviceStatus)[0], 'id')
        addEntityData(deviceTypes, Object.values(data.relations.deviceType)[0], 'id')
        addEntityData(itamUsers, Object.values(data.relations.itamUser)[0], 'id', { fields: ['firstname', 'lastname'], technique: 'concat', separator: ' '})
        addEntityData(merchants, Object.values(data.relations.merchant)[0], 'id')
        addEntityData(positions, Object.values(data.relations.position)[0], 'id')
        addEntityData(quantityUnits, Object.values(data.relations.quantityUnit)[0], 'id')
        
        /* Load main data. */
        const d = data.mainData;

        assetNumber.value = d.assetNumber ?? ''
        description.value = d.description ?? ''
        imageFileId.value = d.imageFileId ?? null
        invoiceNumber.value = d.invoiceNumber ?? ''
        name.value = d.name ?? ''
        purchaseDate.value = d.purchaseDate ? new Date(d.purchaseDate + 'T00:00:00') : null
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
        if (imageFileId.value) {
            imagePreviewUrl.value = generateUrl(`/core/preview?fileId=${imageFileId.value}&x=300&y=300&a=1`)
        }
    } catch (e: any) {
        generalError.value = t('sfxonitam', 'Could not load device.')
        console.error('Error while loading device:', e)
    } finally {
        deviceLoading.value = false
    }
}

async function searchDeviceStatis(query: string, signal: AbortSignal): Promise<void> {
    deviceStatisLoading.value = true;

    let filters = {
        name: [query]
    };

    const data = await findDeviceStatis({ filters: filters }, signal)

    deviceStatis.value = Object.values(data.mainData).map((deviceStatus: any) => ({
        id: deviceStatus.id,
        label: deviceStatus.name
    }))

    deviceStatisLoading.value = false;
}

async function searchDeviceTypes(query: string, signal: AbortSignal): Promise<void> {
    deviceTypesLoading.value = true;

    let filters = {
        name: [query]
    };

    const data = await findDeviceTypes({ filters: filters }, signal)

    deviceTypes.value = Object.values(data.mainData).map((deviceType: any) => ({
        id: deviceType.id,
        label: deviceType.name
    }))

    deviceTypesLoading.value = false;
}

async function searchItamUsers(query: string, signal: AbortSignal): Promise<void> {
    itamUsersLoading.value = true;

    let filters = {
        firstname: [query],
        lastname: [query],
        email: [query]
    };

    const data = await findItamUsers({ filters: filters }, signal)

    itamUsers.value = Object.values(data.mainData).map((itamUser: any) => ({
        id: itamUser.id,
        label: itamUser.firstname + ' ' + itamUser.lastname
    }))

    itamUsersLoading.value = false;
}

/*
async function loadLocations() {
    locationsLoading.value = true;

    try {
        const data = await fetchAllLocations({})

        locations.value = Object.values(data.locations).map((location: any) => ({
            id: location.id,
            label: location.name
        }))
    } catch(e) {
        console.error('Fehler beim Laden der Locations', e)
    } finally {
        locationsLoading.value = false
    }
}
*/

async function searchMerchants(query: string, signal: AbortSignal): Promise<void> {
    merchantsLoading.value = true;

    let filters = {
        name: [query]
    };

    const data = await findMerchants({ filters: filters }, signal)

    merchants.value = Object.values(data.mainData).map((merchant: any) => ({
        id: merchant.id,
        label: merchant.name
    }))

    merchantsLoading.value = false;
}

async function searchPositions(query: string, signal: AbortSignal): Promise<void> {
    positionsLoading.value = true;

    let filters = {
        name: [query]
    };

    const data = await findPositions({ filters: filters }, signal)

    positions.value = Object.values(data.mainData).map((position: any) => ({
        id: position.id,
        label: position.name
    }))

    positionsLoading.value = false;
}

async function searchQuantityUnits(query: string, signal: AbortSignal): Promise<void> {
    quantityUnitsLoading.value = true;

    let filters = {
        name: [query]
    };

    const data = await findQuantityUnits({ filters: filters }, signal)

    quantityUnits.value = Object.values(data.mainData).map((quantityUnit: any) => ({
        id: quantityUnit.id,
        label: quantityUnit.name
    }))

    quantityUnitsLoading.value = false;
}

function onBackButton() {
    window.location.href = generateUrl('/apps/sfxonitam')
}

function onLocalFileChange(event: Event) {
    const files = Array.from((event.target as HTMLInputElement).files ?? [])
    onLocalImageSelected(files)
}

function onLocalImageSelected(files: File[]) {
    const file = files?.[0] ?? null
    if (!file) {
        return
    }

    if (imagePreviewUrl.value) {
        URL.revokeObjectURL(imagePreviewUrl.value)
    }

    selectedImageFile.value = file
    selectedRemoteFile.value = null
    imageFileId.value = null
    imagePreviewUrl.value = URL.createObjectURL(file)
}

async function openNextcloudFilePicker(): Promise<void> {
    return new Promise((resolve) => {
        // @ts-ignore : OC is globally available in every NC-Instance.
        OC.dialogs.filepicker(
            t('sfxonitam', 'Select device image'),
            async (path: string) => {
                try {
                    const user = getCurrentUser()
                    const davUrl = generateRemoteUrl(`dav/files/${user?.uid}${path}`)

                    const res = await fetch(davUrl, {
                        method: 'PROPFIND',
                        credentials: 'include',
                        headers: {
                            'Depth': '0',
                            'Content-Type': 'application/xml',
                            'requesttoken': (window as any).oc_requesttoken,
                        },
                        body: `<?xml version="1.0"?>
                            <d:propfind xmlns:d="DAV:" xmlns:oc="http://owncloud.org/ns">
                                <d:prop><oc:fileid/></d:prop>
                            </d:propfind>`,
                    })

                    const xml = new DOMParser().parseFromString(await res.text(), 'application/xml')
                    const fileIdEl = xml.getElementsByTagNameNS('http://owncloud.org/ns', 'fileid')[0]
                    const fileId = Number(fileIdEl?.textContent ?? 0) || null

                    if (!fileId) {
                        console.error('PROPFIND returned no fileId, status:', res.status)
                        generalError.value = t('sfxonitam', 'Could not resolve file ID.')
                        resolve()
                        return
                    }

                    if (imagePreviewUrl.value) {
                        URL.revokeObjectURL(imagePreviewUrl.value)
                        imagePreviewUrl.value = null
                    }

                    selectedRemoteFile.value = {
                        id: fileId,
                        name: path.split('/').pop() ?? path,
                        path,
                    }
                    imageFileId.value = fileId
                    imagePreviewUrl.value = generateUrl(`/core/preview?fileId=${fileId}&x=300&y=300&a=1`)
                    selectedImageFile.value = null
                } catch (e) {
                    console.error('Error resolving fileId:', e)
                    generalError.value = t('sfxonitam', 'Could not select a file.')
                } finally {
                    resolve()
                }
            },
            false, // multiselect
            ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'],
            true, // modal
            1, // OC.dialogs.FILEPICKER_TYPE_CHOOSE
        )
    })
}

async function submitForm() {
    clearErrors()
    savedSuccessfully.value = false;
    isSaving.value = true

    try {
        await uploadImageIfNeeded()
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
        imageFileId: imageFileId.value,
        invoiceNumber: invoiceNumber.value,
        itamUserId: selectedItamUser.value?.id ?? null,
        merchantId: selectedMerchant.value?.id ?? null,
        name: name.value,
        positionId: selectedPosition.value?.id ?? null,
        quantity: quantity.value,
        quantityUnitId: selectedQuantityUnit.value?.id ?? null,
        purchaseDate: purchaseDate.value ? toLocalDateString(purchaseDate.value) : null,
        serialNumber: serialNumber.value,
        serialNumber2: serialNumber2.value
    }

    try {
        const data = isEditMode.value
            ? await updateDevice(deviceId.value!, payload)
            : await createDevice(payload)

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
    } finally {
        isSaving.value = false
    }
}

async function uploadImageIfNeeded(): Promise<void> {
    if (!selectedImageFile.value) return

    const user = getCurrentUser()
    const filename = selectedImageFile.value.name
    const davUrl = generateRemoteUrl(`dav/files/${user?.uid}/ITAM-Images/${filename}`)

    // Ordner sicherstellen (MKCOL – ignoriert 405 wenn schon vorhanden)
    await fetch(generateRemoteUrl(`dav/files/${user?.uid}/ITAM-Images`), {
        method: 'MKCOL',
        credentials: 'include',
        headers: { 'requesttoken': (window as any).oc_requesttoken },
    })

    // Datei hochladen
    const putRes = await fetch(davUrl, {
        method: 'PUT',
        credentials: 'include',
        headers: {
            'requesttoken': (window as any).oc_requesttoken,
            'Content-Type': selectedImageFile.value.type,
        },
        body: selectedImageFile.value,
    })

    if (!putRes.ok) {
        throw new Error(`Upload failed: ${putRes.status}`)
    }

    // fileId per PROPFIND holen
    const propRes = await fetch(davUrl, {
        method: 'PROPFIND',
        credentials: 'include',
        headers: {
            'Depth': '0',
            'Content-Type': 'application/xml',
            'requesttoken': (window as any).oc_requesttoken,
        },
        body: `<?xml version="1.0"?>
            <d:propfind xmlns:d="DAV:" xmlns:oc="http://owncloud.org/ns">
                <d:prop><oc:fileid/></d:prop>
            </d:propfind>`,
    })

    const text = await propRes.text()
    const xml = new DOMParser().parseFromString(text, 'application/xml')
    const fileIdEl = xml.getElementsByTagNameNS('http://owncloud.org/ns', 'fileid')[0]
    const fileId = Number(fileIdEl?.textContent ?? 0) || null

    if (!fileId) {
        throw new Error('Could not resolve fileId after upload')
    }

    imageFileId.value = fileId
    selectedImageFile.value = null
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
                {{ t('sfxonitam', 'Changes have been saved.') }}
            </NcNoteCard>

            <div :class="SfxonEditorStyles.form">
                <div :class="[SfxonEditorStyles.sfxonFormRow, $style.sfxonFormRow1]">
                    <div :class="[SfxonEditorStyles.sfxonFormSection, $style.sfxonFormSection1]">
                        <div :class="SfxonEditorStyles.sfxonFormColumn">
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
                                field="quantityUnitId"
                                :fieldError="fieldErrors.quantityUnitId"
                                id="quantity-unit-select"
                                @input="clearFieldError('quantityUnitId')"
                                :label="t('sfxonitam', 'Quantity Unit') + ':'"
                                :loading="quantityUnitsLoading"
                                :options="quantityUnits"
                                :searchFn="searchQuantityUnits"
                                trackBy="id"
                                v-model="selectedQuantityUnit"
                            />

                            <SfxonEditorFormEntitySelect
                                field="userSelect"
                                :fieldError="fieldErrors.itamUserId"
                                id="user-select"
                                @input="clearFieldError('itamUserId')"
                                :label="t('sfxonitam', 'User') + ':'"
                                :loading="itamUsersLoading"
                                :options="itamUsers"
                                :searchFn="searchItamUsers"
                                trackBy="id"
                                v-model="selectedItamUser"
                            />

                            <SfxonEditorFormEntitySelect
                                field="positionSelect"
                                :fieldError="fieldErrors.positionId"
                                id="position-select"
                                @input="clearFieldError('positionId')"
                                :label="t('sfxonitam', 'Position') + ':'"
                                :loading="positionsLoading"
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
                        <div :class="[SfxonEditorStyles.sfxonFormColumn, $style.sfxonFormColumnGeneralRight]">
                            <SfxonEditorFormEntitySelect
                                field="deviceStatusSelect"
                                :fieldError="fieldErrors.deviceStatusId"
                                id="device-status-select"
                                @input="clearFieldError('deviceStatusId')"
                                :label="t('sfxonitam', 'Device Status') + ':'"
                                :loading="deviceStatisLoading"
                                :options="deviceStatis"
                                :searchFn="searchDeviceStatis"
                                trackBy="id"
                                v-model="selectedDeviceStatus"
                            />

                            <SfxonEditorFormEntitySelect
                                field="deviceTypeSelect"
                                :fieldError="fieldErrors.deviceTypeId"
                                id="device-type-select"
                                @input="clearFieldError('deviceTypeId')"
                                :label="t('sfxonitam', 'Device Type') + ':'"
                                :loading="deviceTypesLoading"
                                :options="deviceTypes"
                                :searchFn="searchDeviceTypes"
                                trackBy="id"
                                v-model="selectedDeviceType"
                            />
                        </div>
                    </div>
                    <div :class="SfxonEditorStyles.sfxonFormSection">
                        <div :class="SfxonEditorStyles.sfxonFormColumn">
                            <SfxonEditorFormImageSelector
                                field="image"
                                :field-error="fieldErrors.image"
                                id="device-image"
                                :image-preview-url="imagePreviewUrl"
                                @input="clearFieldError('image')"
                                :label="t('sfxonitam', 'Device Image')"
                                @local-file-change="onLocalFileChange"
                                @nextcloud-file-picker="openNextcloudFilePicker"
                                :selected-image-label="selectedImageLabel"
                            />
                        </div>
                    </div>

                    <!-- Description -->
                    <div :class="SfxonEditorStyles.sfxonFormSection">
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
                    <div :class="SfxonEditorStyles.sfxonFormSection">
                        <div :class="SfxonEditorStyles.sfxonFormColumn">
                            <SfxonEditorFormEntitySelect
                                field="merchantSelect"
                                :fieldError="fieldErrors.merchantId"
                                id="device-type-select"
                                @input="clearFieldError('merchantId')"
                                :label="t('sfxonitam', 'Merchant') + ':'"
                                :loading="merchantsLoading"
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
                    <div :class="SfxonEditorStyles.sfxonFormSection">
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
                                    variant="primary"
                                    @click="submitForm">
                                    {{ t('sfxonitam', 'Save Changes') }}
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
/* Page/View related layout */
@media (min-width: 768px) {
    .sfxonFormRow1 {
        grid-template-columns: repeat(2, 1fr);
    }

    .sfxonFormSection1 {
        grid-column: span 2;
    }
}

@media (min-width: 1700px) {
    .sfxonFormRow1 {
        grid-template-columns: repeat(3, 1fr);
    }

    .sfxonFormSection1 {
        display: flex;
        gap: calc(15px + (var(--app-navigation-padding)*4));
        grid-column: span 2;
    }
}

@media (min-width: 1700px) {
    .sfxonFormColumnGeneralRight {
        padding-left: 2rem;
    }
}

/* Description Textarea */
.sfxonFormColumnDescription {
    min-height: 200px;
    width: 100%;
}
</style>
