import { ref } from 'vue'

export interface ApiErrorResponse {
    status: 'error' | 'success'
    errors?: Record<string, string>
}

export function useApiErrors() {
    const fieldErrors = ref<Record<string, string>>({})
    const generalError = ref<string | null>(null)

    function handleApiError(response: ApiErrorResponse, fallback = 'Ein Fehler ist aufgetreten.') {
        fieldErrors.value = response.errors ?? {}
        generalError.value = Object.keys(fieldErrors.value).length > 0
            ? fallback
            : null
    }

    function clearErrors() {
        fieldErrors.value = {}
        generalError.value = null
    }

    function clearFieldError(field: string) {
        delete fieldErrors.value[field]
    }

    return { fieldErrors, generalError, handleApiError, clearErrors, clearFieldError }
}
