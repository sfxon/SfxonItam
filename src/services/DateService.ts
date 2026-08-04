/**
 * Helper functions to work to work with dates.
 */

/** Formats a Date as a local 'YYYY-MM-DD' string (no timezone conversion). */
export function toLocalDateString(date: Date): string {
    const y = date.getFullYear()
    const m = String(date.getMonth() + 1).padStart(2, '0')
    const d = String(date.getDate()).padStart(2, '0')
    return `${y}-${m}-${d}`
}

/** Formats a Date as a local 'YYYY-MM-DD HH:mm:ss' string (no timezone conversion). */
export function toLocalDateTimeString(date: Date): string {
    const h = String(date.getHours()).padStart(2, '0')
    const min = String(date.getMinutes()).padStart(2, '0')
    const s = String(date.getSeconds()).padStart(2, '0')
    return `${toLocalDateString(date)} ${h}:${min}:${s}`
}

/** Parses a local 'YYYY-MM-DD' string into a Date at local midnight. */
export function parseLocalDateString(value: string): Date {
    return new Date(`${value}T00:00:00`)
}

/**
 * Parses a local 'YYYY-MM-DD HH:mm:ss' string into a Date.
 * Normalizes the space to 'T' so the format is reliably parsed across browsers.
 */
export function parseLocalDateTimeString(value: string): Date {
    return new Date(value.replace(' ', 'T'))
}
