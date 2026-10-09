<script setup lang="ts">
import { translate as t } from '@nextcloud/l10n'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcDialog from '@nextcloud/vue/components/NcDialog'

withDefaults(defineProps<{
    open: boolean
    title: string
    message: string
    confirmLabel: string
    cancelLabel?: string
    confirmVariant?: 'primary' | 'error'
}>(), {
    cancelLabel: undefined,
    confirmVariant: 'error',
})

defineEmits<{
    (e: 'confirm'): void
    (e: 'cancel'): void
}>()
</script>

<template>
    <NcDialog
        v-if="open"
        :name="title"
        :open="open"
        @closing="$emit('cancel')"
    >
        <p>{{ message }}</p>

        <template #actions>
            <NcButton
                variant="tertiary"
                @click="$emit('cancel')"
            >
                {{ cancelLabel ?? t('sfxonitam', 'Cancel') }}
            </NcButton>
            <NcButton
                :variant="confirmVariant"
                @click="$emit('confirm')"
            >
                {{ confirmLabel }}
            </NcButton>
        </template>
    </NcDialog>
</template>