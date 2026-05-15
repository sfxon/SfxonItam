<script setup lang="ts" generic="T">

import { generateUrl } from '@nextcloud/router'
import { mdiDelete, mdiPencil, mdiOpenInNew } from '@mdi/js'
import NcActions from '@nextcloud/vue/components/NcActions'
import NcActionButton from '@nextcloud/vue/components/NcActionButton'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import NcPopover from '@nextcloud/vue/components/NcPopover'
import SfxonQrCodeView from '@/components/SfxonQrCodeView'

const props = defineProps<{
    columns: T[],
    dataArray: T[],
    dataArrayKey: "id", // This is a column in the given data, that tells, which key to use.
    dataRow: T[],
    deleteCallback: null,
    editCallback: null,
    orderBy: "",
    orderByCallback: null
    orderDirection: "ASC",
    relatedEntityData: T[]
}>()

// Cleanup Event Handlers, when the rows are not longer used (removing rows from table for example).
const cleanups = new Map<HTMLElement, () => void>()

function onCellRef(col: any, el: HTMLElement | null) {
    if (el instanceof HTMLElement) {
        const cleanup = col.cellMounted?.(el, props.dataRow)
        if (typeof cleanup === 'function') cleanups.set(el, cleanup)
    } else if (el === null) {
        cleanups.forEach(fn => fn())
        cleanups.clear()
    }
}

function getRelatedEntityData(col: any) {
    return props.relatedEntityData[col.relatedEntityName]
        ?.find(item => item.id === props.dataRow[col.key])?.id ?? null
}

</script>
<template>
    <td
        v-for="col in columns"
        :key="col.key"
        :class="col.type === 'actions' ? 'action-col' : ''"
        :ref="(el) => onCellRef(col, el as HTMLElement | null)"
    >
        <span v-if="col.type == null">
            <template v-if="col.colLinkCallback">
                <a :href="col.colLinkCallback(props.dataRow)" :class="$style.sfxonRowEditLink">
                    {{ props.dataRow[col.key] }}
                </a>
            </template>
            <template
                v-else
            >
                {{ props.dataRow[col.key] }}
            </template>
        </span>
        <span v-else-if="col.type == 'date'">
            <template v-if="col.colLinkCallback">
                <a :href="col.colLinkCallback(props.dataRow)" :class="$style.sfxonRowEditLink">
                    {{ props.dataRow[col.key]
                        ? new Intl.DateTimeFormat(undefined, { dateStyle: 'medium' }).format(new Date(props.dataRow[col.key]))
                        : '&nbsp;'
                    }}
                </a>
            </template>
            <template v-else>
                    {{ props.dataRow[col.key]
                        ? new Intl.DateTimeFormat(undefined, { dateStyle: 'medium' }).format(new Date(props.dataRow[col.key]))
                        : '&nbsp;'
                    }}
            </template>
        </span>
        <span
            v-else-if="col.type === 'quantityWithUnit'"
        >
            <template v-if="col.colLinkCallback">
                <a :href="col.colLinkCallback(props.dataRow)" :class="$style.sfxonRowEditLink">
                    {{ props.dataRow[col.key] != null ? parseFloat(props.dataRow[col.key]) : '' }}
                    {{ relatedEntityData[col.relatedEntityName]?.find(item => item.id === props.dataRow[col.relatedEntityKey])?.label ?? props.dataRow[col.relatedEntityName] }}
                </a>
            </template>
            <template v-else>
                {{ props.dataRow[col.key] != null ? parseFloat(props.dataRow[col.key]) : '' }}
                {{ relatedEntityData[col.relatedEntityName]?.find(item => item.id === props.dataRow[col.relatedEntityKey])?.label ?? props.dataRow[col.relatedEntityName] }}
            </template>
        </span>
        <span
            v-else-if="col.type === 'relatedEntity'"
            :class="$style.relatedEntityCol"
        >
            <template v-if="col.colLinkCallback">
                <a :href="col.colLinkCallback(props.dataRow)" :class="$style.sfxonRowEditLink">
                    {{ relatedEntityData[col.relatedEntityName]?.find(item => item.id === props.dataRow[col.key])?.label ?? props.dataRow[col.key] }}
                </a>
            </template>
            <template v-else>
                {{ relatedEntityData[col.relatedEntityName]?.find(item => item.id === props.dataRow[col.key])?.label ?? props.dataRow[col.key] }}
            </template>
            <template v-if="col.entityDetailUrlCallback && getRelatedEntityData(col)">
                <a :href="col.entityDetailUrlCallback?.(getRelatedEntityData(col))">
                    <NcIconSvgWrapper :path="mdiOpenInNew" :size="16" :class="$style.relatedEntityColExternalLink" />
                </a>
            </template>
        </span>
        <span v-else-if="col.type === 'image'">
            <img
                v-if="props.dataRow[col.key]"
                :src="generateUrl(`/core/preview?fileId=${props.dataRow[col.key]}&x=48&y=48&a=1`)"
                :alt="col.label ?? ''"
                style="width: 48px; height: 48px; object-fit: cover; border-radius: 4px; display: block;"
            />
            <span v-else>&nbsp;</span>
        </span>
        <span v-else-if="col.type === 'qrCode'">
            <SfxonQrCodeView
                :deviceId="props.dataRow[col.key]"
                customStyle="width: 24px; height: 24px;"
            />
        </span>
        <span v-else-if="col.type === 'actions'">
            <NcActions>
                <NcActionButton @click="props.editCallback && props.editCallback(props.dataRow)">
                    <template #icon>
                        <NcIconSvgWrapper :path="mdiPencil" :size="20" />
                    </template>
                    Bearbeiten
                </NcActionButton>
                <NcActionButton @click="props.deleteCallback && props.deleteCallback(props.dataRow)">
                    <template #icon>
                        <NcIconSvgWrapper :path="mdiDelete" :size="20" />
                    </template>
                    Löschen
                </NcActionButton>
            </NcActions>
        </span>
    </td>
</template>

<style module>
    .sfxonRowEditLink {
        color: var(--color-primary-element-light-text);
    }

    .relatedEntityCol {
        align-content: center;
        align-items: center;
        display: flex;
        color: var(--color-primary-element-light-text);
    }

    .relatedEntityColExternalLink {
        color: var(--color-primary-element-light-text)!important;
        opacity: .6!important;
    }

    .relatedEntityColExternalLink:hover {
        opacity: 1!important;
    }
</style>