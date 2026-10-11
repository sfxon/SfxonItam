import { translate as t } from '@nextcloud/l10n'
import type { CellMounted } from '@/composables/useMediaPreview'

export const CUSTOM_FIELD_COLUMN_PREFIX = 'custom.'

export interface CustomField {
    technicalName?: string | null
    name?: string | null
}

type UsableCustomField = CustomField & { technicalName: string }

export function customFieldColumnKey(technicalName: string): string {
    return `${CUSTOM_FIELD_COLUMN_PREFIX}${technicalName}`
}

function usableCustomFields(customFields: unknown): UsableCustomField[] {
    if (!Array.isArray(customFields)) {
        return []
    }

    const seen = new Set<string>()
    const result: UsableCustomField[] = []

    for (const cf of customFields as CustomField[]) {
        const technicalName = typeof cf?.technicalName === 'string' ? cf.technicalName.trim() : ''

        if (technicalName === '' || seen.has(technicalName)) {
            continue
        }

        seen.add(technicalName)
        result.push({ ...cf, technicalName })
    }

    return result
}
function formatCustomFieldValue(value: unknown): string {
    if (value === null || value === undefined) {
        return ''
    }

    if (typeof value === 'boolean') {
        return value ? t('sfxonitam', 'Yes') : t('sfxonitam', 'No')
    }

    if (Array.isArray(value)) {
        return value.map(formatCustomFieldValue).filter((v) => v !== '').join(', ')
    }

    if (typeof value === 'object') {
        return JSON.stringify(value)
    }

    return String(value)
}

export function withCustomFieldValues<T extends Record<string, any>>(row: T, customFields: CustomField[]): T {
    const result: Record<string, any> = { ...row }
    const values = row.customFields && typeof row.customFields === 'object' ? row.customFields : {}

    for (const cf of usableCustomFields(customFields)) {
        result[customFieldColumnKey(cf.technicalName)] = formatCustomFieldValue(values[cf.technicalName])
    }

    return result as T
}

export function customFieldColumns(
    customFields: CustomField[],
    cellMounted: CellMounted,
    rowUrl: (dataRow: any) => string,
) {
    return usableCustomFields(customFields).map((cf) => ({
        key: customFieldColumnKey(cf.technicalName),
        label: cf.name?.trim() || cf.technicalName,
        sortable: false,
        cellMounted,
        colLinkCallback: rowUrl,
    }))
}
