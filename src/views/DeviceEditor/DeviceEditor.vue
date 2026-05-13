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
import NcTextField from '@nextcloud/vue/components/NcTextField'
import NcDateTimePickerNative from '@nextcloud/vue/components/NcDateTimePickerNative'
import NcSelect from '@nextcloud/vue/components/NcSelect'
import NcNoteCard from '@nextcloud/vue/components/NcNoteCard'
import { useApiErrors } from '@/composables/useApiErrors'
import { mdiClose } from '@mdi/js'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import { fetchDevice, createDevice, updateDevice } from '@/services/DeviceService'
import { fetchAllDeviceStatis } from '@/services/DeviceStatusService'
import { fetchAllDeviceTypes } from '@/services/DeviceTypeService'
import { fetchAllItamUsers } from '@/services/ItamUserService'
import { fetchAllLocations } from '@/services/LocationService'
import { fetchAllMerchants } from '@/services/MerchantService'
import { fetchAllPositions } from '@/services/PositionService'
import { fetchAllQuantityUnits } from '@/services/QuantityUnitService'
import SfxonMainNavigation from '@/components/SfxonMainNavigation'
import { getCurrentUser } from '@nextcloud/auth'

declare function qrcode(typeNumber: number, errorCorrectionLevel: string): any

// Formulardaten
const assetNumber = ref('')
const imageFileId = ref<number | null>(null)
const imagePreviewUrl = ref<string | null>(null)
const invoiceNumber = ref('')
const name = ref('')
const purchaseDate = ref<Date | null>(null)
const qrCodeSvg = ref<string | null>(null)
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
const locationsLoading = ref(false)
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

// Define functions.
function generateQrCode(id: number) {
    const qr = qrcode(0, 'M')
    qr.addData(generateUrl(`/apps/sfxonitam/device/detail?deviceId=${id}`))
    qr.make()
    qrCodeSvg.value = qr.createSvgTag(4, 0)
}

async function loadDevice(id: number): Promise<void> {
    deviceLoading.value = true

    try {
        const d = await fetchDevice(id)

        assetNumber.value = d.assetNumber ?? ''
        imageFileId.value = d.imageFileId ?? null
        invoiceNumber.value = d.invoiceNumber ?? ''
        name.value = d.name ?? ''
        purchaseDate.value = d.purchaseDate ? new Date(d.purchaseDate + 'T00:00:00') : null
        quantity.value = d.quantity ?? ''
        selectedDeviceStatus.value = deviceStatis.value.find(s => s.id === d.deviceStatusId) ?? null
        selectedDeviceType.value = deviceTypes.value.find(s => s.id == d.deviceTypeId) ?? null
        selectedItamUser.value = itamUsers.value.find(u => u.id === d.itamUserId) ?? null
        selectedMerchant.value = merchants.value.find(u => u.id === d.merchantId) ?? null
        selectedPosition.value = positions.value.find(s => s.id == d.positionId) ?? null
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

async function loadItamUsers() {
    itamUsersLoading.value = true;

    try {
        const data = await fetchAllItamUsers({})

        itamUsers.value = Object.values(data.itamUsers).map((itamUser: any) => ({
            id: itamUser.id,
            label: itamUser.firstname + ' ' + itamUser.lastname
        }))
    } catch(e) {
        console.error('Fehler beim Laden der ItamUsers', e)
    } finally {
        itamUsersLoading.value = false
    }
}

async function loadDeviceStatis() {
    deviceStatisLoading.value = true;

    try {
        const data = await fetchAllDeviceStatis({})

        deviceStatis.value = Object.values(data.deviceStatis).map((deviceStatus: any) => ({
            id: deviceStatus.id,
            label: deviceStatus.name
        }))
    } catch(e) {
        console.error('Fehler beim Laden der Device-Stati', e)
    } finally {
        deviceStatisLoading.value = false
    }
}

async function loadDeviceTypes() {
    deviceTypesLoading.value = true;

    try {
        const data = await fetchAllDeviceTypes({})

        deviceTypes.value = Object.values(data.deviceTypes).map((deviceType: any) => ({
            id: deviceType.id,
            label: deviceType.name
        }))
    } catch(e) {
        console.error('Fehler beim Laden der Device-Types', e)
    } finally {
        deviceTypesLoading.value = false
    }
}

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

async function loadMerchants() {
    merchantsLoading.value = true;

    try {
        const data = await fetchAllMerchants({})

        merchants.value = Object.values(data.merchants).map((merchant: any) => ({
            id: merchant.id,
            label: merchant.name
        }))
    } catch(e) {
        console.error('Fehler beim Laden der Merchants', e)
    } finally {
        merchantsLoading.value = false
    }
}

async function loadPositions() {
    await loadLocations()

    positionsLoading.value = true;

    try {
        const data = await fetchAllPositions({})

        positions.value = Object.values(data.positions).map((position: any) => {
            const location = locations.value.find(l => l.id == position.locationId)
            return {
                id: position.id,
                label: location
                    ? location.label + ' - ' + position.name
                    : position.name
            }
        })

        // Sort list alphabetically ASC.
        positions.value.sort((a, b) => a.label.localeCompare(b.label))
    } catch(e) {
        console.error('Error while loading Quantity Units', e)
    } finally {
        positionsLoading.value = false
    }
}

async function loadQuantityUnits() {
    quantityUnitsLoading.value = true;

    try {
        const data = await fetchAllQuantityUnits({})

        quantityUnits.value = Object.values(data.quantityUnits).map((quantityUnit: any) => ({
            id: quantityUnit.id,
            label: quantityUnit.name
        }))
    } catch(e) {
        console.error('Error while loading Quantity Units', e)
    } finally {
        quantityUnitsLoading.value = false
    }
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
    }
}

onMounted(async () => {
    await loadDeviceStatis()
    await loadDeviceTypes()
    await loadItamUsers()
    await loadMerchants()
    await loadPositions()
    await loadQuantityUnits()

    if (deviceId.value) {
        await loadDevice(deviceId.value)
        generateQrCode(deviceId.value)
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
        <NcAppContent :class="$style.content">
            <div :class="$style.form">
                <h2>
                    {{ isEditMode
                        ? t('sfxonitam', 'Edit device')
                        : t('sfxonitam', 'Create device') }}
                </h2>

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

                <div :class="$style.field">
                    <label class="label">{{ t('sfxonitam', 'Device Image') }}</label>
                    <div :class="$style.fileChooserRow">
                        <input
                            accept="image/*"
                            @change="onLocalFileChange"
                            :class="$style.fileUploadInput"
                            :label="t('sfxonitam', 'Upload image')"
                            type="file"
                        />
                        <NcButton type="button" variant="secondary" @click="openNextcloudFilePicker">
                            {{ t('sfxonitam', 'Select existing file') }}
                        </NcButton>
                    </div>
                    <div v-if="selectedImageLabel" :class="$style.selectedFileLabel">
                        {{ selectedImageLabel }}
                    </div>
                    <img
                        v-if="imagePreviewUrl"
                        :src="imagePreviewUrl"
                        :alt="t('sfxonitam', 'Device image preview')"
                        :class="$style.imagePreview"
                    />
                </div>

                <!-- QR Code -->
                <div v-if="isEditMode && qrCodeSvg" v-html="qrCodeSvg" :class="$style.qrCode" />

                <!-- name -->
                <div :class="$style.field">
                    <NcTextField
                        id="name"
                        v-model="name"
                        :label="t('sfxonitam', 'Name / Device-ID')"
                        :placeholder="t('sfxonitam', 'e.g. JP001')"
                        :class="fieldErrors.name ? $style.fieldError : ''"
                        @input="clearFieldError('name')"
                    />
                    <span v-if="fieldErrors.name" :class="$style.errorText">
                        {{ fieldErrors.name }}
                    </span>
                </div>

                <!-- quantity -->
                <div :class="$style.field">
                    <NcTextField
                        id="quantity"
                        v-model="quantity"
                        :label="t('sfxonitam', 'Quantity')"
                        :class="fieldErrors.name ? $style.fieldError : ''"
                        @input="clearFieldError('quantity')"
                        type="number"
                    />
                    <span v-if="fieldErrors.quantity" :class="$style.errorText">
                        {{ fieldErrors.quantity }}
                    </span>
                </div>

                <!-- QuantityUnit -->
                <div :class="$style.field">
                    <label for="quantity-unit-select" :class="$style.label">
                        {{ t('sfxonitam', 'Quantity Unit') }}
                    </label>
                    <NcSelect
                        id="quantity-unit-select"
                        v-model="selectedQuantityUnit"
                        :options="quantityUnits"
                        :loading="quantityUnitsLoading"
                        :label="'label'"
                        track-by="id"
                        :class="fieldErrors.quantityUnitId ? $style.fieldError : ''"
                        @input="clearFieldError('quantityUnitId')"
                    />
                    <span v-if="fieldErrors.quantityUnitId" :class="$style.errorText">
                        {{ fieldErrors.quantityUnitId }}
                    </span>
                </div>

                <!-- DeviceStatus -->
                <div :class="$style.field">
                    <label for="device-status-select" :class="$style.label">
                        {{ t('sfxonitam', 'Device Status') }}
                    </label>
                    <NcSelect
                        id="device-status-select"
                        v-model="selectedDeviceStatus"
                        :options="deviceStatis"
                        :loading="deviceStatisLoading"
                        :label="'label'"
                        track-by="id"
                        :class="fieldErrors.deviceStatusId ? $style.fieldError : ''"
                        @input="clearFieldError('deviceStatusId')"
                    />
                    <span v-if="fieldErrors.deviceStatusId" :class="$style.errorText">
                        {{ fieldErrors.deviceStatusId }}
                    </span>
                </div>

                <!-- Position -->
                <div :class="$style.field">
                    <label for="position-select" :class="$style.label">
                        {{ t('sfxonitam', 'Position') }}
                    </label>
                    <NcSelect
                        id="position-select"
                        v-model="selectedPosition"
                        :options="positions"
                        :loading="positionsLoading"
                        :label="'label'"
                        track-by="id"
                        :class="fieldErrors.positionId ? $style.fieldError : ''"
                        @input="clearFieldError('positionId')"
                    />
                    <span v-if="fieldErrors.positionId" :class="$style.errorText">
                        {{ fieldErrors.positionId }}
                    </span>
                </div>

                <!-- DeviceType -->
                <div :class="$style.field">
                    <label for="device-type-select" :class="$style.label">
                        {{ t('sfxonitam', 'Geräte-Typ') }}
                    </label>
                    <NcSelect
                        id="device-type-select"
                        v-model="selectedDeviceType"
                        :options="deviceTypes"
                        :loading="deviceTypesLoading"
                        :placeholder="t('sfxonitam', 'Geräte-Typ auswählen')"
                        :label="'label'"
                        track-by="id"
                        :class="fieldErrors.deviceTypeId ? $style.fieldError : ''"
                        @input="clearFieldError('deviceTypeId')"
                    />
                    <span v-if="fieldErrors.deviceTypeId" :class="$style.errorText">
                        {{ fieldErrors.deviceTypeId }}
                    </span>
                </div>

                <!-- ItamUser -->
                <div :class="$style.field">
                    <label for="user-select" :class="$style.label">
                        {{ t('sfxonitam', 'User') }}
                    </label>
                    <NcSelect
                        id="user-select"
                        v-model="selectedItamUser"
                        :options="itamUsers"
                        :loading="itamUsersLoading"
                        :placeholder="t('sfxonitam', 'Benutzer auswählen')"
                        :label="'label'"
                        track-by="id"
                        :class="fieldErrors.itamUserId ? $style.fieldError : ''"
                        @input="clearFieldError('itamUserId')"
                    />
                    <span v-if="fieldErrors.itamUserId" :class="$style.errorText">
                        {{ fieldErrors.itamUserId }}
                    </span>
                </div>

                <!-- serialNumber -->
                <div :class="$style.field">
                    <NcTextField
                        id="serialNumber"
                        v-model="serialNumber"
                        :label="t('sfxonitam', 'Serial Number')"
                        :placeholder="t('sfxonitam', 'e.g. EX-123-45678-999')"
                        :class="fieldErrors.serialNumber ? $style.fieldError : ''"
                        @input="clearFieldError('serialNumber')"
                    />
                    <span v-if="fieldErrors.serialNumber" :class="$style.errorText">
                        {{ fieldErrors.serialNumber }}
                    </span>
                </div>

                <!-- serialNumber2 -->
                <div :class="$style.field">
                    <NcTextField
                        id="serialNumber2"
                        v-model="serialNumber2"
                        :label="t('sfxonitam', 'Serial Number 2')"
                        :placeholder="t('sfxonitam', 'e.g. EX-123-45678-999')"
                        :class="fieldErrors.serialNumber2 ? $style.fieldError : ''"
                        @input="clearFieldError('serialNumber2')"
                    />
                    <span v-if="fieldErrors.serialNumber2" :class="$style.errorText">
                        {{ fieldErrors.serialNumber2 }}
                    </span>
                </div>

                <!-- assetNumber -->
                <div :class="$style.field">
                    <NcTextField
                        id="assetNumber"
                        v-model="assetNumber"
                        :label="t('sfxonitam', 'Asset Number')"
                        :placeholder="t('sfxonitam', 'e.g. EX-123-45678-999')"
                        :class="fieldErrors.assetNumber ? $style.fieldError : ''"
                        @input="clearFieldError('assetNumber')"
                    />
                    <span v-if="fieldErrors.assetNumber" :class="$style.errorText">
                        {{ fieldErrors.assetNumber }}
                    </span>
                </div>

                <!-- Merchant -->
                <div :class="$style.field">
                    <label for="merchant-select" :class="$style.label">
                        {{ t('sfxonitam', 'Merchant') }}
                    </label>
                    <NcSelect
                        id="user-select"
                        v-model="selectedMerchant"
                        :options="merchants"
                        :loading="merchantsLoading"
                        :placeholder="t('sfxonitam', 'Select merchants')"
                        :label="'label'"
                        track-by="id"
                        :class="fieldErrors.merchantId ? $style.fieldError : ''"
                        @input="clearFieldError('merchantId')"
                    />
                    <span v-if="fieldErrors.merchantId" :class="$style.errorText">
                        {{ fieldErrors.merchantId }}
                    </span>
                </div>

                <!-- invoiceNumber -->
                <div :class="$style.field">
                    <NcTextField
                        id="invoiceNumber"
                        v-model="invoiceNumber"
                        :label="t('sfxonitam', 'Invoice Number')"
                        :placeholder="t('sfxonitam', 'e.g. EX-123-45678-999')"
                        :class="fieldErrors.invoiceNumber ? $style.fieldError : ''"
                        @input="clearFieldError('invoiceNumber')"
                    />
                    <span v-if="fieldErrors.invoiceNumber" :class="$style.errorText">
                        {{ fieldErrors.invoiceNumber }}
                    </span>
                </div>

                <!-- purchaseDate -->
                <div :class="$style.field">
                    <label for="purchaseDate" :class="$style.label">
                        {{ t('sfxonitam', 'Kaufdatum') }}
                    </label>
                    <div :class="$style.dateRow">
                        <NcDateTimePickerNative
                            id="purchase-date"
                            v-model="purchaseDate"
                            type="date"
                            :class="fieldErrors.purchaseDate ? $style.fieldError : ''"
                            :label="''"
                            @input="clearFieldError('purchaseDate')"
                        />
                        <NcButton
                            :disabled="purchaseDate === null"
                            type="button"
                            :aria-label="t('sfxonitam', 'Datum entfernen')"
                            @click="purchaseDate = null"
                            >
                            <NcIconSvgWrapper
                                :path="mdiClose"
                                :size="16"
                            />
                        </NcButton>
                    </div>

                    <span v-if="fieldErrors.purchaseDate" :class="$style.errorText">
                        {{ fieldErrors.purchaseDate }}
                    </span>
                </div>

                <!-- Absenden -->
                <div :class="$style.actions">
                    <NcButton variant="primary" @click="submitForm">
                        {{ t('sfxonitam', 'Speichern') }}
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

.fileUploadInput {
    padding-top: 3px!important;
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

.dateRow {
    align-items: center;
    display: flex;
}

.dateRow button {
    max-height: 1rem;
    margin-left: 6px;
    margin-top: 1px;
}

.fileChooserRow {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    align-items: center;
}

.selectedFileLabel {
    color: var(--color-text-secondary);
    font-size: 0.875rem;
    margin-top: 4px;
}

.imagePreview {
    max-width: 100%;
    border-radius: 6px;
    margin-top: 8px;
    object-fit: contain;
}

.dateRow :global(.native-datetime-picker) {
    flex-grow: 1;
}

.qrCode {
    margin-top: 8px;
    width: 120px;
    height: 120px;
}

.qrCode :global(svg) {
    width: 100%;
    height: 100%;
}
</style>
