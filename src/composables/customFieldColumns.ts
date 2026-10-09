import type { CellMounted } from '@/composables/useMediaPreview'

export const CUSTOM_FIELD_COLUMN_PREFIX = 'custom.'

export interface CustomField {
    technicalName: string
    name: string
}

export function customFieldColumnKey(technicalName: string): string {
    return `${CUSTOM_FIELD_COLUMN_PREFIX}${technicalName}`
}

export function withCustomFieldValues<T extends Record<string, any>>(row: T, customFields: CustomField[]): T {
    const result: Record<string, any> = { ...row }

    for (const cf of customFields) {
        result[customFieldColumnKey(cf.technicalName)] = row.customFields?.[cf.technicalName] ?? ''
    }

    return result as T
}

export function customFieldColumns(
    customFields: CustomField[],
    cellMounted: CellMounted,
    rowUrl: (dataRow: any) => string,
) {
    return customFields.map((cf) => ({
        key: customFieldColumnKey(cf.technicalName),
        label: cf.name,
        sortable: false,
        cellMounted,
        colLinkCallback: rowUrl,
    }))
}
