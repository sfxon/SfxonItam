import type { CellMounted } from '@/composables/useMediaPreview'

export interface ListColumnContext {
    relatedEntityOf: (key: string) => string | undefined
    detailUrl: (entity: any, idOrRow: any) => string
    rowUrl: (dataRow: any) => string
    cellMounted: CellMounted
}

type Extra = Record<string, unknown>

export function createListColumns(ctx: ListColumnContext) {
    const linked = (extra: Extra = {}) => ({
        sortable: true,
        cellMounted: ctx.cellMounted,
        colLinkCallback: ctx.rowUrl,
        ...extra,
    })

    return {
        text: (key: string, label: string, extra: Extra = {}) => ({ key, label, ...linked(extra) }),
        date: (key: string, label: string) => ({ type: 'date', key, label, ...linked() }),
        relation: (key: string, label: string) => {
            const entity = ctx.relatedEntityOf(key)

            return {
                type: 'relatedEntity',
                relatedEntityName: entity,
                key,
                label,
                entityDetailUrlCallback: (idOrRow: any) => ctx.detailUrl(entity, idOrRow),
                ...linked(),
            }
        },
        quantityWithUnit: (key: string, unitKey: string, label: string) => {
            const entity = ctx.relatedEntityOf(unitKey)

            return {
                type: 'quantityWithUnit',
                relatedEntityName: entity,
                key,
                relatedEntityKey: unitKey,
                entityDetailUrlCallback: (idOrRow: any) => ctx.detailUrl(entity, idOrRow),
                label,
                ...linked(),
            }
        },
        media: (type: string, key: string, label: string, cellMounted: CellMounted, extra: Extra = {}) => ({
            type,
            key,
            label,
            cellMounted,
            ...extra,
        }),
        actions: (label: string) => ({
            type: 'actions',
            key: '#actions',
            label,
            sortable: false,
            cellMounted: ctx.cellMounted,
        }),
        textFilter: (key: string, label: string) => ({ key, label }),
        relationFilter: (key: string, label: string) => ({
            type: 'relatedEntity',
            relatedEntityName: ctx.relatedEntityOf(key),
            key,
            label,
        }),
        numericRangeFilter: (key: string, labelFrom: string, labelTo: string) => ({
            type: 'numericFromTo',
            key,
            labelFrom,
            labelTo,
        }),
        dateRangeFilter: (key: string, labelFrom: string, labelTo: string) => ({
            type: 'date',
            key,
            labelFrom,
            labelTo,
        }),
    }
}
