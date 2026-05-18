<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import NcAppContent from '@nextcloud/vue/components/NcAppContent'
import NcAppNavigation from '@nextcloud/vue/components/NcAppNavigation'
import NcAppNavigationList from '@nextcloud/vue/components/NcAppNavigationList'
import NcAppNavigationNew from '@nextcloud/vue/components/NcAppNavigationNew'
import NcContent from '@nextcloud/vue/components/NcContent'
import { mdiClose, mdiPlus } from '@mdi/js'
import { translate as t } from '@nextcloud/l10n'
import { generateUrl, generateRemoteUrl } from '@nextcloud/router'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcTextArea from '@nextcloud/vue/components/NcTextArea'
import NcTextField from '@nextcloud/vue/components/NcTextField'
import NcDateTimePickerNative from '@nextcloud/vue/components/NcDateTimePickerNative'
import NcSelect from '@nextcloud/vue/components/NcSelect'
import NcNoteCard from '@nextcloud/vue/components/NcNoteCard'
import { useApiErrors } from '@/composables/useApiErrors'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import { fetchDevice, createDevice, updateDevice } from '@/services/DeviceService'
import { fetchAllDeviceStatis } from '@/services/DeviceStatusService'
import { fetchAllDeviceTypes } from '@/services/DeviceTypeService'
import { fetchAllItamUsers } from '@/services/ItamUserService'
import { fetchAllLocations } from '@/services/LocationService'
import { fetchAllMerchants } from '@/services/MerchantService'
import { fetchAllPositions } from '@/services/PositionService'
import { fetchAllQuantityUnits } from '@/services/QuantityUnitService'
import SfxonBarcode from '@/components/SfxonBarcode'
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

async function loadDevice(id: number): Promise<void> {
    deviceLoading.value = true

    try {
        const d = await fetchDevice(id)

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
            const location = locations.value.find(l => l.id === position.locationId)
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
        console.error('Error while loading Positions', e)
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
    await loadDeviceStatis()
    await loadDeviceTypes()
    await loadItamUsers()
    await loadMerchants()
    await loadPositions()
    await loadQuantityUnits()

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
            <div :class="$style.sfxonItamHeader">
                <div class="content-title">
                    {{ isEditMode
                        ? t('sfxonitam', 'Edit device')
                        : t('sfxonitam', 'Create device')
                    }}
                </div>
                <div :class="$style.sfxonItamHeaderSidebarToggleBtn">
                    <NcButton
                        @click="onBackButton"
                    >
                        {{ t('sfxonitam', 'Back') }}
                    </NcButton>
                </div>
            </div>

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

            <div :class="$style.form">
                <div :class="[$style.sfxonFormRow, $style.sfxonFormRowOne]">
                    <div :class="$style.sfxonFormSection">
                        <div :class="$style.sfxonFormColumn">
                            <!-- name -->
                            <div :class="$style.sfxonFormColumnRow">
                                <div :class="$style.sfxonFormColumnLabel">
                                    <label for="name" :class="$style.label">
                                        {{ t('sfxonitam', 'Device Identifier') }}:
                                    </label>
                                </div>
                                <div :class="$style.sfxonFormColumnInput">
                                    <div :class="$style.field">
                                        <NcTextField
                                            id="name"
                                            v-model="name"
                                            :label-outside="true"
                                            :placeholder="t('sfxonitam', 'e.g. JP001')"
                                            :class="fieldErrors.name ? $style.fieldError : ''"
                                            @input="clearFieldError('name')"
                                        />
                                        <span v-if="fieldErrors.name" :class="$style.errorText">
                                            {{ fieldErrors.name }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Quantity -->
                            <div :class="$style.sfxonFormColumnRow">
                                <div :class="$style.sfxonFormColumnLabel">
                                    <label for="quantity" :class="$style.label">
                                        {{ t('sfxonitam', 'Quantity') }}:
                                    </label>
                                </div>
                                <div :class="$style.sfxonFormColumnInput">
                                    <!-- quantity -->
                                    <div :class="$style.field">
                                        <NcTextField
                                            id="quantity"
                                            v-model="quantity"
                                            :label-outside="true"
                                            :class="fieldErrors.quantity ? $style.fieldError : ''"
                                            @input="clearFieldError('quantity')"
                                            type="number"
                                        />
                                        <span v-if="fieldErrors.quantity" :class="$style.errorText">
                                            {{ fieldErrors.quantity }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Quantity-Unit -->
                            <div :class="$style.sfxonFormColumnRow">
                                <div :class="$style.sfxonFormColumnLabel">
                                    <label for="quantity-unit-select" :class="$style.label">
                                        {{ t('sfxonitam', 'Quantity Unit') }}:
                                    </label>
                                </div>
                                <div :class="$style.sfxonFormColumnInput">
                                    <div :class="$style.field">
                                        <NcSelect
                                            id="quantity-unit-select"
                                            v-model="selectedQuantityUnit"
                                            :options="quantityUnits"
                                            :loading="quantityUnitsLoading"
                                            track-by="id"
                                            :class="fieldErrors.quantityUnitId ? $style.fieldError : ''"
                                            @input="clearFieldError('quantityUnitId')"
                                        />
                                        <span v-if="fieldErrors.quantityUnitId" :class="$style.errorText">
                                            {{ fieldErrors.quantityUnitId }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- ItamUser -->
                            <div :class="$style.sfxonFormColumnRow">
                                <div :class="$style.sfxonFormColumnLabel">
                                    <label for="user-select" :class="$style.label">
                                        {{ t('sfxonitam', 'User') }}:
                                    </label>
                                </div>
                                <div :class="$style.sfxonFormColumnInput">
                                    <div :class="$style.field">
                                        <NcSelect
                                            id="user-select"
                                            v-model="selectedItamUser"
                                            :options="itamUsers"
                                            :loading="itamUsersLoading"
                                            track-by="id"
                                            :class="fieldErrors.itamUserId ? $style.fieldError : ''"
                                            @input="clearFieldError('itamUserId')"
                                        />
                                        <span v-if="fieldErrors.itamUserId" :class="$style.errorText">
                                            {{ fieldErrors.itamUserId }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Position -->
                            <div :class="$style.sfxonFormColumnRow">
                                <div :class="$style.sfxonFormColumnLabel">
                                    <label for="position-select" :class="$style.label">
                                        {{ t('sfxonitam', 'Position') }}:
                                    </label>
                                </div>
                                <div :class="$style.sfxonFormColumnInput">
                                    <div :class="$style.field">
                                        <NcSelect
                                            id="position-select"
                                            v-model="selectedPosition"
                                            :options="positions"
                                            :loading="positionsLoading"
                                            track-by="id"
                                            :class="fieldErrors.positionId ? $style.fieldError : ''"
                                            @input="clearFieldError('positionId')"
                                        />
                                        <span v-if="fieldErrors.positionId" :class="$style.errorText">
                                            {{ fieldErrors.positionId }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- serialNumber -->
                             <div :class="$style.sfxonFormColumnRow">
                                <div :class="$style.sfxonFormColumnLabel">
                                    <label for="serialNumber" :class="$style.label">
                                        {{ t('sfxonitam', 'Serial Number') }}:
                                    </label>
                                </div>
                                <div :class="$style.sfxonFormColumnInput">
                                    <div :class="$style.field">
                                        <NcTextField
                                            id="serialNumber"
                                            v-model="serialNumber"
                                            :label-outside="true"
                                            :class="fieldErrors.serialNumber ? $style.fieldError : ''"
                                            @input="clearFieldError('serialNumber')"
                                        />
                                        <span v-if="fieldErrors.serialNumber" :class="$style.errorText">
                                            {{ fieldErrors.serialNumber }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- serialNumber2 -->
                            <div :class="$style.sfxonFormColumnRow">
                                <div :class="$style.sfxonFormColumnLabel">
                                    <label for="serialNumber2" :class="$style.label">
                                        {{ t('sfxonitam', 'Serial Number 2') }}:
                                    </label>
                                </div>
                                <div :class="$style.sfxonFormColumnInput">
                                    <div :class="$style.field">
                                        <NcTextField
                                            id="serialNumber2"
                                            v-model="serialNumber2"
                                            :label-outside="true"
                                            :class="fieldErrors.serialNumber2 ? $style.fieldError : ''"
                                            @input="clearFieldError('serialNumber2')"
                                        />
                                        <span v-if="fieldErrors.serialNumber2" :class="$style.errorText">
                                            {{ fieldErrors.serialNumber2 }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- assetNumber -->
                            <div :class="$style.sfxonFormColumnRow">
                                <div :class="$style.sfxonFormColumnLabel">
                                    <label for="assetNumber" :class="$style.label">
                                        {{ t('sfxonitam', 'Asset Number') }}:
                                    </label>
                                </div>
                                <div :class="$style.sfxonFormColumnInput">
                                    <div :class="$style.field">
                                        <NcTextField
                                            id="assetNumber"
                                            v-model="assetNumber"
                                            :label-outside="true"
                                            :class="fieldErrors.assetNumber ? $style.fieldError : ''"
                                            @input="clearFieldError('assetNumber')"
                                        />
                                        <span v-if="fieldErrors.assetNumber" :class="$style.errorText">
                                            {{ fieldErrors.assetNumber }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div :class="$style.sfxonFormColumn">
                            <!-- Device Status -->
                            <div :class="$style.sfxonFormColumnRow">
                                <div :class="$style.sfxonFormColumnLabel">
                                    <label for="device-status-select" :class="$style.label">
                                        {{ t('sfxonitam', 'Device Status') }}:
                                    </label>
                                </div>
                                <div :class="$style.sfxonFormColumnInput">
                                    <div :class="$style.field">
                                        <NcSelect
                                            id="device-status-select"
                                            v-model="selectedDeviceStatus"
                                            :options="deviceStatis"
                                            :loading="deviceStatisLoading"
                                            track-by="id"
                                            :class="fieldErrors.deviceStatusId ? $style.fieldError : ''"
                                            @input="clearFieldError('deviceStatusId')"
                                        />
                                        <span v-if="fieldErrors.deviceStatusId" :class="$style.errorText">
                                            {{ fieldErrors.deviceStatusId }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Device Type -->
                            <div :class="$style.sfxonFormColumnRow">
                                <div :class="$style.sfxonFormColumnLabel">
                                    <label for="device-type-select" :class="$style.label">
                                        {{ t('sfxonitam', 'Device Type') }}:
                                    </label>
                                </div>
                                <div :class="$style.sfxonFormColumnInput">
                                    <div :class="$style.field">
                                        <NcSelect
                                            id="device-type-select"
                                            v-model="selectedDeviceType"
                                            :options="deviceTypes"
                                            :loading="deviceTypesLoading"
                                            track-by="id"
                                            :class="fieldErrors.deviceTypeId ? $style.fieldError : ''"
                                            @input="clearFieldError('deviceTypeId')"
                                        />
                                        <span v-if="fieldErrors.deviceTypeId" :class="$style.errorText">
                                            {{ fieldErrors.deviceTypeId }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div :class="$style.sfxonFormSection">
                        <div :class="$style.sfxonFormColumn">
                            <!-- Image -->
                             <div :class="$style.field">
                                <div :class="$style.imageContainer">
                                    <img
                                        v-if="imagePreviewUrl"
                                        :src="imagePreviewUrl"
                                        :alt="t('sfxonitam', 'Device image preview')"
                                        :class="$style.imagePreview"
                                    />
                                    <div v-else :class="$style.imagePlaceholder">
                                        {{ t('sfxonitam', 'No image selected') }}
                                    </div>
                                </div>
                                <div v-if="selectedImageLabel" :class="$style.selectedFileLabel">
                                    {{ selectedImageLabel }}
                                </div>
                                <div :class="$style.fileChooserRow">
                                    <input
                                        accept="image/*"
                                        @change="onLocalFileChange"
                                        :class="$style.fileUploadInput"
                                        type="file"
                                    />
                                    <NcButton
                                        :class="$style.fileSelectInput"
                                        @click="openNextcloudFilePicker"
                                        variant="secondary"
                                        type="button">
                                        {{ t('sfxonitam', 'Select existing file') }}
                                    </NcButton>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div :class="[$style.sfxonFormRow]">
                    <!-- Description -->
                    <div :class="$style.sfxonFormSection">
                        <div :class="$style.sfxonFormColumn">
                            <div :class="[$style.sfxonFormColumnRow, $style.sfxonFormColumnRowTextareaFull]">
                                <div :class="[$style.sfxonFormColumnInput, $style.sfxonFormColumnInputTextareaFull]">
                                    <div :class="$style.field">
                                        <NcTextArea
                                            id="description"
                                            :label="t('sfxonitam', 'Description') + ':'"
                                            v-model="description"
                                            :class="[fieldErrors.description ? $style.fieldError : '', $style.sfxonDescriptionInput]"
                                            @input="clearFieldError('description')"
                                        >
                                        </NcTextArea>
                                        <span v-if="fieldErrors.description" :class="$style.errorText">
                                            {{ fieldErrors.description }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Purchase Information -->
                    <div :class="$style.sfxonFormSection">
                        <div :class="$style.sfxonFormColumn">
                            <!-- Merchant -->
                            <div :class="$style.sfxonFormColumnRow">
                                <div :class="$style.sfxonFormColumnLabel">
                                    <label for="merchant-select" :class="$style.label">
                                        {{ t('sfxonitam', 'Merchant') }}:
                                    </label>
                                </div>
                                <div :class="$style.sfxonFormColumnInput">
                                    <div :class="$style.field">
                                        <NcSelect
                                            id="merchant-select"
                                            v-model="selectedMerchant"
                                            :options="merchants"
                                            :loading="merchantsLoading"
                                            track-by="id"
                                            :class="fieldErrors.merchantId ? $style.fieldError : ''"
                                            @input="clearFieldError('merchantId')"
                                        />
                                        <span v-if="fieldErrors.merchantId" :class="$style.errorText">
                                            {{ fieldErrors.merchantId }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Invoice Number -->
                            <div :class="$style.sfxonFormColumnRow">
                                <div :class="$style.sfxonFormColumnLabel">
                                    <label for="invoiceNumber" :class="$style.label">
                                        {{ t('sfxonitam', 'Invoice Number') }}:
                                    </label>
                                </div>
                                <div :class="$style.sfxonFormColumnInput">
                                    <div :class="$style.field">
                                        <NcTextField
                                            id="invoiceNumber"
                                            v-model="invoiceNumber"
                                            :label-outside="true"
                                            :class="fieldErrors.invoiceNumber ? $style.fieldError : ''"
                                            @input="clearFieldError('invoiceNumber')"
                                        />
                                        <span v-if="fieldErrors.invoiceNumber" :class="$style.errorText">
                                            {{ fieldErrors.invoiceNumber }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Purchase Date -->
                            <div :class="$style.sfxonFormColumnRow">
                                <div :class="$style.sfxonFormColumnLabel">
                                    <label for="purchaseDate" :class="$style.label">
                                        {{ t('sfxonitam', 'Purchase Date') }}:
                                    </label>
                                </div>
                                <div :class="$style.sfxonFormColumnInput">
                                    <div :class="$style.field">
                                        <div :class="$style.dateRow">
                                            <NcDateTimePickerNative
                                                id="purchaseDate"
                                                v-model="purchaseDate"
                                                type="date"
                                                :class="fieldErrors.purchaseDate ? $style.fieldError : ''"
                                                :label="''"
                                                :placeholder="''"
                                                @input="clearFieldError('purchaseDate')"
                                            />
                                            <NcButton
                                                :disabled="purchaseDate === null"
                                                type="button"
                                                :aria-label="t('sfxonitam', 'Clear date')"
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
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- QrCode and Barcode -->
                    <div :class="$style.sfxonFormSection">
                        <div :class="$style.sfxonFormColumn">
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
                <div :class="[$style.sfxonFormRow, $style.sfxonFormRowActionBar]">
                    <div :class="[$style.sfxonFormSection, $style.sfxonFormSectionSave]">
                        <div :class="$style.sfxonFormColumn">
                            <div :class="$style.actions">
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
.sfxonItamHeader {
    align-items: center;
    background-color: var(--color-background-assistant);
    display: flex;
    flex: 0 0;
    font-weight: bold;
    gap: var(--default-grid-baseline);
    /*margin-block: var(--app-navigation-padding, 4px);*/
    /* margin-inline: calc(var(--default-clickable-area) + 2*var(--app-navigation-padding, 4px)) var(--app-navigation-padding, 4px);*/
    max-width: 100%;
    min-height: 32px;
    padding: 
        var(--app-navigation-padding)
        var(--app-navigation-padding)
        var(--app-navigation-padding)
        calc(var(--default-clickable-area) + 2*var(--app-navigation-padding, 4px));
}

.sfxonItamHeaderSidebarToggleBtn {
    margin-left: auto;
    margin-right: 0;
}

.fileUploadInput {
    padding-top: 3px!important;
    max-width: 200px;
}

.fileSelectInput {
    width: 100%!important;
    max-width: 200px;
}

.form {
    display: flex;
    flex-direction: column;
    position: relative;
    width: 100%;
}

.field {
    display: flex;
    flex-direction: column;
    gap: 4px;
    width: 100%;
}

.field :global(.input-field__input) {
    border-radius: 3px!important;
}

.field :global(.v-select) {
    background-color: var(--color-main-background);
}

.field :global(.vs__selected) {
    padding-left: 0!important;
}

.field :global(.vs__dropdown-toggle) {
    border-color: var(--input-border-box-shadow-light);
    border-radius: 3px;
    border-width: 0;
}

.label {
    width: 100%;
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
    align-items: center;
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-left: auto;
    margin-right: auto;
    margin-top: 12px;
    max-width: 200px;
}

.selectedFileLabel {
    color: var(--color-text-secondary);
    font-size: 0.875rem;
    margin-left: auto;
    margin-right: auto;
    margin-top: 4px;
    max-width: 200px;
    text-align: center;
    width: 100%;
}

.imageContainer {
    aspect-ratio: 1 / 1;
    border-radius: 6px;
    margin-left: auto;
    margin-right: auto;
    max-width: 200px;
    overflow: hidden;
    position: relative;
    width: 100%;
}

.imagePreview {
    background-color: var(--color-main-background);
    border: 1px solid var(--color-border);
    height: 100%;
    object-fit: contain;
    inset: 0;
    position: absolute;
    width: 100%;
}

.imagePlaceholder {
    align-items: center;
    border: 6px dashed var(--color-border);
    border-radius: 0;
    color: var(--color-text-lighter);
    display: flex;
    font-size: 0.9rem;
    inset: 0;
    justify-content: center;
    margin-left: auto;
    margin-right: auto;
    position: absolute;
}

.dateRow :global(.native-datetime-picker) {
    flex-grow: 1;
}

/* Architectural styles for the sections and rows */
.sfxonFormRow {
    display: flex;
}

.sfxonFormRow.sfxonFormRowActionBar {
    bottom: 0;
    position: sticky;
    width: 100%;
}

.sfxonFormSection {
    display: flex;
    background-color: var(--color-background-assistant);
    margin-left: var(--app-navigation-padding);
    margin-top: var(--app-navigation-padding);
    padding: calc(var(--app-navigation-padding)*2) calc(var(--app-navigation-padding)*2) var(--app-navigation-padding);
    width: 100%;
}

.sfxonFormSection.sfxonFormSectionSave {
    padding-top: 0;
}

.sfxonFormColumn {
    width: 100%;
}

.sfxonFormColumnRow {
    display: flex;
    padding-bottom: 6px;
}

.sfxonFormColumnLabel {
    align-content: center;
    display: flex;
    flex-direction: column;
    justify-content: center;
    width: 40%;
}

.sfxonFormColumnInput {
    display: flex;
    width: 60%;
}

/* Special Layout Designs */
.sfxonFormRowOne .sfxonFormSection:first-child {
    flex: 1.4;
}

.sfxonFormRowOne .sfxonFormSection:last-child {
    flex: .6;
}

.sfxonFormRowOne .sfxonFormSection:first-child .sfxonFormColumn:last-child {
    padding-left: 2rem;
}

/* Textarea for description */
.sfxonFormColumnRow.sfxonFormColumnRowTextareaFull {
    height: 100%!important;
}

.sfxonFormColumnInput.sfxonFormColumnInputTextareaFull {
    width: 100%!important;
}

.sfxonDescriptionInput {
    height: 100%!important;
}

.sfxonDescriptionInput :global(.textarea__main-wrapper) {
    height: 100%!important;
}

.sfxonDescriptionInput :global(textarea) {
    height: 100%!important;
    margin-top: 0!important;
    padding-top: 1.6rem!important;
    width: 100%!important;
}

.sfxonDescriptionInput :global(.textarea__label) {
    inset-block-start: 6px!important;
}
</style>
