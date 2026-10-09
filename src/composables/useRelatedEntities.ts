import { reactive } from 'vue'
import { generateUrl } from '@nextcloud/router'
import { fetchEntityList } from '@/services/EntityListService'

export interface RelationMeta {
    entity: string
    route: string
    labelFields: string[]
    labelParent: string | null
    labelSeparator: string
    searchFields: string[]
}

export interface RelatedOption {
    id: number | string
    label: string
}

type RelationRows = Record<string, Record<string, any>>

const SEARCH_LIMIT = 50

export function useRelatedEntities(metaByEntity: Record<string, RelationMeta>) {
    const data = reactive<Record<string, RelatedOption[]>>(
        Object.fromEntries(Object.keys(metaByEntity).map((name) => [name, []])),
    )

    function buildLabel(meta: RelationMeta, row: any, relationRows?: RelationRows): string {
        const own = meta.labelFields
            .map((field) => row[field])
            .filter((value) => value !== null && value !== undefined && value !== '')
            .join(' ')

        if (!meta.labelParent) {
            return own
        }

        const parent = row[meta.labelParent]
            ?? relationRows?.[meta.labelParent]?.[row[`${meta.labelParent}Id`]]

        return parent?.name ? `${parent.name}${meta.labelSeparator}${own}` : own
    }

    function toOptions(meta: RelationMeta, rows: any[], relationRows?: RelationRows): RelatedOption[] {
        return rows
            .map((row) => ({ id: row.id, label: buildLabel(meta, row, relationRows) }))
            .sort((a, b) => a.label.localeCompare(b.label))
    }

    function mergeOptions(name: string, options: RelatedOption[]) {
        const merged = new Map<string, RelatedOption>(data[name].map((o) => [String(o.id), o]))
        for (const option of options) {
            merged.set(String(option.id), option)
        }
        data[name] = [...merged.values()].sort((a, b) => a.label.localeCompare(b.label))
    }

    function applyRelations(relationRows?: RelationRows) {
        if (!relationRows) {
            return
        }

        for (const [name, rows] of Object.entries(relationRows)) {
            const meta = metaByEntity[name]
            if (!meta || !rows) {
                continue
            }

            mergeOptions(name, toOptions(meta, Object.values(rows), relationRows))
        }
    }

    async function search(entity: string, query: string, signal: AbortSignal): Promise<void> {
        const meta = metaByEntity[entity]
        if (!meta) {
            return
        }

        const filters = query
            ? Object.fromEntries(meta.searchFields.map((field) => [field, [query]]))
            : {}

        try {
            const response = await fetchEntityList(
                meta.route,
                { page: 1, limit: SEARCH_LIMIT, filters },
                signal,
            )
            const mainData = response?.data?.mainData
            if (!mainData) {
                return
            }

            mergeOptions(entity, toOptions(meta, Object.values(mainData), response.data.relations))
        } catch (e) {
            if (signal.aborted) {
                return
            }
            throw e
        }
    }

    const searchFns: Record<string, (query: string, signal: AbortSignal) => Promise<void>> = Object.fromEntries(
        Object.keys(metaByEntity).map((name) => [name, (query: string, signal: AbortSignal) => search(name, query, signal)]),
    )

    function detailUrl(entity: string | undefined, idOrRow: any): string {
        const meta = entity ? metaByEntity[entity] : undefined
        const id = typeof idOrRow === 'object' ? idOrRow?.id : idOrRow
        if (!meta || id === null || id === undefined) {
            return ''
        }

        return generateUrl(`/apps/sfxonitam/${meta.route}/detail?${entity}Id=${id}`)
    }

    return { data, applyRelations, searchFns, detailUrl }
}
