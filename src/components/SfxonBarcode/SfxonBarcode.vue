<script setup lang="ts">
import { ref, watch, nextTick } from 'vue'
import JsBarcode from 'jsbarcode'

const props = defineProps<{
    customStyle: any,
    name: string,
    prefix: string,
}>()

const barcodeSvg = ref<SVGElement | null>(null)

// Define functions.
function generateBarcode(id: string, prefix: string) {
    if (!barcodeSvg.value) return

    JsBarcode(barcodeSvg.value, `${prefix}-${id}`, {
        format: 'CODE128',
        displayValue: true,
        fontSize: 14,
        margin: 10,
    })
}



// Varible name can be undefined in first render attempt - so put a watcher on it.
watch(
    () => [props.name, props.prefix],
    ([name, prefix]) => {
        if (name && prefix) {
            nextTick(() => generateBarcode(name, prefix))
        }
    },
    { immediate: true }
)
</script>
<template>
    <div :class="$style.wrapper">
        <div :class="$style.barcodeWrapper">
            <svg
                ref="barcodeSvg"
                :class="$style.barcode"
                :style="customStyle !== null ? customStyle : ''"
            >
            </svg>
        </div>
    </div>
</template>

<style module>
.wrapper {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 16px;
    width: 100%;
}

.barcodeWrapper {
    display: flex;
    justify-content: center;
    width: 100%;
}

.barcode {
    max-width: 500px;
    width: 100%;
}
</style>
