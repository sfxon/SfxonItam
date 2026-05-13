<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { generateUrl } from '@nextcloud/router'
import qrcode from '@/static-vendor/qrcode-generator-kazuhiko-arase/qrcode.js'

const props = defineProps<{
    customStyle: any,
    deviceId: string,
}>()

const qrCodeSvg = ref<string | null>(null)

// Define functions.
function generateQrCode(id: string) {
    const qr = qrcode(0, 'M')
    qr.addData(generateUrl(`/apps/sfxonitam/device/detail?deviceId=${id}`))
    qr.make()
    qrCodeSvg.value = qr.createSvgTag(4, 0)
}

onMounted(async () => {
    generateQrCode(props.deviceId)
})
</script>
<template>
    <div v-if="qrCodeSvg" v-html="qrCodeSvg" :class="$style.qrCode" :style="props.customStyle !== null ? props.customStyle : ''" />
</template>

<style module>
.qrCode {
    width: 120px;
    height: 120px;
}

.qrCode :global(svg) {
    width: 100%;
    height: 100%;
}
</style>
