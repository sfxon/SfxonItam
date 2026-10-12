import { loadState } from '@nextcloud/initial-state'
import { fetchEntityList } from '@/services/EntityListService'
import { buildForeignKeyLabel, customFieldColumnKey, parseForeignKeyOptions, usableCustomFields } from '@/composables/customFieldColumns'
import type { CustomField } from '@/composables/customFieldColumns'
import type { RelatedOption, RelationMeta } from '@/composables/useRelatedEntities'

const SEARCH_LIMIT = 50

type SearchFn = (query: string, signal: AbortSignal) => Promise<void>

export function registerCustomFieldFilterOptions(
    customFields: CustomField[],
    data: Record<string, RelatedOption[]>,
    searchFns: Record<string, SearchFn>,
): void {
    const entityMeta = loadState<Record<string, RelationMeta>>('sfxonitam', 'entityMeta', {})

    for (const cf of usableCustomFields(customFields)) {
        if (cf.type !== 'foreign_key') {
            continue
        }

        const foreignKey = parseForeignKeyOptions(cf)
        const meta = foreignKey ? entityMeta[foreignKey.targetEntity] : undefined

        if (!foreignKey || !meta) {
            continue
        }

        const key = customFieldColumnKey(cf.technicalName)
        const searchFields = foreignKey.labelComposition
            .filter((part) => part.type === 'field')
            .map((part) => part.id)

        data[key] = []

        searchFns[key] = async (query, signal) => {
            const filters = query ? { _search: [query], _searchFields: searchFields } : {}

            try {
                const response = await fetchEntityList(meta.route, { page: 1, limit: SEARCH_LIMIT, filters }, signal)
                const mainData = response?.data?.mainData

                if (!mainData) {
                    return
                }

                const merged = new Map<string, RelatedOption>(data[key].map((option) => [String(option.id), option]))

                for (const record of Object.values(mainData) as Record<string, any>[]) {
                    merged.set(String(record.id), {
                        id: record.id,
                        label: buildForeignKeyLabel(record, foreignKey.labelComposition),
                    })
                }

                data[key] = [...merged.values()].sort((a, b) => a.label.localeCompare(b.label))
            } catch (e) {
                if (signal.aborted) {
                    return
                }

                throw e
            }
        }
    }
}