import { createApp } from 'vue'
import App from './ManufacturerList.vue'

const el = document.getElementById('sfxonitam')
const customFields = JSON.parse(el.dataset.customFields || '[]')

const app = createApp(App, {
    customFields,
})
app.mount('#sfxonitam')
