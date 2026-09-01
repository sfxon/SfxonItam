<script setup lang="ts">
import { ref, watch, nextTick } from 'vue'

const props = defineProps({
    visible: {
        type: Boolean,
        default: false,
    },
    isReflash: {
        type: Boolean,
        default: false,
    },
    flashKey: {
        type: [Number, String],
        default: 0,
    },
})

const badgeEl = ref<HTMLElement | null>(null)

watch(() => props.flashKey, async () => {
    if (!props.visible || !badgeEl.value) {
        return
    }

    await nextTick()

    const el = badgeEl.value
    el.style.animation = 'none'
    void el.offsetWidth
    el.style.animation = ''
})
</script>

<template>
    <div :class="$style.saveBadgeOuter">
        <Transition name="save-badge">
            <div
                v-if="visible"
                ref="badgeEl"
                :class="[$style.saveBadge, isReflash && $style.saveBadgeReflash]"
                aria-live="polite"
                role="status">
                <svg
                    viewBox="0 0 24 24"
                    fill="none"
                    xmlns="http://www.w3.org/2000/svg"
                    :class="$style.saveBadgeIcon">
                    <circle
                        cx="12"
                        cy="12"
                        r="10"
                        stroke="currentColor"
                        stroke-width="1.8"
                        fill="rgba(255,255,255,0.1)" />
                    <polyline
                        points="7,12.5 10.5,16 17,9"
                        stroke="currentColor"
                        stroke-width="2.2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        fill="none" />
                </svg>
            </div>
        </Transition>
    </div>
</template>

<style module>
.saveBadgeOuter {
    position: absolute;
    top: 6px;
    right: -134px;
    width: 126px;
    height: 100%;
    overflow: visible;
    pointer-events: none;
    z-index: 1500;
}

.saveBadge {
    align-items: center;
    background: color-mix(in srgb, var(--color-success) 80%, transparent);
    border-radius: var(--border-radius-large, 12px);
    box-shadow:
        0 6px 20px color-mix(in srgb, var(--color-success) 50%, transparent),
        0 2px 6px rgba(0,0,0,0.15),
        inset 0 1px 0 rgba(255,255,255,0.15);
    display: flex;
    height: 108px;
    justify-content: center;
    pointer-events: auto;
    position: sticky;
    right: 0;
    top: 12px;
    transform-origin: top right;
    translate: -126px 0;
    width: 108px;
    z-index: 9999;
}

.saveBadgeIcon {
    color: var(--color-success-text);
    height: 66px;
    width: 66px;
}

.saveBadgeReflash {
    animation:
        saveBadgeReflashScale 0.45s cubic-bezier(0.34, 1.56, 0.64, 1) both,
        saveBadgeFlash 0.55s ease-out both;
}

:global(.save-badge-enter-from) {
    opacity: 0;
    transform: scale(0.5) translateY(-8px);
}

:global(.save-badge-enter-active) {
    transition: opacity 0.25s ease-out, transform 0.45s cubic-bezier(0.34, 1.56, 0.64, 1);
}

:global(.save-badge-enter-to) {
    opacity: 1;
    transform: scale(1) translateY(0);
}

:global(.save-badge-leave-active) {
    display: none;
    position: absolute;
    transition: opacity 0.4s ease, transform 0.4s ease;
}

:global(.save-badge-leave-to) {
    opacity: 0;
    transform: scale(0.8) translateY(-4px);
}

@keyframes saveBadgeReflashScale {
    0%   { transform: scale(1.08); }
    100% { transform: scale(1); }
}

@keyframes saveBadgeFlash {
    0%   { box-shadow: 0 6px 20px color-mix(in srgb, var(--color-success) 50%, transparent), 0 2px 6px rgba(0,0,0,0.15); }
    30%  { box-shadow: 0 0 0 10px color-mix(in srgb, var(--color-success) 35%, transparent), 0 8px 30px color-mix(in srgb, var(--color-success) 70%, transparent); }
    100% { box-shadow: 0 6px 20px color-mix(in srgb, var(--color-success) 50%, transparent), 0 2px 6px rgba(0,0,0,0.15); }
}
</style>
