import { translate as t } from '@nextcloud/l10n'
import type { CellMounted } from '@/composables/useMediaPreview'

export const CUSTOM_FIELD_COLUMN_PREFIX = 'custom.'

export interface CustomField {
    technicalName?: string | null
    name?: string | null
    type?: string | null
    options?: string | null
}

type UsableCustomField = CustomField & { technicalName: string }

export function customFieldColumnKey(technicalName: string): string {
    return `${CUSTOM_FIELD_COLUMN_PREFIX}${technicalName}`
}

export function usableCustomFields(customFields: unknown): UsableCustomField[] {
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
    const labels = row.customFieldLabels && typeof row.customFieldLabels === 'object' ? row.customFieldLabels : {}

    for (const cf of usableCustomFields(customFields)) {
        const label = cf.type === 'foreign_key' ? labels[cf.technicalName] : undefined

        result[customFieldColumnKey(cf.technicalName)] = typeof label === 'string'
            ? label
            : formatCustomFieldValue(values[cf.technicalName])
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
        sortable: true,
        cellMounted,
        colLinkCallback: rowUrl,
    }))
}

export type LabelCompositionPart = { type: 'field'; id: string } | { type: 'text'; value: string }

export interface ForeignKeyOptions {
    targetEntity: string
    labelComposition: LabelCompositionPart[]
}

export function parseForeignKeyOptions(customField: CustomField): ForeignKeyOptions | null {
    if (!customField.options) {
        return null
    }

    try {
        const foreignKey = JSON.parse(customField.options)?.foreignKey

        if (typeof foreignKey?.targetEntity !== 'string' || foreignKey.targetEntity === '') {
            return null
        }

        const labelComposition = (Array.isArray(foreignKey.labelComposition) ? foreignKey.labelComposition : [])
            .filter((part: any): part is LabelCompositionPart =>
                (part?.type === 'field' && typeof part.id === 'string')
                || (part?.type === 'text' && typeof part.value === 'string'))

        return { targetEntity: foreignKey.targetEntity, labelComposition }
    } catch {
        return null
    }
}

export function buildForeignKeyLabel(record: Record<string, any>, labelComposition: LabelCompositionPart[]): string {
    return labelComposition
        .map((part) => part.type === 'field' ? String(record[part.id] ?? '') : part.value)
        .join('')
}

export function customFieldFilters(customFields: CustomField[], isKnownEntity: (entity: string) => boolean) {
    const filters: Record<string, unknown>[] = []

    for (const cf of usableCustomFields(customFields)) {
        const key = customFieldColumnKey(cf.technicalName)
        const label = cf.name?.trim() || cf.technicalName
        const labelFrom = t('sfxonitam', '{label} from', { label })
        const labelTo = t('sfxonitam', '{label} to', { label })

        switch (cf.type) {
            case 'text':
            case 'longtext':
                filters.push({ key, label })
                break
            case 'integer':
            case 'decimal':
                filters.push({ type: 'numericFromTo', key, label, labelFrom, labelTo })
                break
            case 'date':
            case 'datetime':
                filters.push({ type: 'date', key, label, labelFrom, labelTo })
                break
            case 'foreign_key': {
                const foreignKey = parseForeignKeyOptions(cf)

                if (foreignKey !== null
                    && foreignKey.labelComposition.some((part) => part.type === 'field')
                    && isKnownEntity(foreignKey.targetEntity)) {
                    filters.push({ type: 'relatedEntity', relatedEntityName: key, key, label })
                }

                break
            }
        }
    }

    return filters
}
