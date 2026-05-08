<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import NcAppContent from '@nextcloud/vue/components/NcAppContent'
import NcAppNavigation from '@nextcloud/vue/components/NcAppNavigation'
import NcAppNavigationList from '@nextcloud/vue/components/NcAppNavigationList'
import NcAppNavigationNew from '@nextcloud/vue/components/NcAppNavigationNew'
import NcContent from '@nextcloud/vue/components/NcContent'
import { mdiPlus } from '@mdi/js'
import { translate as t } from '@nextcloud/l10n'
import { generateUrl } from '@nextcloud/router'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcTextField from '@nextcloud/vue/components/NcTextField'
import NcDateTimePickerNative from '@nextcloud/vue/components/NcDateTimePickerNative'
import NcSelect from '@nextcloud/vue/components/NcSelect'
import axios from '@nextcloud/axios'
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
import SfxonMainNavigation from '@/components/SfxonMainNavigation'


// Formulardaten
const name = ref('')
const purchaseDate = ref<Date | null>(null)
const selectedDeviceStatus = ref<{ id: string; label: string } | null>(null)
const selectedDeviceType = ref<{ id: string; label: string } | null>(null)
const selectedItamUser = ref<{ id: string; label: string } | null>(null)
const selectedPosition = ref<{ id: string; label: string } | null>(null)
const selectedMerchant = ref<{ id: string; label: string } | null>(null)
const savedSuccessfully = ref(false)

// Ladezustände
const deviceLoading = ref(false)
const deviceStatisLoading = ref(false)
const deviceTypesLoading = ref(false)
const itamUsersLoading = ref(false)
const locationsLoading = ref(false)
const merchantsLoading = ref(false)
const positionsLoading = ref(false)
const isSaving = ref(false)

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

const toLocalDateString = (date: Date): string => {
    const y = date.getFullYear()
    const m = String(date.getMonth() + 1).padStart(2, '0')
    const d = String(date.getDate()).padStart(2, '0')
    return `${y}-${m}-${d}`
}

// Funktionen definieren.
async function loadDevice(id: number): Promise<void> {
    deviceLoading.value = true

    try {
        const d = await fetchDevice(id)

        name.value = d.name ?? ''
        selectedDeviceStatus.value = deviceStatis.value.find(s => s.id === d.deviceStatusId) ?? null
        selectedDeviceType.value = deviceTypes.value.find(s => s.id == d.deviceTypeId) ?? null
        selectedItamUser.value = itamUsers.value.find(u => u.id === d.itamUserId) ?? null
        selectedMerchant.value = merchants.value.find(u => u.id === d.merchantId) ?? null
        selectedPosition.value = positions.value.find(s => s.id == d.positionId) ?? null
        purchaseDate.value = d.purchaseDate ? new Date(d.purchaseDate + 'T00:00:00') : null
    } catch (e: any) {
        generalError.value = t('sfxonitam', 'Gerät konnte nicht geladen werden.')
        console.error('Fehler beim Laden des Geräts:', e)
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
        console.error('Fehler beim Laden der Positionen', e)
    } finally {
        positionsLoading.value = false
    }
}

async function submitForm() {
    clearErrors()
    savedSuccessfully.value = false;
    isSaving.value = true

    const payload = {
        name: name.value,
        deviceStatusId: selectedDeviceStatus.value?.id ?? null,
        deviceTypeId: selectedDeviceType.value?.id ?? null,
        itamUserId: selectedItamUser.value?.id ?? null,
        merchantId: selectedMerchant.value?.id ?? null,
        positionId: selectedPosition.value?.id ?? null,
        purchaseDate: purchaseDate.value ? toLocalDateString(purchaseDate.value) : null,
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
                :text="t('sfxonitam', 'Neues Gerät')"
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
                        ? t('sfxonitam', 'Gerät bearbeiten')
                        : t('sfxonitam', 'Gerät erfassen') }}
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
                    {{ t('sfxonitam', 'Die Änderungen wurden gespeichert.') }}
                </NcNoteCard>

                <!-- Geräte-Name -->
                <div :class="$style.field">
                    <NcTextField
                        id="name"
                        v-model="name"
                        :label="t('sfxonitam', 'Name / Geräte-ID')"
                        :placeholder="t('sfxonitam', 'z.B. JP001')"
                        :class="fieldErrors.name ? $style.fieldError : ''"
                        @input="clearFieldError('name')"
                    />
                    <span v-if="fieldErrors.name" :class="$style.errorText">
                        {{ fieldErrors.name }}
                    </span>
                </div>

                <!-- Geräte-Status -->
                <div :class="$style.field">
                    <label for="device-status-select" :class="$style.label">
                        {{ t('sfxonitam', 'Geräte-Status') }}
                    </label>
                    <NcSelect
                        id="device-status-select"
                        v-model="selectedDeviceStatus"
                        :options="deviceStatis"
                        :loading="deviceStatisLoading"
                        :placeholder="t('sfxonitam', 'Geräte-Status auswählen')"
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
                        :placeholder="t('sfxonitam', 'Position auswählen')"
                        :label="'label'"
                        track-by="id"
                        :class="fieldErrors.positionId ? $style.fieldError : ''"
                        @input="clearFieldError('positionId')"
                    />
                    <span v-if="fieldErrors.positionId" :class="$style.errorText">
                        {{ fieldErrors.positionId }}
                    </span>
                </div>

                <!-- Geräte-Typ -->
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

                <!-- itamUser -->
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

                <!-- merchant -->
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

                <!-- Purchase-Date -->
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

.dateRow :global(.native-datetime-picker) {
    flex-grow: 1;
}
</style>
