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
    <div
        v-if="qrCodeSvg"
        :class="$style.qrCodeWrapper"
    >
        <div 
            v-html="qrCodeSvg"
            :class="$style.qrCode"
            :style="props.customStyle !== null ? props.customStyle : ''"
        />
    </div>
</template>

<style module>
.qrCodeWrapper {
    display: flex;
    justify-content: center;
    width: 100%;
}

.qrCode {
    height: 100%;
    margin: 0 auto;
    max-height: 220px;
    max-width: 220px;
    width: 100%;
}

.qrCode :global(svg) {
    width: 100%;
    height: 100%;
}
</style>
