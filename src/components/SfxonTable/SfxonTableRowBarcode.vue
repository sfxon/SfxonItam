<script setup lang="ts" generic="T">

import SfxonBarcode from '@/components/SfxonBarcode'

const props = defineProps<{
    customStyle: String | null,
    name: String,
    prefix: String,
}>()

</script>
<template>
    <SfxonBarcode
        :name="name"
        :prefix="prefix"
        :customStyle="customStyle"
    />
</template>